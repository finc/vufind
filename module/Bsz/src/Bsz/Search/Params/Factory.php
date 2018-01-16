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

namespace Bsz\Search\Params;
use \Zend\ServiceManager\ServiceManager;

/**
 * BSz Search params Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
    
    public static function getInterlending(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $options = $sm->getServiceLocator()
            ->get('VuFind\SearchOptionsPluginManager')->get('interlending');
        $params = new \Bsz\Search\Interlending\Params($options, $config);
        return $params;
    }
    /**
     * Factory for Interlending params object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getFis(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $options = $sm->getServiceLocator()
            ->get('VuFind\SearchOptionsPluginManager')->get('fis');
        $params = new \Bsz\Search\Fis\Params($options, $config);
        return $params;
    }
    /**
     * Factory for Interlending params object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getSolr(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $options = $sm->getServiceLocator()
            ->get('VuFind\SearchOptionsPluginManager')->get('solr');
        $params = new \Bsz\Search\Solr\Params($options, $config);
        return $params;
    }
}
