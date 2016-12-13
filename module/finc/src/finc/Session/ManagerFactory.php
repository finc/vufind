<?php
/**
 * Factory for instantiating Session Manager
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2016.
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
 * @package  Session_Handlers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\Session;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for instantiating Session Manager
 *
 * @category VuFind
 * @package  Session_Handlers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 *
 * @codeCoverageIgnore
 */
class ManagerFactory extends \VuFind\Session\ManagerFactory
{
    /**
     * Build the options array.
     *
     * @param ServiceLocatorInterface $sm Service manager
     *
     * @return array
     */
    protected function getOptions(ServiceLocatorInterface $sm)
    {
        $cookieManager = $sm->get('VuFind\CookieManager');
        $options = [
            'cookie_path' => $cookieManager->getPath(),
            'cookie_secure' => $cookieManager->isSecure()
        ];

        $domain = $cookieManager->getDomain();
        if (!empty($domain)) {
            $options['cookie_domain'] = $domain;
        }

        $name = $cookieManager->getSessionName();
        if (!empty($name)) {
            $options['name'] = $name;
        }

        return $options;
    }
}
