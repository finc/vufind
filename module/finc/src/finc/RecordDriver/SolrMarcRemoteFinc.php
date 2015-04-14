<?php
/**
 * finc specific model for MARC records without a fullrecord in Solr. The fullrecord is being
 * retrieved from an external source.
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
 * finc specific model for MARC records without a fullrecord in Solr. The fullrecord is being
 * retrieved from an external source.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcRemoteFinc extends SolrMarcRemote
{

    /**
     * pattern to identify bsz
     */
    const BSZ_PATTERN = '/^(\(DE-576\))(\d+)(\w|)/';

    /**
     * @var string  ISIL of this instance's library
     */
    protected $isil = '';

    /**
     * @var array   Array of ISILs set in the LibraryGroup section in config.ini.
     */
    protected $libraryGroup = array();

    /**
     * @var string|null
     * @link https://intern.finc.info/fincproject/projects/finc-intern/wiki/FincMARC_-_Erweiterung_von_MARC21_f%C3%BCr_finc
     */
    protected $localMarcFieldOfLibrary = null;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $mainConfig     VuFind main configuration (omit for
     * built-in defaults)
     * @param \Zend\Config\Config $recordConfig   Record-specific configuration file
     * (omit to use $mainConfig as $recordConfig)
     * @param \Zend\Config\Config $searchSettings Search-specific configuration file
     */
    public function __construct($mainConfig = null, $recordConfig = null,
                                $searchSettings = null
    )
    {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        if (isset($mainConfig->InstitutionInfo->isil)) {
            $this->isil = $this->mainConfig->InstitutionInfo->isil;
        } else {
            $this->debug('InstitutionInfo setting is missing.');
        }

        if (isset($mainConfig->LibraryGroup->libraries)) {
            $this->libraryGroup = explode(',' , $this->mainConfig->LibraryGroup->libraries);
        } else {
            $this->debug('LibraryGroup setting is missing.');
        }

        if (isset($this->mainConfig->CustomSite->namespace)) {
            // map for marc fields
            $map = [
                'che' => '971',
                'hgb' => '979',
                'hfbk' => '978',
                'hfm' => '977',
                'hmt' => '970',
                'htw' => '973',
                'htwk' => '974',
                'tuf' => '972',
                'ubl' => '969',
                'zit' => '976',
                'zwi' => '975',
            ];
            $this->localMarcFieldOfLibrary =
                isset($map[$this->mainConfig->CustomSite->namespace]) ?
                    $map[$this->mainConfig->CustomSite->namespace] : null;
        } else {
            $this->debug('Namespace setting for localMarcField is missing.');
        }
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

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $retVal = array();

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = array(
            '856' => array('u'),   // Standard URL
            '555' => array('a')         // Cumulative index/finding aids
        );

        foreach ($fieldsToCheck as $field => $subfields) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {

                    $isil = $url->getSubfield('9');

                    $isISIL = false;

                    if($isil) {
                        $isil = $isil->getData();
                        if(preg_match('/'.$this->isil.'.*/', $isil)) {
                            $isISIL = true;
                        }
                    } else {
                        $isISIL = true;
                    }

                    if($isISIL) {

                        // Is there an address in the current field?
                        $address = $url->getSubfield('u');
                        if ($address) {
                            $address = $address->getData();

                            $tmpArr = array();
                            // Is there a description?  If not, just use the URL itself.
                            foreach (array('y', '3', 'z', 'x') as $current) {
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
                            if (!in_array(array('url' => $address, 'desc' => $desc), $retVal)) {
                                $retVal[] = array('url' => $address, 'desc' => $desc);
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
     * @link https://intern.finc.info/issues/2639
     */
    public function getLocalCallnumber()
    {
        $array = array();

        if (count($this->libraryGroup) > 0 && isset($this->fields['itemdata']))
        {
            $itemdata = json_decode($this->fields['itemdata'], true);
            if (count($itemdata) > 0) {
                // error_log('Test: '. print_r($this->fields['itemdata'], true));
                $i = 0;
                foreach ($this->libraryGroup as $isil) {
                    if (isset($itemdata[$isil])) {
                        foreach ($itemdata[$isil] as $val) {
                            $array[$i]['barcode'] = '(' . $isil . ')' . $val['bc'];
                            $array[$i]['callnumber'] = '(' . $isil . ')' . $val['cn'];
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
     */
    protected function getLocalCallnumbersByLibrary()
    {
        $array = array();
        $callnumbers = array();

        if (count($this->libraryGroup) > 0 && isset($this->fields['itemdata']))
        {
            $itemdata = json_decode($this->fields['itemdata'], true);
            if (count($itemdata) > 0) {
                $i = 0;
                foreach ($this->libraryGroup as $isil) {
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
        $retval = array();
        $arrSignatur = $this->getFieldArray($this->localMarcFieldOfLibrary, array('i'));

        foreach ($arrSignatur as $signatur) {
            foreach ($this->libraryGroup as $code) {
                if (0 < preg_match('/^\('.$code.'\)/', $signatur)) {

                    $retval[] = preg_replace( '/^\('.$code.'\)/','', $signatur);
                }
            }
        }
        return $retval;
    }

    /**
     * Get an array of supplements and special issue entry.
     *
     * @link http://www.loc.gov/marc/bibliographic/bd770.html
     * @return array
     * @access protected
     */
    protected function getSupplements()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        //return $this->_getFieldArray('770', array('i','t')); // has been originally 'd','h','n','x' but only 'i' and 't' for ubl requested;
        $array = array();
        $supplement = $this->marcRecord->getFields('770');
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
     * @link https://intern.finc.info/fincproject/issues/1442
     */
    protected function getIndexOfGermanPrints()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        // define a false indicator
        $lookfor_indicator = '8';
        $retval = array();

        $fields = $this->marcRecord->getFields('024');
        if (!$fields) {
            return null;
        }
        foreach ($fields as $field) {
            // ->getIndicator(position)
            $subjectrow = $field->getIndicator('1');
            if ($subjectrow == $lookfor_indicator) {
                if ($subfield = $field->getSubfield('a')){
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
        return $this->getFieldArray('590', array('b'));
    }

    /**
     * Get the ISSN from a record.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/fincproject/issues/969 description
     */
    protected function getISSN()
    {
        return $this->getFieldArray('022', array('a'));
    }

    /**
     * Get the ISSN from a the parallel title of a record.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/fincproject/issues/969 description
     */
    protected function getISSNsParallelTitles()
    {
        return $this->getFieldArray('029', array('a'));
    }

    /**
     * Get an array of information about Journal holdings realised for the
     * special needs of University library of Chemnitz. MAB fields 720.
     *
     * @return array
     * @access public
     * @link https://intern.finc.info/fincproject/issues/338
     */
    public function getJournalHoldings()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $retval = array();
        $match = array();

        // Get ID and connect to catalog
        //$catalog = ConnectionManager::connectToCatalog();
        //$terms = $catalog->getConfig('OrderJournalTerms');

        $fields = $this->marcRecord->getFields('971');
        if (!$fields) {
            return array();
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
     * @link https://intern.finc.info/issues/1302
     */
    protected function getLocalAccessNumber()
    {
        if (null != $this->localMarcFieldOfLibrary) {
            return $this->getFieldArray($this->localMarcFieldOfLibrary, array('o'));
        }
        return array();
    }

    /**
     * Get all local class subjects. First realization for HGB.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/issues/2626
     */
    protected function getLocalClassSubjects()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $array = array();
        $classsubjects = $this->marcRecord->getFields('979');
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
        if (null != $this->localMarcFieldOfLibrary) {
            if (count($localformat = $this->getFieldArray($this->localMarcFieldOfLibrary, array('c'))) > 0) {
                foreach ($localformat as &$line) {
                    if ($line != "") {
                        $line = trim('local_format_' . strtolower($line));
                    }
                }
                unset($line);
                return $localformat;
            }
        }
        return array();
    }

    /**
     * Return a local notice via an consortial defined field with subfield $k.
     * Marc field depends on library e.g. 970 for HMT or 972 for TUBAF.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/fincproject/issues/1308
     */
    protected function getLocalNotice()
    {
        if (null != $this->localMarcFieldOfLibrary) {
            return $this->getFieldArray($this->localMarcFieldOfLibrary, array('k'));
        }
        return array();
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

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $retval = array();

        $fields = $this->marcRecord->getFields('937');
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
        return $this->getFirstFieldValue('971', array('l'));
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
        return $this->getFieldArray('590', array('a'));
    }

    /**
     * Get specific marc information about parallel editions. Unflexible solution
     * for HMT only implemented.
     *
     * @todo        more flexible implementation
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/issues/4327
     */
    protected function getParallelEditions()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $array = array();
        $fields = array('775');
        $i = 0;

        foreach ($fields as $field) {

            $related = $this->marcRecord->getFields($field);
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
     * @todo        use HttpService for URL query
     *
     * @return string
     * @access protected
     */
    public function getPrice()
    {
        $currency = $this->getFirstFieldValue('365', array('c'));
        $price = $this->getFirstFieldValue('365', array('b'));
        if (!empty($currency) && !empty($price) ) {
            // if possible convert it in euro
            if (is_array($converted =
                json_decode(str_replace(
                    array('lhs','rhs','error','icc'),
                    array('"lhs"','"rhs"','"error"','"icc"'),
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
        return $this->getFieldArray('561', array('a'));
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
        if (null != $this->localMarcFieldOfLibrary) {
            if ( $this->getFirstFieldValue($this->localMarcFieldOfLibrary, array('m')) == 'e') {
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
     * @link http://www.loc.gov/marc/bibliographic/bd830.html
     * @link https://intern.finc.info/fincproject/issues/457
     */
    protected function getSeriesWithVolume()
    {
        return $this->getFieldArray('830', array('a','v'), false);
    }

    /**
     * Get local classification of UDK.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/fincproject/issues/1135
     */
    protected function getUDKs()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $array = array();
        if (null != $this->localMarcFieldOfLibrary) {

            $udk = $this->marcRecord->getFields($this->localMarcFieldOfLibrary);
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
     * Get addional entries for personal names.
     *
     * @return array
     * @access protected
     * @link http://www.loc.gov/marc/bibliographic/bd700.html
     */
    protected function getAdditionalAuthors()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        // result array to return
        $retval = array();

        $results = $this->marcRecord->getFields('700');
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
     * @link https://intern.finc.info/fincproject/issues/1315
     */
    protected function getAdditionals()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        $array = array();
        $fields = array('770','775','776');
        $i = 0;

        foreach ($fields as $field) {

            $related = $this->marcRecord->getFields($field);
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
                                    $array[$i]['record_id'] = $matches[2].$matches[3];
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
     * Special method to extracting the data of the marc21 field 689 of the
     * the bsz heading subjects chains.
     *
     * @return array
     * @access protected
     */
    protected function getAllSubjectHeadingsExtended()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        // define a false indicator
        $firstindicator = 'x';
        $retval = array();

        $fields = $this->marcRecord->getFields('689');
        if (!$fields) {
            return null;
        }
        foreach ($fields as $field) {
            $subjectrow = $field->getIndicator('1');
            if ($subjectrow != $firstindicator) {
                $key = (isset($key) ? $key +1 : 0);
                $firstindicator = $subjectrow;
            }
            if ($subfield = $field->getSubfield('a')){
                $retval[$key]['subject'][] = $subfield->getData();
            }
            if ($subfield = $field->getSubfield('t')){
                $retval[$key]['subject'][] = $subfield->getData();
            }
            if ($subfield = $field->getSubfield('9')){
                $retval[$key]['subsubject'] = $subfield->getData();
            }
        }
        return  $retval;
    }

    /**
     * Return all barcode of finc marc 983 $a at full marc record.
     *
     * @param  string       Prefixes of library seals.
     *
     * @return array        List of barcodes.
     * @access protected
     */
    protected function getBarcode()
    {

        $barcodes = array();

        //$driver = ConnectionManager::connectToCatalog();
        //$libraryCodes = $driver->getIniFieldAsArray('searches','LibraryGroup');
        $libraryCodes = $this->searchesConfig->LibrarayGroup;

        // get barcodes from marc
        $barcodes = $this->getFieldArray('983', array('a'));

        if (!isset($libraryCodes->libraries)) {
            return $barcodes;
        } else {
            if (count($barcodes) > 0) {
                $codes = explode(",", $libraryCodes->libraries);
                $match = array();
                $retval = array();
                foreach($barcodes as $barcode) {
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
        return array();
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
        return $this->getFieldArray('245', array('b'));
    }

    /**
     * Get an array of content notes.
     *
     * @return array
     * @access protected
     */
    protected function getContentNote()
    {
        return $this->getFieldArray('505', array('t'));
    }

    /**
     * Get dissertation notes for the record.
     *
     * @return array
     * @access protected
     */
    protected function getDissertationNote()
    {
        return $this->getFieldArray('502', array('a'));
    }

    /**
     * Get id of related items
     *
     * @return string
     * @access protected
     */
    protected function getRelatedItems()
    {
        return $this->getFirstFieldValue('776', array('z'));
    }
}
