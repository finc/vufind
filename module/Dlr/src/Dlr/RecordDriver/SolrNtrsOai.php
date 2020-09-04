<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Dlr\RecordDriver;

use DateTime;
use VuFind\RecordDriver\IlsAwareTrait;
use VuFind\RecordDriver\SolrDefault;
use VuFind\Search\SearchRunner;

/**
 * Description of SolrOai
 * @author Stefan Winkler <stefan.winkler@bsz-bw.de>
 */
class SolrNtrsOai extends SolrDefault
{
    use IlsAwareTrait;

    /**
     * @var SimpleXMLElement
     */
    protected $xml;


    /**
     * Attach a Search Results Plugin Manager connection and related logic to
     * the driver
     *
     * @param \VuFind\SearchRunner $runner
     *
     * @return void
     */
    public function attachSearchRunner(SearchRunner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     *                    objects are normally constructed by Record Driver objects
     *                    using data passed in from a Search Results object.  The
     *                    exact nature of the data may vary depending on the data
     *                    source -- the important thing is that the Record Driver +
     *                    Search Results objects work together correctly.
     *
     * @return void
     */
    public function setRawData($data)
    {
        $this->fields = $data;
        $this->xml = simplexml_load_string($this->fields['fullrecord']);
    }

    /**
     * Returns an array with url and desc keys to link the document id.
     * @return array
     * @throws \Exception
     */
    public function getDocumentLink()
    {
        $link = [];
        $id = parent::getUniqueID();
        $split = explode(':', $id);
        if (strpos($split[1], 'nasa') !== false) {
            $link['url'] = 'https://ntrs.nasa.gov/search.jsp?R=' . end($split);
        } else {
            $link['url'] = 'https://elib.dlr.de/' . end($split);
        }
        $link['desc'] = end($split);
        return $link;
    }

    public function getCopyright()
    {
        $copy = $this->getDcFields('coverage');
        return array_shift($copy);
    }

    /**
     * @param string $field
     *
     * @return array
     */
    protected function getDcFields($field)
    {
        return $this->xml->xpath('dc:' . $field);
    }

    public function getSource()
    {
        $source = $this->getDcFields('source');
        return array_shift($source);
    }

    /**
     * get all formats from solr field format
     * @return array
     */
    public function getFormats()
    {
        $formats = [];
        if (isset($this->fields['format'])) {
            $formats = $this->fields['format'];
        }
//        // Vorläufiger Workaround um die Reihen auf Berichte zu mappen
//        $keys = array_keys($formats, 'Serial');
//        foreach ($keys as $key) {
//            $formats[$key] = 'Report';
//        }
        return $formats;
    }

    /**
     * get Institutes and Institutions from solr field
     * @return array
     */
    public function getInstitutes()
    {
        $institutes = [];
        if (isset($this->fields['institute'])) {
            $institutes = array_filter($this->fields['institute']);
        }

        return array_unique($institutes, SORT_STRING);
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     * @return array
     */
    public function getURLs()
    {
        //url = 856u:555u

        $urls = [];
        $urls = parent::getURLs();
        foreach ($urls as $key => $url) {
            // different descriptions for elib and NTRS
            if (!array_key_exists('desc', $url) && $this->isElib()) {
                switch ($key) {
                    case 0:
                        $url['desc'] = 'to_elib_record';
                        break;
                    default:
                        $url['desc'] = 'More Information';
                }
            } elseif (!array_key_exists('desc', $url) && $this->isNTRS()) {
                $url['desc'] = 'Full Text';
            }
            $urls[$key] = $url;
        }
        return $urls;
    }

    /**
     * Source elib?
     * @return boolean
     */
    protected function isElib()
    {
        if (isset($this->fields['institution_id']) &&
            in_array('elib', $this->fields['institution_id'])) {
            return true;
        }
        return false;
    }

    /**
     * Source NASA?
     * @return boolean
     */
    protected function isNTRS()
    {
        if (isset($this->fields['institution_id']) &&
            in_array('NTRS', $this->fields['institution_id'])) {
            return true;
        }
        return false;
    }

    /**
     * For rticles: get container title
     * @return string
     */
    public function getContainerTitle()
    {
        return $this->getContainerInfo(0);
    }

    /**
     * For rticles: get container title
     * @return array
     */
    public function getContainer()
    {
        return [];
    }

    /**
     * Get the Container issue from different fields
     * @return string
     */
    public function getContainerIssue()
    {
        return $this->getContainerInfo(2);
    }

    /**
     * @return string
     */
    public function getContainerVolume()
    {
        return $this->getContainerInfo(1);
    }

    /**
     * @param $arraykey
     *
     * @return string
     */
    private function getContainerInfo($arraykey)
    {
        $array = $this->getDcFields('type');
        if (is_array($array)) {
            $raw = array_shift($array);
            if (!empty($raw)) {
                $string = $raw->__toString();
                $parts = explode('; ', $string);
                return isset($parts[$arraykey]) ? $parts[$arraykey] : '';
            }

        }
        return '';
    }

    /**
     * Get container pages from different fields
     * @return string
     */
    public function getContainerPages()
    {
        return $this->getContainerInfo(3);
    }

    /**
     * get container year from different fields
     * @return string
     */
    public function getContainerYear()
    {
        // not supported for OAI data:
        return '';
    }

    /**
     * get container year from different fields
     * @return array
     */
    public function getRelatedItems()
    {
        // not supported for OAI data:
        return [];
    }

    /**
     * @return string
     */
    public function getContainerRelParts()
    {
        return '';
    }

    /**
     * NASA OAI data are always articles.
     * @return bool
     */
    public function isArticle()
    {
        return true;
    }

    /**
     * @return array
     */
    protected function getBookOpenUrlParams()
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        $params['rft.genre'] = 'book';
        $params['rft.btitle'] = $this->getTitle();
        $params['rft.volume'] = $this->getContainerVolume();
        $series = $this->getSeries();
        if (count($series) > 0) {
            // Handle both possible return formats of getSeries:
            $params['rft.series'] = is_array($series[0]) ?
                $series[0]['name'] : $series[0];
        }
        $authors = $this->getPrimaryAuthors();
        $params['rft.au'] = array_shift($authors);
        $publication = $this->getPublicationDetails();
        // we drop everything, except first entry
        $publication = array_shift($publication);
        if (is_object($publication)) {
            if ($date = $publication->getDate()) {
                $params['rft.date'] = preg_replace('/[^0-9]/', '', $date);
            }
            if ($place = $publication->getPlace()) {
                $params['rft.place'] = $place;
            }
        }
        $params['rft.volume'] = $this->getContainerVolume();

        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }

        $params['rft.edition'] = $this->getEdition();
        $params['rft.isbn'] = (string)$this->getCleanISBN();
        return array_filter($params);
    }

    /**
     * Get default OpenURL parameters.
     * this is slightly changed compared to VuFind original
     * @return array
     */
    protected function getDefaultOpenUrlParams()
    {
        // Get a representative publication date:
        $pubDate = $this->getPublicationDates();
        $pubDate = empty($pubDate) ? '' : $pubDate[0];

        // Start an array of OpenURL parameters:
        return [
            'url_ver' => 'Z39.88-2004',
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => 'info:sid/' . $this->getCoinsID() . ':generator',
            'rft.date' => $pubDate
        ];
    }

    /**
     * Parse the date out of oai data
     * @return array
     */
    public function getPublicationDates()
    {
        $dates = $this->getDcFields('date');
        // if we got a known format, parse this
        if (isset($dates[0]) && strlen($dates[0]) == 8) {
            $year = substr($dates[0], 0, 4);
            $month = substr($dates[0], 4, 2);
            $day = substr($dates[0], 6, 2);
            $date = new DateTime($year . '-' . $month . '-' . $day);
            return [$date->format('d.m.Y')];
        }
        return $dates;
    }

    /**
     * Support method for getOpenUrl() -- pick the OpenURL format.
     *
     * @return string
     */
    protected function getOpenUrlFormat()
    {
        // If we have multiple formats, Book, Journal and Article are most
        // important...
        $formats = $this->getFormats();
        if (in_array('Book', $formats)) {
            return 'Book';
        } elseif (in_array('Article', $formats)) {
            return 'Article';
        } elseif (in_array('Journal', $formats)
            || in_array('Serial', $formats)
        ) {
            return 'Journal';
        } elseif (isset($formats[0])) {
            return $formats[0];
        } elseif (strlen($this->getCleanISSN()) > 0) {
            return 'Journal';
        } elseif (strlen($this->getCleanISBN()) > 0) {
            return 'Book';
        }
        return 'UnknownFormat';
    }

    /**
     * Get OpenURL parameters for a journal.
     *
     * @return array
     */
    protected function getJournalOpenUrlParams()
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft.title'] = $this->getTitle();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['rft.genre'] = 'journal';
        $params['rft.jtitle'] = $params['rft.title'];
        $params['rft.issn'] = $this->getCleanISSN();
        $params['rft.au'] = $this->getPrimaryAuthor();
        $params['rft.issn'] = (string)$this->getCleanISSN();

        // Including a date in a title-level Journal OpenURL may be too
        // limiting -- in some link resolvers, it may cause the exclusion
        // of databases if they do not cover the exact date provided!
        unset($params['rft.date']);

        // If we're working with the SFX resolver, we should add a
        // special parameter to ensure that electronic holdings links
        // are shown even though no specific date or issue is specified:
        if (isset($this->mainConfig->OpenURL->resolver)
            && strtolower($this->mainConfig->OpenURL->resolver) == 'sfx'
        ) {
            $params['sfx.ignore_date_threshold'] = 1;
        }
        return $params;
    }
}
