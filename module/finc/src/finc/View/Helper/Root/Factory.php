<?php
/**
 * Factory for Root view helpers.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;
use Zend\ServiceManager\ServiceManager;

/**
 * Factory for Root view helpers.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Construct the Permission helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Permission
     */
    public static function getPermission(ServiceManager $sm)
    {
        return new Permission(
            $sm->getServiceLocator()->get('VuFind\AuthManager'),
            $sm->getServiceLocator()->get('ZfcRbac\Service\AuthorizationService')
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
            $sm->get('url'),
            $sm->getServiceLocator()->get('VuFind\AuthManager'),
            $sm->getServiceLocator()->get('finc\Rewrite'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('Resolver')
        );
    }

    /**
     * Construct the RecordLink helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return RecordLink
     */
    public static function getRecordLink(ServiceManager $sm)
    {
        return new RecordLink(
            $sm->getServiceLocator()->get('VuFind\RecordRouter'),
            $sm->getServiceLocator()->get('VuFind\RecordLoader'),
            $sm->getServiceLocator()->get('VuFind\Search')
        );
    }

    /**
     * Construct the Record helper.
     *
     * @return RecordLink
     */
    public static function getInterlibraryLoanLink()
    {
        return new InterlibraryLoanLink();
    }

    /**
     * Construct the Citation helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Citation
     */
    public static function getCitation(ServiceManager $sm)
    {
        return new Citation($sm->getServiceLocator()->get('VuFind\DateConverter'));
    }

    /**
     * Construct the Branches.yaml helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return BranchInfo
     */
    public static function getBranchInfo(ServiceManager $sm)
    {
        return new BranchInfo(
            $sm->getServiceLocator()
        );
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
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('Resolver');

        // check if config json exists, as fallback empty array is passed to
        // constructor
        if (file_exists(
            \VuFind\Config\Locator::getConfigPath('OpenUrlRules.json')
        )) {
            $openUrlRules = json_decode(
                file_get_contents(
                    \VuFind\Config\Locator::getConfigPath('OpenUrlRules.json')
                ),
                true
            );
        }

        return new OpenUrl(
            $sm->get('context'),
            empty($openUrlRules) ? [] : $openUrlRules,
            isset($config->General) ? $config : null
        );
    }

    /**
     * Construct the SideFacet helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SideFacet
     */
    public static function getSideFacet(ServiceManager $sm)
    {
        return new SideFacet(
            $sm->getServiceLocator()->get('VuFind\Config')->get('facets')
        );
    }

    /**
     * Construct the External Catalogue Link Record helper.
     *
     * @param ServiceManager $sm Service manager
     *
     * @return ExternalCatalogueLink
     */
    public static function getExternalCatalogueLink(ServiceManager $sm)
    {
        // check if config json exists, as fallback empty array is passed to
        // constructor
        if (file_exists(
            \VuFind\Config\Locator::getConfigPath('ExternalCatalogue.json')
        )) {
            $externalAccessLinks = json_decode(
                file_get_contents(
                    \VuFind\Config\Locator::getConfigPath('ExternalCatalogue.json')
                ),
                true
            );
        }

        return new ExternalCatalogueLink(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            empty($externalAccessLinks) ? [] : $externalAccessLinks
        );
    }

}
