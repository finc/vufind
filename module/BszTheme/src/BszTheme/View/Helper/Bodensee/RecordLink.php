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

namespace BszTheme\View\Helper\Bodensee;

/**
 * Extension of Root RecordLink Helper
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class RecordLink extends \VuFind\View\Helper\Root\RecordLink {
    /**
     *
     * @var \VuFind\Config\config
     */
    protected $config;
    /**
     *
     * @var string
     */
    protected $baseUrl;

    public function __construct(\VuFind\Record\Router $router, \Zend\Config\Config $config, $baseUrl = null)
    {
        parent::__construct($router);
        $this->baseUrl = $baseUrl;
        $this->config = $config;
    }

    public function getCoverServiceUrls($driver) {
        $services = [];
        $sources = $this->config->get('CoverSources');

        foreach($sources as $source => $url) {
            $isxn = strlen($driver->getCleanISSN()) > 0 ?
                    $driver->getCleanISSN() : $driver->getCleanISBN();
            if(strlen($isxn) > 0) {
                $services[$source] = sprintf($url, $isxn);
            }
        }
        return $services;
    }

    public function linkPPN(\Bsz\RecordDriver\SolrMarc $driver, $url = '')
    {
        $id = $driver->getUniqueId();
        $pos = strpos($id, ')');
        $ppn = substr($id, $pos + 1);
        $recordHelper = $this->getView()->plugin('record');
        $label = '';
        $url = empty($url) ? $this->baseUrl : $url;


        // if the record is available at the first ISIL, either link an external
        // url or use the baseUrl (which is normally the aDIS URL of the current
        // library).
        // otherwise use the network OPAC urls, which can be found in BSZ.ini

        if ($driver->getNetwork() == 'SWB' && $recordHelper->isAtFirstIsil()) {
            // deprecated but still in the database
            $url = str_replace('<PPN>', $ppn, $url);
            // new syntax
            $url = str_replace('%PPN%', $ppn, $url);
            $label = 'To library OPAC';
        } else {
            $label = 'To network OPAC';
            $opacList = $this->config->get('OPAC')->toArray();
            $network = $driver->getNetwork();
            if (array_key_exists($network, $opacList)) {
                $url = $opacList[$network];
                $url = str_replace('%PPN%', $ppn, $url);
            }
        }
        return $this->getView()->render('Helpers/ppn.phtml', [
            'ppn' => $ppn,
            'link' => $url,
            'label' => $label
        ]);
    }

    public function linkPPNButton(\Bsz\RecordDriver\SolrMarc $driver, $url = '')
    {
        $id = $driver->getUniqueId();
        $pos = strpos($id, ')');
        $ppn = substr($id, $pos + 1);
        $recordHelper = $this->getView()->plugin('record');
        $label = '';
        $url = empty($url) ? $this->baseUrl : $url;


        // if the record is available at the first ISIL, either link an external
        // url or use the baseUrl (which is normally the aDIS URL of the current
        // library).
        // otherwise use the network OPAC urls, which can be found in BSZ.ini

        if ($driver->getNetwork() == 'SWB' && $recordHelper->isAtFirstIsil()) {
            // deprecated but still in the database
            $url = str_replace('<PPN>', $ppn, $url);
            // new syntax
            $url = str_replace('%PPN%', $ppn, $url);
            $label = 'To library OPAC';
        } else {
            $label = 'To network OPAC';
            $opacList = $this->config->get('OPAC')->toArray();
            $network = $driver->getNetwork();
            if (array_key_exists($network, $opacList)) {
                $url = $opacList[$network];
                $url = str_replace('%PPN%', $ppn, $url);
            }
        }
        return $this->getView()->render('Helpers/ppnButton.phtml', [
            'ppn' => $ppn,
            'link' => $url,
            'label' => $label
        ]);
    }
    
}
