<?php
/**
 * Factory for Bootstrap view helpers.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2014.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace BszTheme\View\Helper\Bodensee;
use Zend\ServiceManager\ServiceManager;


/**
 * Factory for Bootstrap view helpers.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 *
 * @codeCoverageIgnore
 */
class Factory 
{
    /**
     * Construct the Flashmessages helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Flashmessages
     */
    public static function getFlashmessages(ServiceManager $sm)
    {
        $messenger = $sm->getServiceLocator()->get('ControllerPluginManager')
            ->get('FlashMessenger');
        return new Flashmessages($messenger);
    }

    /**
     * Construct the LayoutClass helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return LayoutClass
     */
    public static function getLayoutClass(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $left = !isset($config->Site->sidebarOnLeft)
            ? false : $config->Site->sidebarOnLeft;
        $mirror = !isset($config->Site->mirrorSidebarInRTL)
            ? true : $config->Site->mirrorSidebarInRTL;
        $offcanvas = !isset($config->Site->offcanvas)
            ? false : $config->Site->offcanvas;
        // The right-to-left setting is injected into the layout by the Bootstrapper;
        // pull it back out here to avoid duplicate effort, then use it to apply
        // the mirror setting appropriately.
        $layout = $sm->getServiceLocator()->get('viewmanager')->getViewModel();
        if ($layout->rtl && !$mirror) {
            $left = !$left;
        }
        return new LayoutClass($left, $offcanvas);
    }
    /**
     * Construct the OpenUrl helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return OpenUrl
     */
    public static function getOpenUrl(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $client = $sm->getServiceLocator()->get('Bsz\Client');
        $isils = $client->getIsils();
        $openUrlRules = json_decode(
            file_get_contents(
                \VuFind\Config\Locator::getConfigPath('OpenUrlRules.json')
            ),
            true
        );
        
        return new OpenUrl(
            $sm->get('context'),
            $openUrlRules,
            isset($config->OpenURL) ? $config->OpenURL : null,
            !empty($isils) ? array_shift($isils) : null            
        );
    }
      
    /**
     * Construct the Record helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Record
     */
    public static function getRecord(ServiceManager $sm)
    {
        return new Record(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            $sm->getServiceLocator()->get('Bsz\Client'),
            $sm->getserviceLocator()->get('bsz\holding')
        );
    }
    /**
     * Construct the RecordLink helper.
     *
     * @param ServiceManager $sm Service manager.
     * 
     * @throws \Bsz\Exception
     *
     * @return Record
     */
    public static function getRecordLink(ServiceManager $sm)
    {
        $client = $sm->getServiceLocator()->get('bsz\config\client');
        $libraries = $sm->getServiceLocator()->get('bsz\config\libraries');      
        try {
            $library = $libraries->getFirst($client->getIsils());  
            // @todo: check adisUrl in Detals tab!
            // $adisUrl = $library->getAdisUrl() !== null ? $library->getADisUrl() : null;  
        } catch (\Exception $ex) {
            throw new \Bsz\Exception('Library not found');
        }

        return new RecordLink(
            $sm->getServiceLocator()->get('VuFind\RecordRouter'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config')
        );
    }
    
    /**
     * Construct the GetLastSearchLink helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return GetLastSearchLink
     */
    public static function getGetLastSearchLink(ServiceManager $sm)
    {
        return new GetLastSearchLink(
            $sm->getServiceLocator()->get('VuFind\Search\Memory')
        );
    }
    
        /**
     * Construct the Piwik helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Piwik
     */
    public static function getPiwik(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $url = isset($config->Piwik->url) ? $config->Piwik->url : false;
        $siteId = isset($config->Piwik->site_id) ? $config->Piwik->site_id : 1;
        $globalSiteId = isset($config->Piwik->site_id_global) ? $config->Piwik->site_id_global : 0;
        $customVars = isset($config->Piwik->custom_variables)
            ? $config->Piwik->custom_variables
            : false;
        return new Piwik($url, $siteId, $customVars, $globalSiteId);
    }
    
            /**
     * Construct the SearchTabs helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SearchTabs
     */
    public static function getSearchTabs(ServiceManager $sm)
    {
        return new SearchTabs(
            $sm->getServiceLocator()->get('VuFind\SearchResultsPluginManager'),
            $sm->get('url'), $sm->getServiceLocator()->get('VuFind\SearchTabsHelper')
        );
    }
    

    /**
     * @param ServiceManager $sm
     * @return \BszTheme\View\Helper\Bodensee\IllForm
     */
    public static function getIllForm(ServiceManager $sm) 
    {
        $request = $sm->getServiceLocator()->get('request');
        // params from form submission
        $params = $request->getPost()->toArray();
        // params from open url
        $openUrlParams = $request->getQuery()->toArray();
        $parser = $sm->getServiceLocator()->get('bsz\parser\openurl');            
        $parser->setParams($openUrlParams);
        // mapped openURL params
        $formParams = $parser->map2Form();
        // merge both param sets
        $mergedParams = array_merge($formParams, $params);
        return new IllForm($mergedParams);        
    }
    
}