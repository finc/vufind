<?php

/*
 * Copyright (C) 2019 Bibliotheksservice Zentrum Baden-Württemberg, Konstanz
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Bsz\ILL;

use Bsz\RecordDriver\SolrMarc;
use Bsz\ILL\Holding;
/**
 * Class to determing availability via inter-library loan
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Logic {

    const FORMAT_EJOUNAL = 'Ejournal';
    const FORMAT_JOURNAL = 'Eournal';
    const FORMAT_EBOOK = 'Ebook';
    const FORMAT_BOOK = 'Book';
    const FORMAT_MONOSERIAL = 'MonoSerial';
    const FORMAT_ARTICLE = 'Article';
    const FORMAT_UNDEFINED = 'Undefined';

    protected $config;
    protected $driver;
    protected $format;
    protected $holding;
    protected $localIsils;
    protected $ppns = [];
    protected $messages = [];

    /**
     *
     * @param \Zend\Config\Config $config
     * @param Holding $holding
     * @param type $isils
     */

    public function __construct(\Zend\Config\Config $config, Holding $holding, $isils = [])
    {
        $this->config = $config;
        $this->holding = $holding;
        $this->localIsils = $isils;
    }

    /**
     * Each instance of this class can be used for many RecordDriver instances,
     * but not at the same time.
     *
     * @param SolrMarc $driver
     */
    public function setDriver(SolrMarc $driver)
    {
        $this->driver = $driver;
        $this->format = $this->getFormat();
        $this->ppns = [];
    }


    /**
     * Checks if the item can be ordered via ILL
     *
     * @return boolean
     */

    public function isAvailable()
    {
        $status = [];

        /*
         * Take care of the negate operator here!
         */
        $status[] = !$this->isHebis8();
        $status[] = !$this->isFree();
        $status[] = !$this->isSerialOrCollection();
        $status[] = !$this->isAtCurrentLibrary();
        $status[] = $this->checkFormat();
        $status[] = $this->checkIndicator();
        /*
         * No ILL allowed if one value is false
         */
        return in_array(false, $status);

    }

    /**
     * Map the driver formats to more simple ILL formats
     *
     * @return string
     */

    private function getFormat()
    {

        $format = static::FORMAT_UNDEFINED;

        if ($this->driver->isElectronic()) {
            if ($this->driver->isJournal() || $this->driver->isNewspaper()) {
                $format = static::FORMAT_EJOUNAL;
            } elseif ($this->driver->isEBook()) {
                $format = static::FORMAT_EBOOK;
            }
        } else {
            // Print items
            if ($this->driver->isMonographicSerial()) {
                $format = static::FORMAT_MONOSERIAL;
            } elseif ($this->driver->isBook()) {
                $format = static::FORMAT_BOOK;
            } elseif ($this->driver->isArticle()) {
                $format = static::FORMAT_ARTICLE;
            } elseif ($this->driver->isJournal() || 
                $this->driver->isNewsPaper()
            ) {
                $format = static::FORMAT_JOURNAL;
            }
        }
       return $format;
    }

    /**
     * Query webservice to get SWB hits with the same
     * <ul>
     * <li>ISSN or ISBN (preferred)</li>
     * <li>Title, author and year (optional)</li>
     * </ul>
     * Found PPNs are added to ppns array and can be accessed by other methods.
     *
     * @return boolean
     */
    protected function queryWebservice()
    {

        // set up query params
        $this->holding->setNetwork('DE-576');
        $isbn = $this->driver->getCleanISBN();
        $years = $this->driver->getPublicationDates();
        $zdb = $this->driver->tryMethod('getZdbId');
        $year = array_shift($years);

        if ($this->driver->isArticle() || $this->driver->isJournal()
                || $this->driver->isNewspaper()
            ) {
            // prefer ZDB ID
            if (!empty($zdb)) {
                $this->holding->setZdbId($zdb);
            } else {
                $this->holding->setIsxns($this->driver->getCleanISSN());
            }
            // use ISSN and year
        } elseif (!empty($isbn)) {
            // use ISBN and year
            $this->holding->setIsxns($isbn)
                            ->setYear($year);
        } else {
            // use title and author and year
            $this->holding->setTitle($this->driver->getTitle())
                          ->setAuthor($this->driver->getPrimaryAuthor())
                          ->setYear($year);
        }
        // check query and fire
        if ($this->holding->checkQuery()) {
            $result = $this->holding->query();
            // check if any ppn is available locally
            if (isset($result['holdings'])) {
                // search for local available PPNs
                foreach ($result['holdings'] as $ppn => $holding) {
                    foreach ($holding as $entry) {
                        if (isset($entry['isil']) && in_array($entry['isil'], $this->localIsils)) {
                            // save PPN
                            $this->ppns[] = '(DE-627)'.$ppn;
                            $this->libraries[] = $entry['isil'];
                        }

                    }
                }
            }
            // if no locally available ppn found, just take the first one
            if (count($this->ppns) < 1 && isset($result['holdings'])) {
                reset($result['holdings']);
                $this->ppns[] = '(DE-627)'.key($result['holdings']);
            }

        }

        // check if any of the isils from webservic matches local isils
        if (is_array($this->libraries) && count($this->libraries) > 0) {
            return true;
        }
        return false;
    }

        /**
     * Quer< solr for parallel Editions available at local libraries
     * Save the found PPNs in global array
     *
     * @return boolean
     */
    protected function hasParallelEditions()
    {
        $ppns = [];
        $related = $this->driver->tryMethod('getRelatedEditions');
        $hasParallel = false;

        foreach ($related as $rel) {
            $ppns[] = $rel['id'];
        }
        $parallel = [];
        if (count($ppns) > 0) {
            $parallel = $this->holding->getParallelEditions($ppns, $this->client->getIsilAvailability());
            // check the found records for local available isils
            $isils = [];
            foreach ($parallel->getResults() as $record) {
                $f924 = $record->getField924(true);
                $recordIsils = array_keys($f924);
                $isils = array_merge($isils, $recordIsils);
            }
            foreach ($isils as $isil) {
                if (in_array($isil, $this->localIsils)) {
                    $hasParallel = true;
                    $this->ppns[] = $record->getUniqueId();
                }
            }
        }
        return $hasParallel;
    }

    /**
     * Determin if an item is available locally. Checks for
     * * 924 entries
     * * Parallel editions in SWB
     * * Similar results from SWB (if network != SWB)     *
     *
     * @return boolean
     */

    protected function isAtCurrentLibrary()
    {
        $status = false;
        $network = $this->driver->getNetwork();

        if (count($this->ppns) == 0) {
            // if we have local holdings, item can't be ordered
            if ($this->driver->hasLocalHoldings()) {
                $status = true;
            } elseif ($network == 'SWB'  && $this->hasParallelEditions()
            ) {
                $status = true;
            } elseif ($network !== 'SWB' && $this->queryWebservice()
            ) {
                $status = true;
            }
        }
        if ($this->driver->hasLocalHoldings() && $network == 'ZDB') {
            $this->queryWebservice();
        }
        return $status;

    }

    /**
     * Check if the item should have an ill button
     * @return boolean
     */
//    public function isAvailableForInterlending()
//    {
//        $ppn = $this->driver->getPPN();
//        $network = $this->driver->getNetwork();
//        // first, the special cases
//        if (($network == 'HEBIS' && preg_match('/^8/', $ppn))) {
//            // HEBIS items with 8 at the first position are freely available
//            return false;
//        } elseif ($this->driver->isFree()) {
//            return false;
//        } elseif (($this->driver->isArticle()
//            // printed journals, articles, newspapers - show hint
//            || $this->driver->isJournal()
//            || $this->driver->isNewspaper()) && !$this->driver->isElectronic()
//        ) {
//            return true;
//        } else if ($this->driver->isEBook()) {
//            return false;
//        } else if ($this->driver->isJournal() && $this->driver->isElectronic() && ($network == 'SWB' || $network == 'ZDB')) {
//            return $this->checkIllIndicator(['e', 'b', ]);
//        } elseif ($this->driver->isMonographicSerial() || $this->driver->isEBook()) {
//            return false;
//        }
//
//        // if we arrived here, item is not available at current library, is no
//        // serial and no collection, it is available
//
//        if (!$this->isAtCurrentLibrary(true)
//                && !$this->driver->isSerial()
//                && !$this->driver->isCollection()) {
//            return true;
//        }
//        return false;
//    }

    /**
     * Check the ILL indicator - invalid or empty indicators are ignored
     *
     * @return boolean
     */
    protected function checkIndicator()
    {

        $f924 = $this->driver->tryMethod('getField924');
        $section = $this->config->get($this->format);
        $allowedCodes = $section->get('indicator')->toArray();

        foreach ($f924 as $field) {
           if (isset($field['d']) && in_array($field['d'], $allowedCodes)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether record is from HEBIS and it's ID begins with 8
     *
     * @return boolean
     */

    protected function isHebis8()
    {
        $network = $this->driver->getNetwork();
        $ppn = $this->driver->getPPN();
        $this->messages[] = 'ILL::cond_hebis_8';
        return ($network == 'HEBIS' && preg_match('/^8/', $ppn));

    }

    /**
     * Determine is record is a serial or a collection
     * 
     * @return boolean
     */
    
    protected function isSerialOrCollection()
    {
        if ($this->driver->isSerial() || $this->driver->isCollection()) {
            $this->messages[] = 'ILL::cond_serial_collection';
            return true;
        }
        return false;
    }
    /**
     * Check if format is enabled for inter-library loan and if it's enabled for
     * the current network
     * 
     * @return boolean
     */
    
    protected function checkFormat()
    {
        $section = $this->config->get($this->format);
        $network = $this->driver->getNetwork();
        
        if (in_array($network, $section->get('excludeNetwork')->toArray())) {
            $this->messages[] = 'ILL::cond_format_network';
            return false;
        } elseif (!$section->get('enabled')) {
            $this->messages[] = 'ILL::cond_format';
            return false;
        }        
        return true;
    }
   
    /**
     * Check if the record is available for free
     * 
     * @return boolean
     */
    
    protected function isFree() 
    {
        if ($this->driver->isFree()) {
            $this->messages[] = 'ILL::cond_free';
            return true;
        }
        return false;
    }
    
    
    /**
     * Get all messages that occurrec during processing. Messages are trans-
     * lation keys and should be translated afterwards. 
     *      * 
     * @return array
     */
    
    public function getMessages()
    {
        return $this->messages;
    }
    
    /**
     * Access the PPNs found via web service
     * 
     * @return array
     */
    public function getPPNs() 
    {
        return array_unique($this->ppns);
    }
    



}
