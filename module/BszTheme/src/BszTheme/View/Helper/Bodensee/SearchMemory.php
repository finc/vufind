<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BszTheme\View\Helper\Bodensee;

/**
 * Description of SearchMemory
 *
 * @author amzar
 */
class SearchMemory extends \VuFind\View\Helper\Root\SearchMemory {
    
    /**
     * Use this instead of getLastSearchLink if you don't want any markup. 
     * 
     * @return string
     */
    public function getLastSearchUrl() {
        $last = $this->memory->retrieveSearch();
        if (!empty($last)) {
            $escaper = $this->getView()->plugin('escapeHtml');
            return $escaper($last);                 
        }
        return '';
    }
}
