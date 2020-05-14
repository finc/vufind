<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\RecordDriver;

/**
 * Trait for Helper methods
 *
 * @author amzar
 */
trait HelperTrait {

        /**
     * Removes colon and slash at the end of the string
     * Removes any HTML
     * @param string $string
         * 
     * @return string
         * 
     */
    public function cleanString(?string $string = '') : string
    {
        $string = trim($string);
        $string = preg_replace('/:$|\/$/', '', $string);
//        $string = strip_tags($string);
        $string = trim($string);
        return $string;
    }
    
    /**
     * Return breadcrumb information for this record
     * 
     * @return string
     */
    public function getBreadcrumb() : string
    {
        return $this->cleanString($this->getTitle());
    }
    
}
