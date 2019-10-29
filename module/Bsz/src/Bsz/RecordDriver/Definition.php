<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\RecordDriver;

/**
 * Description of ConstantTrait
 *
 * @author amzar
 */
interface Definition {
    
    const DELIMITER = ' ';
    // Multipart Levels
    const MULTIPART_PART = 'part';
    const MULTIPART_COLLECTION = 'collection';
    const NO_MULTIPART = 'no_multipart';
    // Bibliographic Levels
    const BIBLIO_MONO_COMPONENT = 'MonographPart';
    const BIBLIO_SERIAL_COMPONENT = 'SerialPart';
    const BIBLIO_COLLECTION = 'Collection';
    const BIBLIO_SUBUNIT = 'Subunit';
    const BIBLIO_MONOGRAPH = 'Monograph';
    const BIBLIO_SERIAL = 'Serial';
    const BIBLIO_INTEGRATED = 'Integrated';
    // Simple breakdown of above 
    const INDEPENDENT = 'independent';
    const COLLECTION = 'collection';
    const PART = 'part';
    
    const AUTHOR_GND = 'gnd';
    const AUTHOR_LIVE = 'live';
    const AUTHOR_NOLIVE = 'nolive'; // deprecated, will be removed
    const AUTHOR_NAME = 'name';
    
    public function getCallNumber() : string;
    public function getPPN() : string;
    public function getISBNs() : array;
    public function getISSNs() : array;
    public function getCleanISBN() : string;
    public function getCleanISSN() : string;
    public function getLanguages() : array;
    public function getPublishers() : array;
    public function getTitle() : string;
    public function getShortTitle() : string;
    public function getSubTitle() : string;
    #public function getSubTitle() : string|bool ;
    //public function getURLs() : array;
    
    
    
    
    
    
    
    
}
