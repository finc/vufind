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

namespace Bsz\Controller;
use \Bsz\Cover\Loader;
use \Zend\View\Model\ViewModel;

/**
 * Our Cover Controller always returns HTML
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class CoverController extends \VuFind\Controller\CoverController{
    
    protected function getLoader()
    {
        // Construct object for loading cover images if it does not already exist:
        if (!$this->loader) {
            $cacheDir = $this->serviceLocator->get('VuFind\CacheManager')
                ->getCache('cover')->getOptions()->getCacheDir();
            
            $this->loader = new Loader(
                $this->getConfig(),
                $this->serviceLocator->get('VuFind\ContentCoversPluginManager'),
                $this->serviceLocator->get('VuFindTheme\ThemeInfo'),
                $this->serviceLocator->get('VuFind\Http')->createClient(),
                $cacheDir
            );
            
            \VuFind\ServiceManager\Initializer::initInstance(
                $this->loader, $this->serviceLocator
            );
        }     
        return $this->loader;
    }
    
    /**
     * 
     * @return ViewModel
     */
    public function showAction()
    {
        // protect against use from outside
        $request = $this->getRequest();
        $referrer = $request->getServer('HTTP_REFERER');   
        
        if (\Bsz\Debug::isInternal() || 
                (!empty($referrer) 
                    && preg_match('/.(localhost|bsz-bw\.de|bibliothek\.goethe\.de).*/Uis', $referrer) === 1 )) {
            $this->writeSession();  // avoid session write timing bug
            // Special case: proxy a full URL:
            $proxy = $this->params()->fromQuery('proxy');
            if (!empty($proxy)) {
                return $this->proxyUrl($proxy);
            }

            // Default case -- use image loader:
            $this->getLoader()->loadImage(
                    // Legacy support for "isn" param which has been superseded by isbn:
                    $this->params()->fromQuery('isbn', $this->params()->fromQuery('isn')), $this->params()->fromQuery('size'), $this->params()->fromQuery('contenttype'), $this->params()->fromQuery('title'), $this->params()->fromQuery('author'), $this->params()->fromQuery('callnumber'), $this->params()->fromQuery('issn'), $this->params()->fromQuery('oclc'), $this->params()->fromQuery('upc'), $this->params()->fromQuery('ean')
            );
            return $this->displayImage();
        } else {
            die();
        } 
        
    }
}
