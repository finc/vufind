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
use Zend\Config\Config;

/**
 * Class to determing availability via inter-library loan
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Logic
{
    const FORMAT_EJOUNAL = 'Ejournal';
    const FORMAT_JOURNAL = 'Journal';
    const FORMAT_EBOOK = 'Ebook';
    const FORMAT_BOOK = 'Book';
    const FORMAT_MONOSERIAL = 'MonoSerial';
    const FORMAT_ARTICLE = 'Article';
    const FORMAT_UNDEFINED = 'Undefined';

    protected $config;

    /**
     * @var SolrMarc
     */
    protected $driver;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var Holding
     */
    protected $holding;
    /**
     * @var array
     */
    protected $localIsils;
    protected $swbppns = [];
    protected $parallelppns = [];
    protected $linklabels = [];
    protected $messages = [];
    protected $libraries = [];
    /**
     * @var array
     */
    protected $status;

    /**
     * @param Config $config
     * @param Holding $holding
     * @param array $isils
     */

    public function __construct(Config $config, $isils = [])
    {
        $this->config = $config;
        $this->localIsils = $isils;
    }

    /**
     * Each instance of this class can be used for many RecordDriver instances,
     * but not at the same time.
     *
     * @param SolrMarc $driver
     */
    public function attachDriver(SolrMarc $driver)
    {
        $this->driver = $driver;
        $this->format = $this->getFormat();
        $this->status = [];
        $this->swbppns = [];
        $this->parallelppns = [];
        $this->linklabels = [];
    }

    /**
     * Map the driver formats to more simple ILL formats
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
     * @param Holding $holding
     */
    public function attachHoldings(Holding $holding)
    {
        $this->holding = $holding;
    }

    /**
     * Checks if the item can be ordered via ILL
     * @return boolean
     */

    public function isAvailable()
    {
        if (empty($this->status)) {
            $this->determineStatus();
        }
        if (in_array(false, $this->status)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Fills the internal status array.
     * @return array
     */
    protected function determineStatus()
    {
        $checks = $this->config->get('Checks')->get('methods');
        $checks = explode(', ', $checks);

        foreach ($checks as $check) {
            $negate = (bool)preg_match('/^!/', $check);

            if ($negate) {
                $check = preg_replace('/^!/', '', $check);
            }

            $method = 'check' . $check;

            if (method_exists($this, $method)) {
                $status = $this->$method();
                if ($negate) {
                    $status = !$status;
                }
                $this->status[$check] = $status;
            }
        }
        return $this->status;
    }

    /**
     * Returns the unique status code
     * TODO: this method shoudl return something from conffiguration.      *
     * @return int
     */
    public function getStatusCode()
    {
        if (empty($this->status)) {
            $this->determineStatus();
        }
        $binary = implode('', $this->status);
        return bindec($binary);
    }

    /**
     * Get all messages that occurre during processing. Messages are trans-
     * lation keys and should be translated afterwards.
     * @return array
     */

    public function getMessages()
    {
        /*
         * TODO there are still some messages set in methods. These should be
         * removed to the configuration. It might be neccessary to split those
         * methods into exactly one task.
         */
        $retval = $this->messages;
        foreach ($this->status as $check => $result) {
            if (!$result && $this->config->get('Messages')->OffsetExists($check)) {
                $retval[] = $this->config->get('Messages')->get($check);
            }
        }
        return $retval;
    }

    /**
     * Access the PPNs found via web service
     * @return array
     */
    public function getPPNs()
    {
        $ppns = array_merge($this->parallelppns, $this->swbppns);
        return array_unique($ppns);
    }

    /**
     * Returns array of local available ISILs
     * @return array
     */
    public function getLocalIsils()
    {
        return $this->localIsils;
    }

    public function getLinkLabels()
    {
        return $this->linklabels;
    }

    /**
     * Checks whether record is from HEBIS and it's ID begins with 8
     * @return boolean
     */

    protected function checkHebis8(): bool
    {
        $network = $this->driver->getNetwork();
        $ppn = $this->driver->getPPN();

        if ($network == 'HEBIS' && preg_match('/^8/', $ppn)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the record is available for free
     * @return boolean
     */

    protected function checkFree(): bool
    {
        if ($this->driver->isFree()) {
            return true;
        }
        return false;
    }

    /**
     * Determine is record is a serial or a collection
     * @return boolean
     */

    protected function checkSerialOrCollection(): bool
    {
        if ($this->driver->isSerial() && $this->driver->isCollection()) {
            return true;
        }
        return false;
    }

    /**
     * Determin if an item is available locally. Checks for
     * * 924 entries
     * * Parallel editions in SWB
     * * Similar results from SWB (if network != SWB)     *
     * @return boolean
     */

    protected function checkCurrentLibrary()
    {
        $status = false;
        $network = $this->driver->getNetwork();

        // if we have local holdings, item can't be ordered - except Journals
        if ($this->driver->hasLocalHoldings() && $this->getFormat() != static::FORMAT_JOURNAL) {
            $status = true;
        } elseif ($network !== 'SWB' && $this->queryWebservice()) {
            $status = true;
        }
        return $status;
    }

    /**
     * Query webservice to get SWB hits with the same
     * ISSN or ISBN (preferred)
     * Title, author and year (optional)
     * Found PPNs are added to ppns array and can be accessed by other methods.
     * @return boolean
     */
    protected function queryWebservice()
    {
        if (!$this->holding instanceof Holding) {
            return false;
        }

        // avoid running the webservice twice
        if (count($this->swbppns) > 0) {
            return true;
        }

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
                            $this->swbppns[] = '(DE-627)' . $ppn;
                            $this->linklabels[] = 'ILL::to_local_hit';
                            $this->libraries[] = $entry['isil'];
                        }
                    }
                }
            }
            // if no locally available ppn found, just take the first one
            if (count($this->swbppns) < 1 && isset($result['holdings'])) {
                reset($result['holdings']);
                $this->swbppns[] = '(DE-627)' . key($result['holdings']);
                $this->messages[] = 'ILL::no_lokal_hit_go_to_swb';
            }
        }
        // check if any of the isils from webservic matches local isils
        if (is_array($this->libraries) && count($this->libraries) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Check if there are parallel editions available
     * @return bool
     */
    protected function checkParallelEditions(): bool
    {
        $network = $this->driver->getNetwork();
        if ($network == 'SWB' && $this->hasParallelEditions()) {
            return true;
        }
        return false;
    }

    /**
     * Quer< solr for parallel Editions available at local libraries
     * Save the found PPNs in global array
     * @return boolean
     */
    protected function hasParallelEditions()
    {
        if (!$this->holding instanceof Holding) {
            return false;
        }
        // avoid running the web service twice
        if (count($this->parallelppns) > 0) {
            return true;
        }
        $ppns = [];
        $related = $this->driver->tryMethod('getRelatedEditions');
        $hasParallel = false;

        foreach ($related as $rel) {
            $ppns[] = $rel['id'];
        }
        $parallel = [];
        if (count($ppns) > 0) {
            $parallel = $this->holding->getParallelEditions($ppns, $this->localIsils);
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
                    $this->parallelppns[] = $record->getUniqueId();
                    $this->linklabels[] = 'ILL::to_parallel_edition';
                }
            }
        }
        return $hasParallel;
    }

    /**
     * Check if it is a journal and available locally.
     * @return bool
     */
    protected function checkJournalAvailable(): bool
    {
        if ($this->driver->hasLocalHoldings() && $this->getFormat() === static::FORMAT_JOURNAL) {
            return true;
        }
        return false;
    }

    /**
     * Check if format is enabled for inter-library loan and if it's enabled for
     * the current network
     * @return boolean
     */

    protected function checkFormat()
    {
        $section = $this->config->get($this->format);
        $network = $this->driver->getNetwork();

        $forbidden = $section->get('excludeNetwork', []);
        $forbidden = is_object($forbidden) ? $forbidden->toArray() : [];

        if (in_array($network, $forbidden)) {
            $this->messages[] = 'ILL::cond_format_network';
            return false;
        } elseif (!$section->get('enabled')) {
            $this->messages[] = 'ILL::cond_format_' . $this->format;
            return false;
        }
        return true;
    }

    /**
     * Check the ILL indicator - invalid or empty indicators are ignored
     * @return boolean
     */
    protected function checkIndicator()
    {
        $f924 = $this->driver->tryMethod('getField924');
        $section = $this->config->get($this->format);
        $tmp = $section->get('indicator', []);
        $allowedCodes = is_object($tmp) ? $tmp->toArray() : $tmp;

        // for this format not indicator check applicable
        if (count($allowedCodes) == 0) {
            return true;
        }

        foreach ($f924 as $field) {
            $code = isset($field['d']) ? $field['d'] : null;
            if (isset($code) && in_array($code, $allowedCodes)) {
                return true;
            }
        }
        return false;
    }
}
