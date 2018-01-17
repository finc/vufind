<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Bsz\RecordDriver;
use Bsz\FormatMapper;

/**
 * Description of SolrFismarc
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrFismarc extends SolrGvimarc {
    
        /**
     * Get an array of all the languages associated with the record.
     *
     * @return array
     */
    public function getLanguages()
    {
        $languages = [];
        $fields = $this->getMarcRecord()->getFields('041');
        foreach($fields as $field)
        {
            foreach($field->getSubFields('a') as $sf)
            {
                $languages[] = $sf->getData();
            }             

        }
        return $languages;
    }
    
    /**
     * Get SWD subjects.     *
     * @return array
     */
    public function getAllSWDSubjectHeadings()
    {
        $subjects = []; 
        foreach ($this->getFieldArray(653, ['a']) as $field){
           $subjects[][] = trim($field); 
        }

        return $subjects;
    }
    
    /**
     * Is this a FIS record
     * @return boolean
     */
    public function isFisBildung() {
        return true;
    }
    
    public function getAbstract() {
        $abstract = $this->getFieldArray(520, ['a']);
        return array_shift($abstract);
        
    }    
    public function supportsAjaxStatus() {
        return false;
    }
    
        /**
     * Get container pages from different fields
     * @return string
     */
    public function getContainerPages()
    {
        $pages = '';
        $raw = $this->getFieldArray(773, ['g'], true);
        $raw = array_shift($raw);
        // Split at the comma
        $parts = preg_split('/, /', $raw);
        if (isset($parts[1])) {
            // get the number after S. and the number after - (optionally)
            preg_match('/S\. (\d*)-?(\d*)/', $parts[1], $pages);            
        }
        $start = isset($pages[1]) ? $pages[1] : '';
        $end = isset($pages[2]) ? $pages[2] : '';
        $output = '';
        if (!empty($start)) {
            $output .= $start;
        } 
        if (!empty($end)) {
            $output .= '-'.$end;
        }
        return $output;
    }
    
        /**
     * Get the Container issue from different fields
     * @return string
     */
    public function getContainerIssue()
    {
        $issue = '';
        $raw = $this->getFieldArray(773, ['g'], true);
        $raw = array_shift($raw);
        // Split at the comma
        $parts = preg_split('/, /', $raw);
        
        // get the number after H.
        if (isset($parts[0])) {
            preg_match('/H\. (\d*)/', $parts[0], $issue);            
        }
        return end($issue);
    }
        /**
     * get container year from different fields
     * @return string
     */
    public function getContainerYear()
    {
        $year = '';
        $raw = $this->getFieldArray(773, ['g'], true);
        $raw = array_shift($raw);
        // Split at the comma
        // get the number after H.
        preg_match('/\((.*?)\)/', $raw, $year);
        return end($year);
    }
    
    /**
     * For rticles: get container title
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [
            773 => ['t'], //SWB, GBV
        ];
        $array = $this->getFieldsArray($fields);
        return array_shift($array);
    }    

}
