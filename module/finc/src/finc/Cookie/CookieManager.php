<?php
/**
 * Cookie Manager
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
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
 * @package  Cookie
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\Cookie;

/**
 * Cookie Manager
 *
 * @category VuFind
 * @package  Cookie
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class CookieManager extends \VuFind\Cookie\CookieManager
{
    /**
     * The name of the session cookie
     *
     * @var string
     */
    protected $sessionName;

    /**
     * Constructor
     *
     * @param array  $cookies     Cookie array to manipulate (e.g. $_COOKIE)
     * @param string $path        Cookie base path (default = /)
     * @param string $domain      Cookie domain
     * @param bool   $secure      Are cookies secure only? (default = false)
     * @param string $sessionName Session cookie name (if null defaults to PHP
     * settings)
     */
    public function __construct($cookies, $path = '/', $domain = null,
                                $secure = false, $sessionName = null
    ) {
        $this->cookies = $cookies;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->sessionName = $sessionName;
    }

    /**
     * Get the name of the cookie
     *
     * @return mixed
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }
}
