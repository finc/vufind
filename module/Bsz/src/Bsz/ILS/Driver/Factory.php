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

namespace Bsz\ILS\Driver;

use Zend\ServiceManager\ServiceManager;

/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{

    public static function getDAIAbsz(ServiceManager $sm)
    {
        $client = $sm->getServiceLocator()->get('Bsz\Client');
        // if we are on ILL portal
        $baseUrl = '';
        $isils = $client->getIsils();

        if ($client->isIsilSession() && $client->hasIsilSession()) {            
            $libraries = $sm->getServiceLocator()->get('Bsz\libraries');
            $active = $libraries->getFirstActive($isils);
            $baseUrl = isset($active) ? $active->getUrlDAIA() : '';
        }
        


        $converter = $sm->getServiceLocator()->get('VuFind\DateConverter');
        return new DAIAbsz($converter, $isils, $baseUrl);
    }
    
    
    public static function getDAIA(ServiceManager $sm)
    {
        $client = $sm->getServiceLocator()->get('Bsz\Config\Client');
        // if we are on ILL portal
        $baseUrl = '';
        $isils = $client->getIsils();

        if ($client->isIsilSession() && $client->hasIsilSession()) {            
            $libraries = $sm->getServiceLocator()->get('Bsz\libraries');
            $active = $libraries->getFirstActive($isils);
            $baseUrl = isset($active) ? $active->getUrlDAIA() : '';
        }    

        $converter = $sm->getServiceLocator()->get('VuFind\DateConverter');
        return new DAIA($converter, $isils, $baseUrl);
    }
        /**
     * Factory for NoILS driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return NoILS
     */
    public static function getNoILS(ServiceManager $sm)
    {
        $libraries = $sm->getServiceLocator()->get('bsz\config\libraries');
        return new NoILS($sm->getServiceLocator()->get('VuFind\RecordLoader'), $libraries);
    }

    

}
