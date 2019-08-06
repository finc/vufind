<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\RecordDriver;

/**
 * Description of MarcAuthorTrait
 *
 * @author amzar
 */
trait MarcAuthorTrait
{
    
    /**
     * Just a copy from DefaultRecord, can be removed after finishing this trait. 
     *
     * @param array $dataFields An array of extra data fields to retrieve (see
     * getAuthorDataFields)
     *
     * @return array
     */
    public function getDeduplicatedAuthors($dataFields = ['role'])
    {
        $authors = [];
        foreach (['primary', 'secondary', 'corporate'] as $type) {
            $authors[$type] = $this->getAuthorDataFields($type, $dataFields);
        }

        // deduplicate
        $dedup = function (&$array1, &$array2) {
            if (!empty($array1) && !empty($array2)) {
                $keys = array_keys($array1);
                foreach ($keys as $author) {
                    if (isset($array2[$author])) {
                        $array1[$author] = array_merge(
                            $array1[$author],
                            $array2[$author]
                        );
                        unset($array2[$author]);
                    }
                }
            }
        };

        $dedup($authors['primary'], $authors['corporate']);
        $dedup($authors['secondary'], $authors['corporate']);
        $dedup($authors['primary'], $authors['secondary']);

        $dedup_data = function (&$array) {
            foreach ($array as $author => $data) {
                foreach ($data as $field => $values) {
                    if (is_array($values)) {
                        $array[$author][$field] = array_unique($values);
                    }
                }
            }
        };

        $dedup_data($authors['primary']);
        $dedup_data($authors['secondary']);
        $dedup_data($authors['corporate']);

        return $authors;
    }
    
    public function getPrimaryAuthors() : array
    {
        $primary = $this->getFirstFieldValue('100', ['a', 'b']);
        return empty($primary) ? [] : [$primary];
    }
    public function getPrimaryAuthorsLives() : array
    {
        $primary = $this->getFirstFieldValue('100', ['d']);
        return empty($primary) ? [] : [$primary];
    }
    public function getPrimaryAuthorsRoles() : array
    {
        $primary = $this->getFirstFieldValue('100', ['4']);
        $array = strpos($primary, ' ') > 1 ? explode(' ', $primary) : [$primary];        
        return array_unique($array);
    }
    public function getPrimaryAuthorsGnds() : array
    {
        $primary = $this->getFirstFieldValue('100', ['0']);
        preg_match('/\(DE-588\)([0-9a-z]*)/i', $primary, $matches);
        return empty($matches[1]) ? [] : [$matches[1]];
    }    
    public function getSecondaryAuthors() : array
    {
        return $this->getFieldArray('700', ['a', 'b']);
    }
    public function getSecondaryAuthorsLives() : array
    {
        $secondary = $this->getFirstFieldValue('700', ['d']);
        return empty($secondary) ? [] : [$secondary];
    }
    public function getSecondaryAuthorsRoles() : array
    {
        $secondary = $this->getFirstFieldValue('700', ['4']);
        $array = strpos($secondary, ' ') > 1 ? explode(' ', $secondary) : [$secondary];        
        return array_unique($array);
    }
    public function getSecondaryAuthorsGnds() : array
    {
        $secondary = $this->getFirstFieldValue('700', ['0']);
        preg_match('/\(DE-588\)([0-9a-z]*)/i', $secondary, $matches);
        return empty($matches[1]) ? [] : [$matches[1]];
    } 
    
    public function getCorporateAuthors() : array
    {
        return array_merge(
            $this->getFieldArray('110', ['a', 'b']),
            $this->getFieldArray('111', ['a', 'b']),
            $this->getFieldArray('710', ['a', 'b']),
            $this->getFieldArray('711', ['a', 'b'])
        );
    }
    
    public function getCorporateAuthorsRoles() : array
    {
        return array_merge(
            $this->getFieldArray('110', ['4']),
            $this->getFieldArray('111', ['4']),
            $this->getFieldArray('710', ['4']),
            $this->getFieldArray('711', ['4'])
        );
    }
    
    
}
