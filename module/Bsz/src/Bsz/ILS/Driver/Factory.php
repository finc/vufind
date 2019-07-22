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

use Interop\Container\ContainerInterface,
    Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory implements FactoryInterface
{

    public static function getDAIAbsz(ContainerInterface $container)
    {
        $client = $container->getServiceLocator()->get('Bsz\Config\Client');
        // if we are on ILL portal
        $baseUrl = '';
        $isils = $client->getIsils();

        if ($client->isIsilSession() && $client->hasIsilSession()) {            
            $libraries = $container->getServiceLocator()->get('Bsz\Config\Libraries');
            $active = $libraries->getFirstActive($isils);
            $baseUrl = isset($active) ? $active->getUrlDAIA() : '';
        }
        


        $converter = $container->getServiceLocator()->get('VuFind\DateConverter');
        return new DAIAbsz($converter, $isils, $baseUrl);
    }
    
    
    public static function getDAIA(ContainerInterface $container)
    {
        $client = $container->getServiceLocator()->get('Bsz\Config\Client');
        // if we are on ILL portal
        $baseUrl = '';
        $isils = $client->getIsils();

        if ($client->isIsilSession() && $client->hasIsilSession()) {            
            $libraries = $container->getServiceLocator()->get('Bsz\Config\Libraries');
            $active = $libraries->getFirstActive($isils);
            $baseUrl = isset($active) ? $active->getUrlDAIA() : '';
        }    

        $converter = $container->getServiceLocator()->get('VuFind\DateConverter');
        return new DAIA($converter, $isils, $baseUrl);
    }
        /**
     * Factory for NoILS driver.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return NoILS
     */
    public static function getNoILS(ContainerInterface $container)
    {
        $client = $container->getServiceLocator()->get('Bsz\Config\Client');
        $isils = $client->getIsilAvailability();
        $libraries = $container->getServiceLocator()->get('Bsz\Config\Libraries');
        return new NoILS($container->getServiceLocator()->get('VuFind\RecordLoader'), $libraries, $isils);
    }
    
    public function __invoke(ContainerInterface $container, $requestedName, 
        ?array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
                $client = $container->getServiceLocator()->get('Bsz\Config\Client');
        // if we are on ILL portal
        $baseUrl = '';
        $isils = $client->getIsils();

        if ($client->isIsilSession() && $client->hasIsilSession()) {            
            $libraries = $container->getServiceLocator()->get('Bsz\Config\Libraries');
            $active = $libraries->getFirstActive($isils);
            $baseUrl = isset($active) ? $active->getUrlDAIA() : '';
        }  
        $converter = $container->getServiceLocator()->get('VuFind\DateConverter');
        return new $requestedName($converter, $isils, $baseUrl);
        
    }

    

}
