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

namespace Bsz\Search\Results;
use \Zend\ServiceManager\ServiceManager;

/**
 * BSz Search Results Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
    /**
     * Factory for Interlending results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getInterlending(ServiceManager $sm)
    {
        $factory = new PluginFactory();
        $solr = $factory->createServiceWithName($sm, 'interlending', 'Interlending');
        $config = $sm->getServiceLocator()
            ->get('VuFind\Config')->get('config');
        $spellConfig = isset($config->Spelling)
            ? $config->Spelling : null;
        $solr->setSpellingProcessor(
            new \VuFind\Search\Solr\SpellingProcessor($spellConfig)
        );
        return $solr;
    }
    /**
     * Factory for Interlending results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getFis(ServiceManager $sm)
    {
        $factory = new PluginFactory();
        $solr = $factory->createServiceWithName($sm, 'fis', 'Fis');
        $config = $sm->getServiceLocator()
            ->get('VuFind\Config')->get('config');
        $spellConfig = isset($config->Spelling)
            ? $config->Spelling : null;
        $solr->setSpellingProcessor(
            new \VuFind\Search\Solr\SpellingProcessor($spellConfig)
        );
        return $solr;
    }
}
