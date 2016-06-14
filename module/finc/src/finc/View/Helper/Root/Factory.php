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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
            $sm->getServiceLocator()->get('finc\Rewrite')
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
        $openUrlRules = json_decode(
            file_get_contents(
                \VuFind\Config\Locator::getConfigPath('OpenUrlRules.json')
            ),
            true
        );
        return new OpenUrl(
            $sm->get('context'),
            $openUrlRules,
            isset($config->General) ? $config : null
        );
    }
}
