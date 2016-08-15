<?php
/**
 * finc specific model for MARC records with a fullrecord in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace finc\RecordDriver;

/**
 * finc specific model for MARC records with a fullrecord in Solr.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
trait SolrMarcFincTrait
{
    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        return $this->hasILS();
    }

    /**
     * Do we have an attached ILS connection and (finc specific) do we want ILS support
     * for the records source_id and access_facet-value?
     *
     * @return bool
     */
    /*protected function hasILS()
    {
        // put all ILS supported source_id in here
        $ilsSourceIds = ['0'];

        // put all ILS supported access_facet values in here
        $accessFacetValues = ['Local Holdings'];

        if (in_array($this->getSourceID(), $ilsSourceIds)
            && in_array($this->getAccessFacet(), $accessFacetValues)
        ) {
            return parent::hasILS();
        }

        // ILS connection for this source_id not supported
        return false;
    }*/

    /**
     * Returns whether the current record is a RDA record (contains string 'rda' in
     * 040$e)
     *
     * @return bool
     */
    public function isRDA()
    {
        return $this->getFirstFieldValue('040', ['e']) == 'rda';
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        $retVal = [];

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = [
            '856' => ['u'],   // Standard URL
            '555' => ['a']         // Cumulative index/finding aids
        ];

        foreach ($fieldsToCheck as $field => $subfields) {
            $urls = $this->getMarcRecord()->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    $isil = $url->getSubfield('9');

                    $isISIL = false;

                    if ($isil) {
                        $isil = $isil->getData();
                        if (true === in_array($isil, $this->isil)) {
                            $isISIL = true;
                        }
                    } else {
                        $isISIL = true;
                    }

                    if ($isISIL) {
                        // Is there an address in the current field?
                        $address = $url->getSubfield('u');
                        if ($address) {
                            $address = $address->getData();

                            $tmpArr = [];
                            // Is there a description?  If not, just use the URL itself.
                            foreach (['y', '3', 'z', 'x'] as $current) {
                                $desc = $url->getSubfield($current);
                                if ($desc) {
                                    $desc = $desc->getData();
                                    $tmpArr[] = $desc;
                                }
                            }
                            $tmpArr = array_unique($tmpArr);
                            $desc = implode(', ', $tmpArr);

                            if (empty($desc)) {
                                $desc = $address;
                            }

                            // If url doesn't exist as key so far write to return variable.
                            if (!in_array(['url' => $address, 'desc' => $desc], $retVal)) {
                                $retVal[] = ['url' => $address, 'desc' => $desc];
                            }
                        }
                    }
                }
            }
        }
        return $retVal;
    }

    /**
     * Return the local callnumber.
     *
     * @todo Optimization by removing of prefixed isils
     *
     * @return array   Return fields.
     * @access public
     * @deprecated (https://intern.finc.info/issues/6324)
     * @link   https://intern.finc.info/issues/2639
     */
    public function getLocalCallnumber()
    {
        $array = [];

        if (isset($this->fields['itemdata'])) {
            $itemdata = json_decode($this->fields['itemdata'], true);
            if (count($itemdata) > 0) {
                // error_log('Test: '. print_r($this->fields['itemdata'], true));
                $i = 0;
                foreach ($this->isil as $isil) {
                    if (isset($itemdata[$isil])) {
                        foreach ($itemdata[$isil] as $val) {
                            $array[$i]['barcode'] = $val['bc'];
                            $array[$i]['callnumber'] = $val['cn'];
                            $i++;
                        }
                    } // end if
                } // end foreach
            } // end if
        } // end if
        return $array;
    }

    /**
     * Get local callnumbers of a special library.
     *
     * @return array
     * @access protected
     * @deprecated (https://intern.finc.info/issues/6324)
     */
    protected function getLocalCallnumbersByLibrary()
    {
        $array = [];
        $callnumbers = [];

        if (isset($this->fields['itemdata'])) {
            $itemdata = json_decode($this->fields['itemdata'], true);
            if (count($itemdata) > 0) {
                $i = 0;
                foreach ($this->isil as $isil) {
                    if (isset($itemdata[$isil])) {
                        foreach ($itemdata[$isil] as $val) {
                            // exclude equal callnumbers
                            if (false == in_array($val['cn'], $callnumbers)) {
                                $array[$i]['callnumber'] = $val['cn'];
                                $array[$i]['location'] = $isil;
                                $callnumbers[] = $val['cn'];
                                $i++;
                            }
                        } // end foreach
                    } // end if
                } // end foreach
            } // end if
        } // end if
        unset($callnumbers);
        return $array;
    }

    /**
     * Get the special local call number; for the moment only used by the
     * university library of Freiberg at finc marc 972i.
     *
     * @return string
     * @access protected
     */
    protected function getLocalGivenCallnumber()
    {
        $retval = [];
        $arrSignatur = $this->getFieldArray($this->getLocalMarcFieldOfLibrary(), ['i']);

        foreach ($arrSignatur as $signatur) {
            foreach ($this->isil as $code) {
                if (0 < preg_match('/^\('.$code.'\)/', $signatur)) {
                    $retval[] = preg_replace('/^\('.$code.'\)/', '', $signatur);
                }
            }
        }
        return $retval;
    }

    /**
     * Get an array of supplements and special issue entry.
     *
     * @link   http://www.loc.gov/marc/bibliographic/bd770.html
     * @return array
     * @access protected
     */
    protected function getSupplements()
    {
        //return $this->_getFieldArray('770', array('i','t')); // has been originally 'd','h','n','x' but only 'i' and 't' for ubl requested;
        $array = [];
        $supplement = $this->getMarcRecord()->getFields('770');
        // if not return void value
        if (!$supplement) {
            return $array;
        } // end if

        foreach ($supplement as $key => $line) {
            $array[$key]['pretext'] = ($line->getSubfield('i'))
                ? $line->getSubfield('i')->getData() : '';
            $array[$key]['text'] = ($line->getSubfield('t'))
                ? $line->getSubfield('t')->getData() : '';
            // get ppns of bsz
            $linkFields = $line->getSubfields('w');
            foreach ($linkFields as $current) {
                $text = $current->getData();
                // Extract parenthetical prefixes:
                if (preg_match(self::BSZ_PATTERN, $text, $matches)) {
                    //$id = $this->checkIfRecordExists($matches[2]);
                    //if ($id != null) {
                    $array[$key]['record_id'] = $matches[2].$matches[3];
                    //}
                    //break;
                }
            } // end foreach
        } // end foreach

        return $this->addFincIDToRecord($array);
    }

    /**
     * Special method to extracting the index of German prints of the marc21
     * field 024 indicator 8 subfield a
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/fincproject/issues/1442
     */
    protected function getIndexOfGermanPrints()
    {
        // define a false indicator
        $lookfor_indicator = '8';
        $retval = [];

        $fields = $this->getMarcRecord()->getFields('024');
        if (!$fields) {
            return null;
        }
        foreach ($fields as $field) {
            // ->getIndicator(position)
            $subjectrow = $field->getIndicator('1');
            if ($subjectrow == $lookfor_indicator) {
                if ($subfield = $field->getSubfield('a')) {
                    if (preg_match('/^VD/i', $subfield->getData()) > 0) {
                        $retval[] = $subfield->getData();
                    }
                }
            }
        }
        // echo "<pre>"; print_r($retval); echo "</pre>";
        return  $retval;
    }

    /**
     * Get an array of instrumentation notes taken from the local data
     * of the Petrucci music library subfield 590b
     *
     * @return array
     * @access protected
     */
    protected function getInstrumentation()
    {
        return $this->getFieldArray('590', ['b']);
    }

    /**
     * Get the ISSN from a record.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/fincproject/issues/969 description
     */
    protected function getISSN()
    {
        return $this->getFieldArray('022', ['a']);
    }

    /**
     * Get the ISSN from a the parallel title of a record.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/fincproject/issues/969 description
     */
    protected function getISSNsParallelTitles()
    {
        return $this->getFieldArray('029', ['a']);
    }

    /**
     * Get the content-designated representation, in a different script, (field 880)
     * of the given field. fieldIterator is used if no Linkage in subfield 6 is
     * found.
     *
     * @param $field
     * @param int|bool $fieldIterator
     * @return array|bool
     */
    protected function getLinkedField($field, $fieldIterator = false)
    {
        // we need to know which field we are dealing with
        $tagNo = $field->getTag();

        // if we found a subfield 6 in given field we can compute the content of
        // subfield 6 in the corresponding field 880
        if ($sub6 = $field->getSubfield(6)) {
            $sub6Id = $tagNo . substr($sub6->getData(), 3);

            // now cycle through all available fields 880 and return the field with
            // the exact match on computed $sub6Id
            if ($linkedFields = $this->getMarcRecord()->getFields('880')) {
                foreach ($linkedFields as $current) {
                    if ($sub6Id == $current->getSubfield(6)->getData()) {
                        return $current;
                    }
                }
            }
        }

        // alternative approach, cycle through all available fields 880 and return
        // the field with a field and iterator match.
        if ($fieldIterator !== false) {
            if ($linkedFields = $this->getMarcRecord()->getFields('880')) {
                $i = 0;
                foreach ($linkedFields as $current) {
                    if ($tagNo == substr($current->getSubfield(6)->getData(), 0, 3)
                    ) {
                        if ($fieldIterator == $i) {
                            return $current;
                        }
                        $i++;
                    }
                }
            }
        }

        // not enough information to return linked field
        return false;
    }

    /**
     * Return an array of all values extracted from the linked field (MARC 880)
     * corresponding with the specified field/subfield combination.  If multiple
     * subfields are specified and $concat is true, they will be concatenated
     * together in the order listed -- each entry in the array will correspond with a
     * single MARC field.  If $concat is false, the return array will contain
     * separate entries for separate subfields.
     *
     * @param string $field     The MARC field number used for identifying the linked
     *                          MARC field to read
     * @param array  $subfields The MARC subfield codes to read
     * @param bool   $concat    Should we concatenate subfields?
     * @param string $separator Separator string (used only when $concat === true)
     *
     * @return array
     */
    protected function getLinkedFieldArray($field, $subfields = null, $concat = true,
                                           $separator = ' '
    ) {
        // Default to subfield a if nothing is specified.
        if (!is_array($subfields)) {
            $subfields = ['a'];
        }

        // Initialize return array
        $matches = [];

        // Try to look up the specified field, return empty array if it doesn't
        // exist.
        $fields = $this->getMarcRecord()->getFields($field);
        if (!is_array($fields)) {
            return $matches;
        }

        $i = 0;
        // Extract all the linked fields.
        foreach ($fields as $currentField) {
            // Pass the iterator $i as a fallback if subfield $6 of MARC880 does not
            // contain the Linkage
            if ($linkedField = $this->getLinkedField($currentField, $i)) {
                // Extract all the requested subfields, if applicable.
                $next = $this
                    ->getSubfieldArray($linkedField, $subfields, $concat, $separator);
                $matches = array_merge($matches, $next);
            }
            $i ++;
        }

        return $matches;
    }

    /**
     * Get the original edition of the record.
     *
     * @return string
     */
    public function getEditionOrig()
    {
        return array_pop($this->getLinkedFieldArray('250', ['a']));
    }

    /**
     * Get an array of publication detail lines with original notations combining
     * information from MARC field 260 and linked content in 880.
     *
     * @return array
     */
    public function getPublicationDetails()
    {
        $retval = [];

        if ($fields = $this->getMarcRecord()->getFields('260')) {
            foreach ($fields as $i => $current) {
                $place = $current->getSubfield('a')
                    ? $current->getSubfield('a')->getData() : null;
                $name = $current->getSubfield('b')
                    ? $current->getSubfield('b')->getData() : null;
                $date = $current->getSubfield('c')
                    ? $current->getSubfield('c')->getData() : null;


                // Build objects to represent each set of data; these will
                // transform seamlessly into strings in the view layer.
                $retval[] = new \VuFind\RecordDriver\Response\PublicationDetails(
                    $place, $name, $date
                );

                // Build the publication details with additional graphical notations
                // for the current set of publication details
                if ($linkedField = $this->getLinkedField($current, $i)) {
                    $retval[] = new \VuFind\RecordDriver\Response\PublicationDetails(
                        $linkedField->getSubfield('a')
                            ? $linkedField->getSubfield('a')->getData() : null,
                        $linkedField->getSubfield('b')
                            ? $linkedField->getSubfield('b')->getData() : null,
                        $linkedField->getSubfield('c')
                            ? $linkedField->getSubfield('c')->getData() : null
                    );
                }
            }
        }

        return $retval;
    }

    /**
     * Hardcoded overwrite to force use of SolrDefault getSeries method in order
     * to prevent fullrecord loading in result-list due to Coins generation.
     *
     * @return array
     */
    public function getSeries()
    {
        return SolrDefault::getSeries();
    }

    /**
     * Get an array of title detail lines with original notations combining
     * information from MARC field 245 and linked content in 880.
     *
     * @return array
     */
    public function getTitleDetails()
    {
        return array_merge(
            $this->getFieldArray('245', ['a','b', 'c']),
            $this->getLinkedFieldArray('245', ['a', 'b', 'c'])
        );
    }

    /**
     * Get the original statement of responsibility that goes with the title (i.e.
     * "by John Smith").
     *
     * @return string
     */
    public function getTitleStatementOrig()
    {
        return array_pop($this->getLinkedFieldArray('245', ['c']));
    }

    /**
     * Support method for getSeries() -- given a field specification, look for
     * series information in the MARC record.
     *
     * @param array $fieldInfo Associative array of field => subfield information
     * (used to find series name)
     *
     * @return array
     */
    protected function getSeriesFromMARC($fieldInfo)
    {
        $matches = [];

        $buildSeries = function ($field, $subfields) use (&$matches) {
            // Can we find a name using the specified subfield list?
            $name = $this->getSubfieldArray($field, $subfields);
            if (isset($name[0])) {
                $currentArray = ['name' => $name[0]];

                // Can we find a number in subfield v?  (Note that number is
                // always in subfield v regardless of whether we are dealing
                // with 440, 490, 800 or 830 -- hence the hard-coded array
                // rather than another parameter in $fieldInfo).
                $number
                    = $this->getSubfieldArray($field, ['v']);
                if (isset($number[0])) {
                    $currentArray['number'] = $number[0];
                }

                // Save the current match:
                $matches[] = $currentArray;
            }
        };

        // Loop through the field specification....
        foreach ($fieldInfo as $field => $subfields) {
            // Did we find any matching fields?
            $series = $this->getMarcRecord()->getFields($field);
            if (is_array($series)) {
                // use the fieldIterator as fallback for linked data in field 880 that
                // is not linked via $6
                $fieldIterator = 0;
                foreach ($series as $currentField) {
                    // Can we find a name using the specified subfield list?
                    if (isset($this->getSubfieldArray($currentField, $subfields)[0])) {
                        $buildSeries($currentField, $subfields);

                        // attempt to find linked data in 880 field
                        if ($linkedData = $this->getLinkedField($currentField, $fieldIterator)) {
                            $buildSeries($linkedData, $subfields);
                        }
                    }
                    $fieldIterator ++;
                }
            }
        }

        return $matches;
    }

    /**
     * Get an array of information about Journal holdings realised for the
     * special needs of University library of Chemnitz. MAB fields 720.
     *
     * @return array
     * @access public
     * @link   https://intern.finc.info/fincproject/issues/338
     */
    public function getJournalHoldings()
    {
        $retval = [];
        $match = [];

        // Get ID and connect to catalog
        //$catalog = ConnectionManager::connectToCatalog();
        //$terms = $catalog->getConfig('OrderJournalTerms');

        $fields = $this->getMarcRecord()->getFields('971');
        if (!$fields) {
            return [];
        }

        $key = 0;
        foreach ($fields as $field) {
            /*if ($subfield = $field->getSubfield('j')) {
               preg_match('/\(.*\)(.*)/', $subfield->getData(), $match);
               $retval[$key]['callnumber'] = trim($match[1]);
            }*/
            if ($subfield = $field->getSubfield('k')) {
                preg_match('/(.*)##(.*)##(.*)/', trim($subfield->getData()), $match);
                $retval[$key]['callnumber'] = trim($match[1]);
                $retval[$key]['holdings'] = trim($match[2]);
                $retval[$key]['footnote'] = trim($match[3]);
                // deprecated check if a certain wording exist
                // $retval[$key]['is_holdable'] = (in_array(trim($match[3]), $terms['terms'])) ? 1 : 0;
                // if subfield k exists so make journal holdable
                $retval[$key]['is_holdable'] = 1;

                if (count($this->getBarcode()) == 1) {
                    $current = $this->getBarcode();
                    $barcode = $current[0];
                } else {
                    $barcode = '';
                }
                // deprecated check if a certain wording exist
                // $retval[$key]['link'] = (in_array(trim($match[3]), $terms['terms'])) ? '/Record/' . $this->getUniqueID() .'/HoldJournalCHE?callnumber=' . urlencode($retval[$key]['callnumber']) .'&barcode=' . $barcode  : '';
                // if subfield k exists so make journal holdable
                $retval[$key]['link'] = '/Record/' . $this->getUniqueID() .'/HoldJournalCHE?callnumber=' . urlencode($retval[$key]['callnumber']) .'&barcode=' . $barcode;
                //var_dump($retval[$key]['is_holdable'], $terms);
                $key++;
            }

        }
        return $retval;
    }

    /**
     * Return a local access number for call number.
     * Marc field depends on library e.g. 975 for WHZ.
     * Seems to be very extraordinary special case.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/issues/1302
     */
    protected function getLocalAccessNumber()
    {
        if (null != $this->getLocalMarcFieldOfLibrary()) {
            return $this->getFieldArray($this->getLocalMarcFieldOfLibrary(), ['o']);
        }
        return [];
    }

    /**
     * Get all local class subjects. First realization for HGB.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/issues/2626
     */
    protected function getLocalClassSubjects()
    {
        $array = [];
        $classsubjects = $this->getMarcRecord()->getFields('979');
        // if not return void value
        if (!$classsubjects) {
            return $array;
        } // end if
        foreach ($classsubjects as $key => $line) {
            // if subfield with class subjects exists
            if ($line->getSubfield('f')) {
                // get class subjects
                $array[$key]['nb'] = $line->getSubfield('f')->getData();
            } // end if subfield a
            if ($line->getSubfield('9')) {
                $array[$key]['data'] = $line->getSubfield('9')->getData();
                /*  $tmp = $line->getSubfield('9')->getData();
                $tmpArray = array();
                $data = explode(',', $tmp);
                if(is_array($data) && (count($data) > 0)) {
                    foreach ($data as $value) {
                        $tmpArray[] = $value;
                    }
                }
                if(count($tmpArray) > 0) {
                    $array[$key]['data'] = $tmpArray;
                } else {
                    $array[$key]['data'] = $data;
                }*/
            }
        } // end foreach
        return $array;
    }


    /**
     * Returning local format field of a library using an consortial defined
     * field with subfield $c. Marc field depends on library e.g. 970 for HMT or
     * 972 for TUBAF
     *
     * @return array
     * @access protected
     */
    public function getLocalFormat()
    {
        if (null != $this->getLocalMarcFieldOfLibrary()) {
            if (count($localformat = $this->getFieldArray($this->getLocalMarcFieldOfLibrary(), ['c'])) > 0) {
                foreach ($localformat as &$line) {
                    if ($line != "") {
                        $line = trim('local_format_' . strtolower($line));
                    }
                }
                unset($line);
                return $localformat;
            }
        }
        return [];
    }

    /**
     * Returns lazily the library specific Marc field configured by CustomIndex
     * settings in config.ini
     *
     * @return mixed
     * @link https://intern.finc.info/issues/7063
     */
    protected function getLocalMarcFieldOfLibrary()
    {
        // return the library specific Marc field if its already set
        if ($this->localMarcFieldOfLibrary != null) {
            return $this->localMarcFieldOfLibrary;
        }

        // get the library specific Marc field configured by CustomIndex settings in
        // config.ini
        if (isset($this->mainConfig->CustomIndex->localMarcFieldOfLibraryNamespace)) {
            $namespace = $this->mainConfig->CustomIndex->localMarcFieldOfLibraryNamespace;
            if (isset($this->mainConfig->CustomIndex->localMarcFieldOfLibraryMapping)) {
                foreach ($this->mainConfig->CustomIndex->localMarcFieldOfLibraryMapping as $mappingValue) {
                    list ($ns, $fn) = explode(':', $mappingValue);
                    if (trim($ns) == trim($namespace)) {
                        $this->localMarcFieldOfLibrary = $fn;
                        break;
                    }
                }
            }
        } else {
            $this->debug('Namespace setting for localMarcField is missing.');
        }
        return $this->localMarcFieldOfLibrary;
    }

    /**
     * Return a local notice via an consortial defined field with subfield $k.
     * Marc field depends on library e.g. 970 for HMT or 972 for TUBAF.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/fincproject/issues/1308
     */
    protected function getLocalNotice()
    {
        if (null != $this->getLocalMarcFieldOfLibrary()) {
            return $this->getFieldArray($this->getLocalMarcFieldOfLibrary(), ['k']);
        }
        return [];
    }

    /**
     * Get an array of musical heading based on a swb field
     * at the marc field.
     *
     * @return mixed        null if there's no field or array with results
     * @access protected
     */
    protected function getMusicHeading()
    {
        $retval = [];

        $fields = $this->getMarcRecord()->getFields('937');
        if (!$fields) {
            return null;
        }
        foreach ($fields as $key => $field) {
            if ($d = $field->getSubfield('d')) {
                $retval[$key][] = $d->getData();
            }
            if ($e = $field->getSubfield('e')) {
                $retval[$key][] = $e->getData();
            }
            if ($f = $field->getSubfield('f')) {
                $retval[$key][] = $f->getData();
            }
        }
        return $retval;
    }

    /**
     * Get notice of a title representing a special case of University
     * library of Chemnitz: MAB field 999l
     *
     * @return string
     * @access protected
     */
    protected function getNotice()
    {
        return $this->getFirstFieldValue('971', ['l']);
    }

    /**
     * Get an array of style/genre of a piece taken from the local data
     * of the Petrucci music library subfield 590a
     *
     * @return array
     * @access protected
     */
    protected function getPieceStyle()
    {
        return $this->getFieldArray('590', ['a']);
    }

    /**
     * Get specific marc information about parallel editions. Unflexible solution
     * for HMT only implemented.
     *
     * @todo More flexible implementation
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/issues/4327
     */
    protected function getParallelEditions()
    {
        $array = [];
        $fields = ['775'];
        $i = 0;

        foreach ($fields as $field) {

            $related = $this->getMarcRecord()->getFields($field);
            // if no entry break it
            if ($related) {
                foreach ($related as $key => $line) {
                    // check if subfields i or t exist. if yes do a record.
                    if ($line->getSubfield('i') || $line->getSubfield('t')) {
                        $array[$i]['identifier'] = ($line->getSubfield('i'))
                            ? $line->getSubfield('i')->getData() : '';
                        $array[$i]['text'] = ($line->getSubfield('t'))
                            ? $line->getSubfield('t')->getData() : '';
                        // get ppns of bsz
                        $linkFields = $line->getSubfields('w');
                        if (is_array($linkFields) && count($linkFields) > 0) {
                            foreach ($linkFields as $current) {
                                $text = $current->getData();
                                // Extract parenthetical prefixes:
                                if (preg_match(self::BSZ_PATTERN, $text, $matches)) {
                                    $array[$key]['record_id'] = $matches[2].$matches[3];
                                }
                            } // end foreach
                        } // end if
                        $i++;
                    } // end if
                } // end foreach
            }
        }
        return $this->addFincIDToRecord($array);
    }

    /**
     * Get an array of previous titles for the record.
     *
     * @todo use HttpService for URL query
     * @todo change currency service
     * @todo pass prices by euro currency
     *
     * @return string
     * @access protected
     */
    public function getPrice()
    {
        $currency = $this->getFirstFieldValue('365', ['c']);
        $price = $this->getFirstFieldValue('365', ['b']);
        if (!empty($currency) && !empty($price) ) {
            // if possible convert it in euro
            if (is_array($converted =
                json_decode(str_replace(
                    ['lhs','rhs','error','icc'],
                    ['"lhs"','"rhs"','"error"','"icc"'],
                    file_get_contents("http://www.google.com/ig/calculator?q=".$price.$currency."=?EUR")
                ),true)
            )) {
                if(empty($converted['error'])){
                    $rhs = explode(' ', trim($converted['rhs']));
                    return  money_format('%.2n', $rhs[0]);
                }
            }
            return $currency . " ". $price;
        }
        return "";
    }

    /**
     * Get the provenience of a title.
     *
     * @return array
     * @access protected
     */
    protected function getProvenience()
    {
        return $this->getFieldArray('561', ['a']);
    }

    /**
     * Checked if an title is ordered by the library using an consortial defined
     * field with subfield $m. Marc field depends on library e.g. 970 for HMT or
     * 972 for TUBAF
     *
     * @return bool
     * @access protected
     */
    protected function getPurchaseInformation()
    {
        if (null != $this->getLocalMarcFieldOfLibrary()) {
            if ($this->getFirstFieldValue($this->getLocalMarcFieldOfLibrary(), ['m']) == 'e') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a short list of series for ISBD citation style
     *
     * @return array
     * @access protected
     * @link   http://www.loc.gov/marc/bibliographic/bd830.html
     * @link   https://intern.finc.info/fincproject/issues/457
     */
    protected function getSeriesWithVolume()
    {
        return $this->getFieldArray('830', ['a', 'v'], false);
    }

    /**
     * Get local classification of UDK.
     *
     * @todo Check if method is used by other institution than HTWK.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/fincproject/issues/1135
     */
    protected function getUDKs()
    {
        $array = [];
        if (null != $this->getLocalMarcFieldOfLibrary()) {

            $udk = $this->getMarcRecord()->getFields($this->getLocalMarcFieldOfLibrary());
            // if not return void value
            if (!$udk) {
                return $array;
            } // end if

            foreach ($udk as $key => $line) {
                // if subfield with udk exists
                if ($line->getSubfield('f')) {
                    // get udk
                    $array[$key]['index'] = $line->getSubfield('f')->getData();
                    // get udk notation
                    // fixes by update of File_MARC to version 0.8.0
                    // @link https://intern.finc.info/issues/2068
                    /*
                    if ($notation = $line->getSubfield('n')) {
                        // get first value
                        $array[$key]['notation'][] = $notation->getData();
                        // iteration over udk notation
                        while ($record = $notation->next()) {
                            $array[$key]['notation'][] = $record->getData();
                            $notation = $record;
                        }
                    } // end if subfield n
                    unset($notation);
                    */
                    if ($record = $line->getSubfields('n')) {
                        // iteration over rvk notation
                        foreach ($record as $field) {
                            $array[$key]['notation'][] = $field->getData();
                        }
                    } // end if subfield n
                } // end if subfield f
            } // end foreach
        }
        //error_log(print_r($array, true));
        return $array;
    }

    /**
     * Get additional titles.
     *
     * @return array
     */
    public function getAdditionalTitles()
    {
        // result array to return
        $retval = [];

        $results = $this->getMarcRecord()->getFields('249');
        if (!$results) {
            return $retval;
        }

        foreach ($results as $line) {
            $retval[] = $line->getSubfield('a')->getData() .
                ($line->getSubfield('v')->getData() ? ' / ' . $line->getSubfield('v')->getData() : '');
        }
        return $retval;
    }

    /**
     * Get addional entries for personal names.
     *
     * @return array
     * @access protected
     * @link   http://www.loc.gov/marc/bibliographic/bd700.html
     */
    protected function getAdditionalAuthors()
    {
        // result array to return
        $retval = [];

        $results = $this->getMarcRecord()->getFields('700');
        if (!$results) {
            return $retval;
        }

        foreach ($results as $key => $line) {
            $retval[$key]['name'] = ($line->getSubfield('a'))
                ? $line->getSubfield('a')->getData() : '';
            $retval[$key]['dates'] = ($line->getSubfield('d'))
                ? $line->getSubfield('d')->getData() : '';
            $retval[$key]['relator'] = ($line->getSubfield('e'))
                ? $line->getSubfield('e')->getData() : '';
        }
        // echo "<pre>"; print_r($retval); echo "</pre>";
        return $retval;
    }

    /**
     * Get specific marc information about additional items. Unflexible solution
     * for UBL only implemented.
     *
     * @return array
     * @access protected
     * @link   https://intern.finc.info/fincproject/issues/1315
     */
    public function getAdditionals()
    {
        $array = [];
        $fields = ['770','775','776'];
        $subfields = ['a', 'l', 't', 'd', 'e', 'f', 'h', 'o', '7'];
        $i = 0;

        foreach ($fields as $field) {
            $related = $this->getMarcRecord()->getFields($field);
            // if no entry break it
            if ($related) {
                foreach ($related as $key => $line) {
                    // -----
                    // https://intern.finc.info/issues/6896#note-7
                    $text = [];
                    foreach ($subfields as $subfield) {
                        if ($line->getSubfield($subfield)) {
                            $text[] = $line->getSubfield($subfield)->getData();
                        }
                    }
                    $array[$i]['text'] = (count($text) > 0
                      ? implode(', ', $text) : '');
                    // -----
                    if ($line->getSubfield('i')) {
                        $array[$i]['identifier'] = ($line->getSubfield('i'))
                            ? $line->getSubfield('i')->getData() : '';
                        // get ppns of bsz
                        $linkFields = $line->getSubfields('w');
                        if (is_array($linkFields) && count($linkFields) > 0) {
                            foreach ($linkFields as $current) {
                                $text = $current->getData();
                                // Extract parenthetical prefixes:
                                if (preg_match(self::BSZ_PATTERN, $text, $matches)) {
                                    $array[$i]['record_id']
                                        = $matches[2] . $matches[3];
                                }
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        return $this->addFincIDToRecord($array);
    }

    /**
     * Special method to extracting the data of the marc21 field 689 of the
     * the bsz heading subjects chains.
     *
     * @return array
     * @access protected
     */
    protected function getAllSubjectHeadingsExtended()
    {
        // define a false indicator
        $firstindicator = 'x';
        $retval = [];

        $fields = $this->getMarcRecord()->getFields('689');
        foreach ($fields as $field) {
            $subjectrow = $field->getIndicator('1');
            if ($subjectrow != $firstindicator) {
                $key = (isset($key) ? $key +1 : 0);
                $firstindicator = $subjectrow;
            }
            // #5668 #5046 BSZ MARC may contain uppercase subfields but solrmarc set to lowercase them which introduces single char topics
            if ($subfields = $field->getSubfields('a')){
                foreach ($subfields as $subfield) {
                    if (strlen($subfield->getData()) > 1)
                        $retval[$key]['subject'][] = $subfield->getData();
                }
            }
            if ($subfield = $field->getSubfield('t')) {
                $retval[$key]['subject'][] = $subfield->getData();
            }
            if ($subfield = $field->getSubfield('9')) {
                $retval[$key]['subsubject'] = $subfield->getData();
            }
        }
        return  $retval;
    }

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @return array
     */
    public function getAllSubjectHeadings()
    {
        // These are the fields that may contain subject headings:
        $fields = [
            '600', '610', '611', '630', '648', '650', '651', '653', '655', '656'
        ];

        // skip fields containing these terms in $2
        $skipTerms = isset($this->mainConfig->SubjectHeadings->remove) ?
            $this->mainConfig->SubjectHeadings->remove->toArray() : [];

        $skipThisField = function ($field) use ($skipTerms) {
            $subField = $field->getSubField('2');
            return !($subField && in_array($subField->getData(), $skipTerms));
        };

        // This is all the collected data:
        $retval = [];

        // Try each MARC field one at a time:
        foreach ($fields as $field) {
            // Do we have any results for the current field?  If not, try the next.
            $results = $this->getMarcRecord()->getFields($field);
            if (!$results) {
                continue;
            }

            // If we got here, we found results -- let's loop through them.
            foreach ($results as $result) {
                // Start an array for holding the chunks of the current heading:
                $current = [];

                // check if this field should be skipped
                if ($skipThisField($result)) {

                    // Get all the chunks and collect them together:
                    $subfields = $result->getSubfields();
                    if ($subfields) {
                        foreach ($subfields as $subfield) {
                            // Numeric subfields are for control purposes and should not
                            // be displayed:
                            if (!is_numeric($subfield->getCode())) {
                                $current[] = $subfield->getData();
                            }
                        }
                        // If we found at least one chunk, add a heading to our result:
                        if (!empty($current)) {
                            $retval[] = $current;
                        }
                    }
                }

                // If we found at least one chunk, add a heading to our result:
                if (!empty($current)) {
                    $retval[] = $current;
                }
            }
        }

        // Remove duplicates and then send back everything we collected:
        return array_map(
            'unserialize', array_unique(array_map('serialize', $retval))
        );
    }

    /**
     * Get specific marc information about topics. Unflexible solution
     * for UBL only implemented.
     *
     * @return array
     * @access protected
     */
    public function getTopics()
    {
        return array_merge($this->getAllSubjectHeadings(), $this->getAllSubjectHeadingsExtended());
    }

    /**
     * Return all barcode of finc marc 983 $a at full marc record.
     *
     * @todo Method seems erroneous. Bugfixin needed.
     *
     * @return     array        List of barcodes.
     * @deprecated
     */
    public function getBarcode()
    {

        $barcodes = [];

        //$driver = ConnectionManager::connectToCatalog();
        $libraryCodes = $this->mainConfig->CustomIndex->LibraryGroup;

        // get barcodes from marc
        $barcodes = $this->getFieldArray('983', ['a']);

        if (!isset($libraryCodes->libraries)) {
            return $barcodes;
        } else {
            if (count($barcodes) > 0) {
                $codes = explode(",", $libraryCodes->libraries);
                $match = [];
                $retval = [];
                foreach ($barcodes as $barcode) {
                    if (preg_match('/^\((.*)\)(.*)$/', trim($barcode), $match));
                    if ( in_array($match[1], $codes) ) {
                        $retval[] = $match[2];
                    }
                } // end foreach
                if (count($retval) > 0 ) {
                    return $retval;
                }
            }
        }
        return [];
    }

    /**
     * Get the catalogue or opus number of a title. Implemented
     * for petrucci music library.
     *
     * @return array
     * @access protected
     */
    protected function getCatalogueNumber()
    {
        return $this->getFieldArray('245', ['b']);
    }

    /**
     * Get an array of content notes.
     *
     * @return array
     * @access protected
     */
    protected function getContentNote()
    {
        return $this->getFieldArray('505', ['t']);
    }

    /**
     * Get dissertation notes for the record.
     *
     * @return array
     * @access protected
     */
    public function getDissertationNote()
    {
        $tmpArray = [];
        $field = $this->getMarcRecord()->getFields('502');

        foreach ($field as $subfield) {

            $subfieldA = $subfield->getSubField('a');
            if ($subfieldA) {
                $tmpArray[] = $subfieldA->getData();
            }

            $subfieldB = $subfield->getSubField('b');
            if ($subfieldB) {
                $tmpArray[] = $subfieldB->getData();
            }

            $subfieldC = $subfield->getSubField('c');
            if ($subfieldC) {
                $tmpArray[] = $subfieldC->getData();
            }

            $subfieldD = $subfield->getSubField('d');
            if ($subfieldD) {
                $tmpArray[] = $subfieldD->getData();
            }
        }
        $retVal = implode(', ', $tmpArray);

        return $retVal;

    }

    /**
     * Get id of related items
     *
     * @return string
     * @access protected
     */
    protected function getRelatedItems()
    {
        return $this->getFirstFieldValue('776', ['z']);
    }

    /**
     * Get RVK classification number with metadata from Marc records.
     *
     * @return array
     * @link https://intern.finc.info/fincproject/issues/599
     */
    public function getRvkWithMetadata()
    {
        $array = [];

        $rvk = $this->getMarcRecord()->getFields('936');
        // if not return void value
        if (!$rvk) {
            return $array;
        } // end if
        foreach ($rvk as $key => $line) {
            // if subfield with rvk exists
            if ($line->getSubfield('a')) {
                // get rvk
                $array[$key]['rvk'] = $line->getSubfield('a')->getData();
                // get rvk nomination
                if ($line->getSubfield('b')) {
                    $array[$key]['name'] = $line->getSubfield('b')->getData();
                }
                if ($record = $line->getSubfields('k')) {
                    // iteration over rvk notation
                    foreach ($record as $field) {
                        $array[$key]['level'][] = $field->getData();
                    }
                } // end if subfield k
            } // end if subfield a
        } // end foreach
        return $array;
    }
}
