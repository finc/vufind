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

use Bsz\FormatMapper;
use Bsz\RecordDriver\ContainerTrait;
use Bsz\RecordDriver\MarcAuthorTrait;
use Bsz\RecordDriver\SolrMarc;
use VuFind\RecordDriver\IlsAwareTrait;

/**
 * Description of SolrDlrmarc
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrDlrMarc extends SolrMarc
{
    use ContainerTrait;
    use IlsAwareTrait;
    use MarcAuthorTrait;

    /**
     * @param FormatMapper $mapper
     * @param type $mainConfig
     * @param type $recordConfig
     * @param type $searchSettings
     */
    public function __construct(
        FormatMapper $mapper,
        $mainConfig = null,
        $recordConfig = null,
        $searchSettings = null
    ) {
        parent::__construct($mapper, $mainConfig, $recordConfig, $searchSettings);
        $this->mapper = $mapper;
    }

    /**
     * Get all subjects associated with this item. They are unique.
     * @return array
     * @throws \File_MARC_Exception
     */
    public function getAllRVKSubjectHeadings()
    {
        $rvkchain = [];
        foreach ($this->getMarcRecord()->getFields('936') as $field) {
            foreach ($field->getSubFields('k') as $item) {
                $rvkchain[] = $item->getData();
            }
        }
        return array_unique($rvkchain);
    }

    /**
     * Get an array with RVK shortcut as key and description as value (array)
     * @returns array
     */
    public function getRVKNotations()
    {
        $notationList = [];
        $replace = [
            '"' => "'",
        ];
        foreach ($this->getMarcRecord()->getFields('936') as $field) {
            $suba = $field->getSubField('a');
            if ($suba) {
                $title = [];
                foreach ($field->getSubFields('k') as $item) {
                    $title[] = htmlentities($item->getData());
                }
                $notationList[$suba->getData()] = $title;
            }
        }
        return $notationList;
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
        // Vorläufiger Workaround um die Reihen auf Berichte zu mappen
        $keys = array_keys($formats, 'Serial');
        foreach ($keys as $key) {
            $formats[$key] = 'Report';
        }
        return $formats;
    }

    /**
     * As out fiels 773 does not contain any further title information we need
     * to query solr again
     * @return array
     */
    public function getContainer()
    {
        $relId = $f773 = $this->getFieldArray(773, ['w']);

        if (null === $this->container && is_array($relId) && count($relId) > 0) {
            $this->container = [];
            foreach ($relId as $k => $id) {
                $relId[$k] = 'ctrlnum:"(Horizon)' . $id . '"';
            }
            $params = [
                'lookfor' => implode(' OR ', $relId),
            ];
            // QnD
            // We need the searchClassId here to get proper filters
            $searchClassId = 'Solr';

            $results = $this->runner->run($params, $searchClassId);
            $this->container = $results->getResults();

        }
        return $this->container;
    }

     /**
     * @return array
     * @throws \File_MARC_Exception
     */
    public function getRelatedItems()
    {
        $related = [];
        $f774 = $this->getMarcRecord()->getFields('774');
        foreach ($f774 as $field) {
            $tmp = [];
            $subfields = $field->getSubfields();
            foreach ($subfields as $subfield) {
                switch ($subfield->getCode()) {
                    case 'd':
                        $label = 'edition';
                        break;
                    case 't':
                        $label = 'title';
                        break;
                    case 'w':
                        $label = 'id';
                        break;
                    case 'a':
                        $label = 'author';
                        break;
                    default:
                        $label = 'unknown_field';
                }
                if (!array_key_exists($label, $tmp)) {
                    $tmp[$label] = $subfield->getData();
                }
            }
            $related[] = $tmp;
        }
        return $related;
    }

    /**
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        return true;
    }

    /**
     * Get an array of all the languages associated with the record.
     * @return array
     * @throws \File_MARC_Exception
     */
    public function getLanguages(): array
    {
        $languages = [];
        $fields = $this->getMarcRecord()->getFields('041');
        $m008 = $this->getMarcRecord()->getField('008');

        foreach ($fields as $field) {
            foreach ($field->getSubFields('a') as $sf) {
                $languages[] = $sf->getData();
            }
        }

        if ($m008) {
            $data = $m008->getData();
            preg_match('/.{35}([a-z]{3})/', $m008->getData(), $matches);

            if (isset($matches[1])) {
                $languages[] = $matches[1];
            }
        }

        return $languages;
    }

    /**
     *
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
     * @return array
     */
    public function getHumanReadablePublicationDates()
    {
        $dates = parent::getHumanReadablePublicationDates();
        foreach ($dates as $k => $date) {
            preg_match('/^(\d{4})/', $date, $matches);
            $dates[$k] = isset($matches[1]) ? $matches[1] : null;
        }
        return $dates;
    }
    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers(): array
    {
        $fields = [
            260 => 'b',
            264 => 'b',
        ];
        $publishers = $this->getFieldsArray($fields);

        foreach ($publishers as $k => $publisher) {
            $publishers[$k] = preg_replace('/ :$/', '', $publisher);
        }
        return $publishers;
    }
}
