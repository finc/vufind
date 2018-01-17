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

namespace Bsz\Recommend;
use Zend\ServiceManager\ServiceManager;
use Bsz\Recommend\SideFacets as SideFacets;

/**
 * Description of Factors
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
        /**
     * Factory for SideFacets module.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SideFacets
     */
    public static function getSideFacets(ServiceManager $sm)
    {
        $client = $sm->getServiceLocator()->get('bsz\client');
        $isil = $client->isIsilSession() && $client->hasIsilSession() ? $client->getIsils() : null;
       
        return new SideFacets(
            $sm->getServiceLocator()->get('VuFind\Config'),
            $sm->getServiceLocator()->get('VuFind\HierarchicalFacetHelper'),
            $isil
        );
    }


    /**
     * Factory for SearchButtons module.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return WorldCatTerms
     */
    public static function getSearchButtons(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        return new SearchButtons(
            $config->Content->europeanaAPI
        );
    }        
    
}
