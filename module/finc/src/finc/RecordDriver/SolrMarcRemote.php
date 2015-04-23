<?php
/**
 * Model for MARC records without a fullrecord in Solr. The fullrecord is being
 * retrieved from an external source.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2014.
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
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace finc\RecordDriver;
use \Zend\Log\LoggerInterface;

/**
 * Model for MARC records without a fullrecord in Solr. The fullrecord is being
 * retrieved from an external source.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcRemote extends SolrMarc
{
    /**
     * Logger (or false for none)
     *
     * @var LoggerInterface|bool
     */
    protected $logger = false;

    /**
     * MARC record
     *
     * @var \File_MARC_Record
     */
    protected $marcRecord;

    /**
     * holds the URI-Pattern of the service that returns the marc binary blob by id
     *
     * @var string
     */
    protected $uriPattern = '';
    
    /**
     * holds config.ini data
     * 
     * @var array
     */
    protected $mainConfig;

    /**
     * holds searches.ini data
     *
     * @var array
     */
    protected $searchesConfig;

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
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        if (!isset($recordConfig->General)) {
            throw new \Exception('SolrMarcRemote General settings missing.');
        }

        // get config values for remote fullrecord service
        if (! $recordConfig->General->get('baseUrl')) {
            throw new \Exception('SolrMarcRemote baseUrl-setting missing.');
        } else {
            $this->uriPattern = $recordConfig->General->get('baseUrl');
        }
        
        $this->mainConfig = $mainConfig;
        $this->searchesConfig = $searchSettings;
    }

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object.  In this case, $data is a Solr record
     * array containing MARC data in the 'fullrecord' field.
     *
     * @throws \Exception
     * @throws \File_MARC_Exception
     * @return void
     */
    public function setRawData($data)
    {
        // Don't call the parent's set method as this would require the fullrecord in the Solr-Data
        // Instead perform basic assignment of data to fields
        $this->fields = $data;
    }

    /**
     * Retrieves the full Marcrecord from a remote service defined by uriPattern
     *
     * @params String $id - this record's unique identifier
     * @throws \Exception
     *
     * @return marc binary blob
     */
    private function retrieveFullrecord($id)
    {

        if (empty($id)) {
            throw new \Exception('empty id given');
        }

        if (!$this->uriPattern) {
            throw new \Exception('no Marc-Server configured');
        }

        $parsed_url = parse_url($this->uriPattern);

        if (false === isset($config['options']) || false === is_array($config['options'])) {
            $config['options']['timeout'] = 0.1;
        }

        $options = [$parsed_url['scheme'] => $config['options']];
        $streamContext = stream_context_create($options);

        $url = sprintf($this->uriPattern, $id);

        $loopCount = 0;

        for ($loopCount=0; $loopCount<10;$loopCount++) {
            $content = @file_get_contents($url , false, $streamContext);

            if (false === $content) {
                $this->debug('Unable to fetch marc from server ' . $url);
                continue;
            } else if (empty($content)) {
                $this->debug('content is empty, trying again. If this happens very often, try to increase timeout');
                continue;
            }

            return $content;
        }

        $this->debug('tried too many times. aborting at record ' . $id);
        return false;

    }

    /**
     * Load data from remote server
     *
     * @throws \Exception
     * @throws \File_MARC_Exception
     */
    protected function getRemoteData() {

        // handle availability of fullrecord
        if (isset($this->fields['fullrecord'])) {
            // standard Vufind2-behaviour

            // also process the MARC record:
            $marc = trim($this->fields['fullrecord']);

        } else {
            // fallback: retrieve fullrecord from external source

            if (! isset($this->fields['id'])) {
                throw new \File_MARC_Exception('No unique id given for fullrecord retrieval');
            }

            $marc = $this->retrieveFullrecord($this->fields['id']);

        }

        if (isset($marc)) {
            // continue with standard Vufind2-behaviour if marcrecord is present

            // check if we are dealing with MARCXML
            $xmlHead = '<?xml version';
            if (strcasecmp(substr($marc, 0, strlen($xmlHead)), $xmlHead) === 0) {
                $marc = new \File_MARCXML($marc, \File_MARCXML::SOURCE_STRING);
            } else {
                // When indexing over HTTP, SolrMarc may use entities instead of certain
                // control characters; we should normalize these:
                $marc = str_replace(
                    ['#29;', '#30;', '#31;'], ["\x1D", "\x1E", "\x1F"], $marc
                );
                $marc = new \File_MARC($marc, \File_MARC::SOURCE_STRING);
            }

            $this->marcRecord = $marc->next();
            if (!$this->marcRecord) {
                throw new \File_MARC_Exception('Cannot Process MARC Record');
            }
        } else {
            // no marcrecord was found

            throw new \Exception('no Marc was found neither on the marc server nor in the solr-record for id ' . $this->fields['id']);
        }
    }

    /**
     * Get the field-value identified by $string
     *
     * @param String field-name
     *
     * @return String
     */
    public function getILSIdentifier($string)
    {
        return (isset($this->fields[$string]) ? $this->fields[$string] : '');
    }

    /**
     * Set the logger
     *
     * @param LoggerInterface $logger Logger to use.
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log a debug message.
     *
     * @param string $msg Message to log.
     *
     * @return void
     */
    protected function debug($msg)
    {
        if ($this->logger) {
            $this->logger->debug(get_class($this) . ": $msg");
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

        return parent::getURLs();
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

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getAllSubjectHeadings();
    }

    /**
     * Get the bibliographic level of the current record.
     *
     * @return string
     */
    public function getBibliographicLevel()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getBibliographicLevel();
    }

    /**
     * Return an array of all values extracted from the specified field/subfield
     * combination.  If multiple subfields are specified and $concat is true, they
     * will be concatenated together in the order listed -- each entry in the array
     * will correspond with a single MARC field.  If $concat is false, the return
     * array will contain separate entries for separate subfields.
     *
     * @param string $field     The MARC field number to read
     * @param array  $subfields The MARC subfield codes to read
     * @param bool   $concat    Should we concatenate subfields?
     *
     * @return array
     */
    protected function getFieldArray($field, $subfields = null, $concat = true)
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getFieldArray($field, $subfields, $concat);
    }

    /**
     * Get the item's publication information
     *
     * @param string $subfield The subfield to retrieve ('a' = location, 'c' = date)
     *
     * @return array
     */
    protected function getPublicationInfo($subfield = 'a')
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getPublicationInfo($subfield);
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

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getSeriesFromMARC($fieldInfo);
    }

    /**
     * Get an array of lines from the table of contents.
     *
     * @return array
     */
    public function getTOC()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getTOC();
    }

    /**
     * Get hierarchical place names (MARC field 752)
     *
     * returns an array of formatted hierarchical place names, consisting of all
     * alpha-subfields, concatenated for display
     *
     * @return array
     */
    public function getHierarchicalPlaceNames()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getHierarchicalPlaceNames();
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Format:
     * array(
     *        array(
     *               'title' => label_for_title
     *               'value' => link_name
     *               'link'  => link_URI
     *        ),
     *        ...
     * )
     *
     * @return null|array
     */
    public function getAllRecordLinks()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getAllRecordLinks();
    }

    /**
     * Get Status/Holdings Information from the internally stored MARC Record
     * (support method used by the NoILS driver).
     *
     * @param array $field The MARC Field to retrieve
     * @param array $data  A keyed array of data to retrieve from subfields
     *
     * @return array
     */
    public function getFormattedMarcDetails($field, $data)
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getFormattedMarcDetails($field, $data);
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Get access to the raw File_MARC object.
     *
     * @return File_MARCBASE
     */
    public function getMarcRecord()
    {

        if(empty($this->marcRecord)) {
            $this->getRemoteData();
        }

        return parent::getMarcRecord();
    }
}
