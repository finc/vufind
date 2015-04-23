<?php
/**
 * finc specific model for Solr records based on the stock
 * VuFind\RecordDriver\SolrDefault
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
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
namespace finc\RecordDriver;

/**
 * finc specific model for Solr records based on the stock
 * VuFind\RecordDriver\SolrDefault
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SolrDefault extends \VuFind\RecordDriver\SolrDefault
{

    /**
     * Return the custom index field local_heading if indexExtension is set.
     * If indexExtension is set local_heading_{indexExtension} is returned,
     * if local_heading_{indexExtesion} is empty,
     * local_heading_facet_{indexExtension} is returned.
     *
     * @return array   Containing local_heading_[facet_]{indexExtension} fields.
     * @access public
     */
    public function getLocalHeading() {

        $array = [];

        if (isset($this->mainConfig->Site->indexExtension)) {
            $array = isset($this->fields['local_heading_' . ($this->mainConfig->Site->indexExtension)]) ?
                $this->fields['local_heading_' . ($this->mainConfig->Site->indexExtension)] : [];
            // Use local_heading_facet field if local_heading field delivers no results at first
            if (count($array) == 0) {
                $array = isset($this->fields['local_heading_facet_' . ($this->mainConfig->Site->indexExtension)]) ?
                    $this->fields['local_heading_facet_' . ($this->mainConfig->Site->indexExtension)] : [];
            }
        }
        return $array;
    }

    /**
     * Controller to decide when local format field of a library should be
     * retrieved from marc. Pass through method for PrimoCentral
     *
     * Public method for tuf to display format at search modul.
     *
     * @internal        This method should be dropped or renamed (getStandardFormat())
     *                  as it is only a wrapper for the custom method getFormat() which
     *                  in turn behaves as the stock getFormats() method.
     *
     * @deprecated      No need for this wrapper in custom SolrDefault
     *
     * @return array
     * @access public
     */
    public function getLocalFormat()
    {
        return $this->getFormat();
    }

    /**
     * Get back the standardizied format field of Solr index.
     *
     * @deprecated      Should also be possible to be dropped (@see getLocalFormat())
     *
     * @return array
     */
    public function getFormat()
    {
        return isset($this->fields['format']) ? $this->fields['format'] : [];
    }

    /**
     * Get an array of all the formats associated with the record. If indexExtension
     * is set and generalFormats is disabled in config.ini return the field
     * format_{indexExtension}, format otherwise.
     *
     * @return array        Array with formats associated with the record.
     */
    public function getFormats()
    {
        // check if general 'format' index field should be used
        $isGeneralFormat = (isset($this->mainConfig->Site->generalFormats)
            && true == $this->mainConfig->Site->generalFormats)
            ? true : false;
        // check if there's an extension defined for the library depended format
        // index field
        $isExtension = (isset($this->mainConfig->Site->indexExtension))
            ? true : false;

        $format = (false === $isGeneralFormat && true === $isExtension)
            ?  'format_' . $this->mainConfig->Site->indexExtension : 'format' ;

        return isset($this->fields[$format]) ? $this->fields[$format] : [];
    }


    /**
     * Get the formats for displaying the icons. Renders the format information to
     * a specific css class.
     * There are two setups possible. If combinedIcons sets to true at config.ini
     * all format values will be concatenated to one string; if it's false only
     * the first vlaue will be taken.
     *
     * @internal            Should be moved out of RecordDriver (Controller/View?)
     * @todo                Should be moved out of RecordDriver (Controller/View?)
     *
     * @return array
     */
/*    protected function getFormatIcon()
    {
        global $configArray;

        $format = $this->getFormats();
        // check which method to build the css class is chosen
        if (isset($this->mainConfig->Site->combinedIcons) && true == $this->mainConfig->Site->combinedIcons) {
            // sort it
            sort($format, SORT_LOCALE_STRING);
            return strtolower(implode('', $format));
            // otherwise take the first format
        } else {
            if (isset($this->fields['multipart_set'])) {
                switch ($this->fields['multipart_set']) {
                    case 'a': return 'sets';
                    case 'b': break; //return 'part-related';
                    case 'c': break; //return 'part-not-related';
                }
            }
            //echo "<pre>"; print_r($format); echo "</pre>";
            return $format[0];
        }
    }*/

    /**
     * Get the source id of the record.
     *
     * @return string
     * @access public
     */
    public function getSourceID()
    {
        return isset($this->fields['source_id']) ?
            $this->fields['source_id'] : '';
    }

    /**
     * Get the GND of an author.
     *
     * @return array
     */
    public function getAuthorId()
    {
        return isset($this->fields['author_id']) ?
            $this->fields['author_id'] : [];
    }

    /**
     * Combined fields of author data.
     *
     * @todo    Check whether static call of getCorporateAuthor is necessary
     *
     * @return array
     * @link https://intern.finc.info/issues/1866
     */
    public function getCombinedAuthors()
    {
        $retval = [];

        if ($this->getPrimaryAuthor() != '') {
            $original = '';
            if ($this->getPrimaryAuthorOrig() != '') {
                $original = $this->getPrimaryAuthorOrig();
            }
            $retval[] = ($original == '') ? $this->getPrimaryAuthor()
                : $this->getPrimaryAuthor() . ' (' . $original .  ')';
        } elseif ( self::getCorporateAuthor() != '' ) {
            $retval[] = self::getCorporateAuthor();
        } elseif (count($this->getSecondaryAuthors()) > 0) {
            foreach ($this->getSecondaryAuthors() as $val) {
                $retval[] = $val;
            }
        } elseif (count($this->getCorporateSecondaryAuthors()) > 0) {
            foreach ($this->getCorporateSecondaryAuthors() as $val) {
                $retval[] = $val;
            }
        }

        return $retval;
    }

    /**
     * Get the original author of the record.
     *
     * @return string
     */
    public function getPrimaryAuthorOrig()
    {
        return isset($this->fields['author_orig']) ?
            $this->_filterAuthorDates($this->fields['author_orig']) : '';
    }

    /**
     * Get the main author of the record.
     *
     * @return string
     * @deprecated
     */
    public function getPrimaryAuthorRaw()
    {
        return isset($this->fields['author']) ?
            $this->_removeAuthorDates($this->fields['author']) : '';
    }

    /**
     * Get the main corporate author (if any) for the record.
     *
     * @return string
     * @access public
     */
    public function getCorporateAuthor()
    {
        return isset($this->fields['author_corp']) ?
            $this->fields['author_corp'] : '';
    }

    /**
     * Get the secondary corporate authors (if any) for the record.
     *
     * @return array
     */
    public function getCorporateSecondaryAuthors()
    {
        return isset($this->fields['author_corp2']) ?
            $this->fields['author_corp2'] : [];
    }

    /**
     * Get an array of all ISMNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISMNs()
    {
        return isset($this->fields['ismn']) && is_array($this->fields['ismn']) ?
            $this->fields['ismn'] : [];
    }

    /**
     * Get the eISSN from a record.
     *
     * @return array
     */
    public function getEISSNs()
    {
        return [];
    }

    /**
     * Get an array of newer titles for the record.
     *
     * @return array
     */
    public function getNewTitles()
    {
        return isset($this->fields['title_new']) ?
            $this->fields['title_new'] : [];
    }

    /**
     * After giving a record ids as e.g. ppn of the BSZ check if a record exists.
     * This method can be used to indicate a direct link than to form a general
     * look for query.
     *
     * @todo                    1. Check if this method is still needed
     * @todo                    2. Refactor Solr-Query to be compatible with VuFind2
     *
     * @param array $rids Array of record ids to test.
     *
     * @return int mixed  If success return at least one finc id otherwise null.
     * @deprecated        Not used.
     */
    protected function addFincIDToRecord ( $array ) {
/*
        // record ids
        $rids = array();
        // return array
        $retval = array();

        // check if array contain record_ids and collect it as an array to
        // use only one solr request for all
        if (isset($array) && is_array($array)) {
            foreach ($array as $line) {
                if (isset($line['record_id'])) {
                    $rids[] = $line['record_id'];
                }
            }
        }
        // solr call
        // call index
        $index = $this->getIndexEngine();

        // build query and accept limit of solr
        $limit = $index->getBooleanClauseLimit();
        if (count($rids) > $limit) {
            $rids = array_slice($rids, 0, $limit);
            $retVal = array();
        }
        // build the query:
        if (count($rids) == 1) {
            // single query:
            $query = "(record_id:". $rids[0] .")";
        } elseif (count($rids) > 1) {
            // multi query:
            $query = 'record_id:(' . implode(' OR ', $rids) . ')';
        } else {
            return $array;
        }
        // set hidden filter to limited the range
        $this->setHiddenFilters();
        // limited search for id and record_id values only
        $result = $index->search($query, null, $this->hiddenFilters, 0, 100, null, '', null, null, 'id, record_id',  HTTP_REQUEST_METHOD_POST , false, false);

        // log to find test data
        // temporary logger
        if (isset($result['response']['numFound'])
            && isset($result['response']['numFound']) != 0) {
        }
        // if error break down
        if (PEAR::isError($result)) {
            return null;
        }
        if (isset($result['response']['docs'])
            && !empty($result['response']['docs'])
        ) {
            foreach( $result['response']['docs'] as $key => $doc) {
                $retval[($doc['record_id'])]=$doc['id'];
            }
        }
        // write back in array
        foreach ($array as &$val) {
            if (isset($val['record_id'])) {
                if (isset($retval[($val['record_id'])])) {
                    $val['id'] = $retval[($val['record_id'])];
                }
            }
        }
        unset($val);
        //echo "<pre>"; print_r($array); echo "</pre>";*/
        return $array;
    }

    /**
     * Get percentage of relevance of a title. First implementaion for TUBAF.
     *
     * @return float        Percentage of Score / Maximum Score rounded by 5.
     * @link https://intern.finc.info/issues/1908
     */
    public function getRelevance() {

        $score = isset($this->fields['score']) ?  $this->fields['score'] : 0;
        $maxScore = isset($this->fields['score_maximum']) ? $this->fields['score_maximum'] : 0;

        if ($score == 0 || $maxScore == 0) {
            return 0;
        }
        return round( ($score / $maxScore) , 5);
    }

    /**
     * Get RVK classifcation number from Solr index.
     *
     * @return string
     */
    public function getRvk() {
        return isset($this->fields['rvk_facet']) ?
            $this->fields['rvk_facet'] : '';
    }

    /**
     * Get special record_id of libero system.
     *
     * @todo    refactor to a more meaningful name?
     *
     * @return string
     */
    public function getRID()
    {
        return isset($this->fields['record_id']) ?
            $this->fields['record_id'] : '';
    }

    /**
     * Get the original title of the record.
     *
     * @return string
     */
    public function getTitleOrig()
    {
        return isset($this->fields['title_orig']) ?
            $this->fields['title_orig'] : '';
    }

    /**
     * Get the GND of topic.
     *
     * @return array
     */
    public function getTopicId()
    {
        return isset($this->fields['topic_id']) ?
            $this->fields['topic_id'] : [];
    }

    /**
     * Get alternatives series titles as array.
     *
     * @return array
     */
    public function getSeriesAlternative()
    {
        if (isset($this->fields['series2']) && !empty($this->fields['series2'])) {
            return $this->fields['series2'];
        }
        return [];
    }

    /**
     * Get alternatives series titles as array.
     *
     * @return array
     */
    public function getSeriesOrig()
    {
        if (isset($this->fields['series_orig']) && !empty($this->fields['series_orig'])) {
            return $this->fields['series_orig'];
        }
        return [];
    }

    /**
     * Filter author data for author year of birth and death
     * to give a better mark up.
     *
     * @param string $authordata
     *
     * @return strings
     */
    private function _filterAuthorDates( $authordata )
    {
        if (preg_match('/^(\s|.*)(\d{4})\s?-?\s?(\d{4})?$/Uu',$authordata, $match)) {
            return (isset($match[3]))
                ? $match[1] .' *'. $match[2] . '-†'. $match[3]
                : $match[1] .' *'. $match[2] . '-';
        }
        return $authordata;
    }

    /**
     * Remove author dates if exists.
     *
     * @param string authordata
     *
     * @return strings
     * @deprecated
     */
    private function _removeAuthorDates( $authordata )
    {
        if (preg_match('/^(\s|.*)\s(fl.\s|d.\s|ca.\s)*\s?(\d{4})\??(\sor\s\d\d?)?\s?(-|–)?\s?(ca.\s|after\s)?(\d{1,4})?(.|,)?$/Uu',$authordata, $match)) {
            return (isset($match[1])) ? $match[1] : $authordata;
        }
        return $authordata;
    }


}
