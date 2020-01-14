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

/**
 * Description of SolrDlrmarc
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrDlrMarc extends \Bsz\RecordDriver\SolrMarc 
    implements \Bsz\RecordDriver\Definition {
    
    /**
     * @param FormatMapper $mapper
     * @param type $mainConfig
     * @param type $recordConfig
     * @param type $searchSettings
     */
    
    public function __construct(FormatMapper $mapper,$mainConfig = null, $recordConfig = null,
        $searchSettings = null) {
        
        parent::__construct($mapper, $mainConfig, $recordConfig, $searchSettings);
        $this->mapper = $mapper;
    }
    
    /**
     * Get all subjects associated with this item. They are unique. 
     *
     * @return array
     */
    public function getAllRVKSubjectHeadings()
    {
        $rvkchain = []; 
        foreach ($this->getMarcRecord()->getFields('936') as $field)
        {
            foreach ($field->getSubFields('k') as $item)
            {
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
        foreach ($this->getMarcRecord()->getFields('936') as $field)
        {
            $suba = $field->getSubField('a');
            if($suba) {
                $title = [];
                foreach ($field->getSubFields('k') as $item)
                {
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
     * Get multipart level from leader 19
     * @return boolean|string
     */
    public function getMultipartLevel()
    {

        $leader = $this->getMarcRecord()->getLeader();
        $multipartLevel = strtoupper($leader{19});

        switch ($multipartLevel) {
            case 'A':
                return static::MULTIPART_COLLECTION;
            //difference between B and C is if they have independend titles
            case 'B':
                return static::NO_MULTIPART;
            case 'C':
                return static::MULTIPART_PART;
            default: 
                return static::NO_MULTIPART;
        }
    }

    /**
     * Get bibliographic level from leader 7
     * @return string
     */
    public function getBibliographicLevel()
    {

        $leader = $this->getMarcRecord()->getLeader();
        $bibliographicLevel = strtoupper($leader{7});
        switch ($bibliographicLevel) {
            case 'A': // Monographic component part
                return static::BIBLIO_MONO_COMPONENT;
            //difference between B and C is if they have independend titles
            case 'B': // Serial component part
                return static::BIBLIO_SERIAL_COMPONENT;
            case 'C': // Collection
                return static::BIBLIO_COLLECTION;
            case 'D': //Subunit
                return static::BIBLIO_SUBUNIT;
            case 'I': //Integration resource
                return static::BIBLIO_INTEGRATED;
            case 'M': //Monograph/Item
                return static::BIBLIO_MONOGRAPH;
            case 'S': //Serial
                return static::BIBLIO_SERIAL;
        }
    }
    

    /**
     * is this item part of a collection?
     * @return boolean
     */
    public function isPart()
    {
        
        $part = [
            static::MULTIPART_PART,
            static::BIBLIO_SERIAL,
            static::BIBLIO_MONO_COMPONENT
                
        ];
        if (in_array($this->getBibliographicLevel(), $part) ||
                in_array($this->getMultipartLevel(), $part)) {
            return true;
        }
        return false;
    }
    
    /**
     * As out fiels 773 does not contain any further title information we need 
     * to query solr again
     *
     * @return array
     */
    public function getContainer()
    {
        if (null === $this->container &&
            $this->isPart()) {
            $relId = $f773 = $this->getFieldArray(773, ['w']);
            $this->container = [];
            if (is_array($relId) && count($relId) > 0) {
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
        }
        return $this->container;
    }

    public function getRelatedItems() 
    {
        $related = [];
        $f774 = $this->getMarcRecord()->getFields('774');
        foreach ($f774 as $field) {
            $tmp = [];
            $subfields = $field->getSubfields();
            foreach ($subfields as $subfield) {
                switch ($subfield->getCode()) {
                    case 'd': $label = 'edition';
                        break;
                    case 't': $label = 'title';
                        break;
                    case 'w' : $label = 'id';
                        break;
                    case 'a' : $label = 'author';
                        break;
                    default: $label = 'unknown_field';
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
     * Get the main corporate author (if any) for the record.
     *
     * @return string
     */
    public function getCorporateAuthor()
    {
        // Try 110 first -- if none found, try 710 next.
        $main = $this->getFirstFieldValue('110', ['a', 'c', 'b']);
        if (!empty($main)) {
            return $main;
        }
        return $this->getFirstFieldValue('710', ['a', 'c', 'b']);
    }
    
        /**
     * Get an array of all secondary authors (complementing getPrimaryAuthor()).
     *
     * @return array
     */
    public function getSecondaryAuthors()
    {
        $corporate = $this->getCorporateAuthor();
        if (empty($corporate)) {
            return isset($this->fields['author2']) ?
                $this->fields['author2'] : [];            
        } else {
            return [];
        }
    }
}
