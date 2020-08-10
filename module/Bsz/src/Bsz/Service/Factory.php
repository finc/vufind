<?php
namespace Bsz\Service;

use Interop\Container\ContainerInterface;

/**
 * Service Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Construct the SearchTabs helper.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return \VuFind\Search\SearchTabsHelper
     */
    public static function getSearchTabsHelper(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        $tabConfig = isset($config->SearchTabs)
            ? $config->SearchTabs->toArray() : [];
        $filterConfig = isset($config->SearchTabsFilters)
            ? $config->SearchTabsFilters->toArray() : [];

        $isils_string = $config->get('Site')->get('isil');
        $isils = explode(',', $isils_string);
        $tabname = 'Solr:filtered1';
        if (!array_key_exists($tabname, $filterConfig)) {
            $filterConfig[$tabname] = [];
        }
        foreach ($isils as $isil) {
            $filter = '~institution_id:"' . $isil . '"';
            if (array_key_exists($tabname, $filterConfig)
                && !in_array($filter, $filterConfig[$tabname])) {
                array_push($filterConfig[$tabname], $filter);
            }
        }
        $permissionConfig = isset($config->SearchTabsPermissions)
            ? $config->SearchTabsPermissions->toArray() : [];
        return new \VuFind\Search\SearchTabsHelper(
            $container->get('VuFind\SearchResultsPluginManager'),
            $tabConfig, $filterConfig,
            $container->get('Application')->getRequest(), $permissionConfig
        );
    }
}
