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
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>, Ulf Seltmann <seltmann@ub.uni-leipzig.de>
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
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>, Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcLite extends \VuFind\RecordDriver\SolrMarc implements \Zend\Log\LoggerAwareInterface
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

        if (!isset($mainConfig->Index)) {
            throw new \Exception('index setting missing.');
        }

        // get config values for remote fullrecord service
        if (! $mainConfig->Index->get('blob_server')) {
            throw new \Exception('blob_server-setting missing.');
        } else {
            $this->uriPattern = $mainConfig->Index->get('blob_server');
        }
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

        // handle availability of fullrecord
        if (isset($data['fullrecord'])) {
            // standard Vufind2-behaviour

            // also process the MARC record:
            $marc = trim($data['fullrecord']);

        } else {
            // fallback: retrieve fullrecord from external source

            if (! isset($data['id'])) {
                throw new \File_MARC_Exception('No unique id given for fullrecord retrieval');
            }

            $marc = $this->retrieveFullrecord($data['id']);

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
                    array('#29;', '#30;', '#31;'), array("\x1D", "\x1E", "\x1F"), $marc
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

        $options = array($parsed_url['scheme'] => $config['options']);
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
}
