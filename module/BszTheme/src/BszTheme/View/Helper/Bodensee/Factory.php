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

use Interop\Container\ContainerInterface;

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
     * @param ContainerInterface$container Service manager.
     *
     * @return Flashmessages
     */
    public static function getFlashmessages(ContainerInterface $container)
    {
        $messenger = $container->get('ControllerPluginManager')
            ->get('FlashMessenger');
        return new Flashmessages($messenger);
    }

    /**
     * Construct the LayoutClass helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return LayoutClass
     */
    public static function getLayoutClass(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        $left = !isset($config->Site->sidebarOnLeft)
            ? false : $config->Site->sidebarOnLeft;
        $mirror = !isset($config->Site->mirrorSidebarInRTL)
            ? true : $config->Site->mirrorSidebarInRTL;
        $offcanvas = !isset($config->Site->offcanvas)
            ? false : $config->Site->offcanvas;
        // The right-to-left setting is injected into the layout by the Bootstrapper;
        // pull it back out here to avoid duplicate effort, then use it to apply
        // the mirror setting appropriately.
        $layout = $container->get('ViewManager')->getViewModel();
        if ($layout->rtl && !$mirror) {
            $left = !$left;
        }
        return new LayoutClass($left, $offcanvas);
    }

    /**
     * Construct the OpenUrl helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return OpenUrl
     */
    public static function getOpenUrl(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        $client = $container->get('Bsz\Config\Client');
        $isils = $client->getIsils();
        $openUrlRules = json_decode(
            file_get_contents(
                \VuFind\Config\Locator::getConfigPath('OpenUrlRules.json')
            ),
            true
        );
        $resolverPluginManager =
            $container->get('VuFind\ResolverDriverPluginManager');
        return new OpenUrl(
            $container->get('ViewHelperManager')->get('context'),
            $openUrlRules,
            $resolverPluginManager,
            isset($config->OpenURL) ? $config->OpenURL : null,
            !empty($isils) ? array_shift($isils) : null
        );
    }

    /**
     * Construct the Record helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return Record
     */
    public static function getRecord(ContainerInterface $container)
    {
        return new Record(
            $container->get('VuFind\Config')->get('config'),
            $container->get(\Bsz\Config\Client::class),
            $container->get('Bsz\Holding')
        );
    }

    /**
     * Construct the RecordLink helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @throws \Bsz\Exception
     *
     * @return Record
     */
    public static function getRecordLink(ContainerInterface $container)
    {
        $client = $container->get(\Bsz\Config\Client::class);
        $libraries = $container->get('Bsz\Config\Libraries');
        $adisUrl = null;

        $library = $libraries->getFirstActive($client->getIsils());
        if ($library instanceof \Bsz\Config\Library) {
            $adisUrl = $library->getAdisUrl() !== null ? $library->getADisUrl() : null;
        }

        return new RecordLink(
            $container->get('VuFind\RecordRouter'),
            $container->get('VuFind\Config')->get('bsz'),
            $adisUrl
        );
    }

    /**
     * Construct the GetLastSearchLink helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return GetLastSearchLink
     */
    public static function getSearchMemory(ContainerInterface $container)
    {
        return new SearchMemory(
            $container->get('VuFind\Search\Memory')
        );
    }

    /**
     * Construct the Piwik helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return Piwik
     */
    public static function getPiwik(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
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
     * @param ContainerInterface$container Service manager.
     *
     * @return SearchTabs
     */
    public static function getSearchTabs(ContainerInterface $container)
    {
        return new SearchTabs(
            $container->get('VuFind\SearchResultsPluginManager'),
            $container->get('ViewHelperManager')->get('url'),
            $container->get('VuFind\SearchTabsHelper')
        );
    }

    /**
     * @param ContainerInterface$container
     * @return \BszTheme\View\Helper\Bodensee\IllForm
     */
    public static function getIllForm(ContainerInterface $container)
    {
        $request = $container->get('request');
        // params from form submission
        $params = $request->getPost()->toArray();
        // params from open url
        $openUrlParams = $request->getQuery()->toArray();
        $parser = $container->get('Bsz\Parser\OpenUrl');
        $parser->setParams($openUrlParams);
        // mapped openURL params
        $formParams = $parser->map2Form();
        // merge both param sets
        $mergedParams = array_merge($formParams, $params);
        return new IllForm($mergedParams);
    }

    public static function getMapongo(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        return new Mapongo(
            $client->get('Mapongo')
        );
    }
}
