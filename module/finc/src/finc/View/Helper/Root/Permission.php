<?php
/**
 * Permissions view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
use Zend\View\Exception\RuntimeException,
    ZfcRbac\Service\AuthorizationServiceAwareInterface,
    ZfcRbac\Service\AuthorizationServiceAwareTrait;

/**
 * Permissions view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Permission extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Authentication manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected $manager;

    /**
     * Authorization object
     *
     * @var null|\ZfcRbac\Service\AuthorizationService
     */
    protected $auth;
    
    /**
     * Constructor
     *
     * @param \VuFind\Auth\Manager                       $manager Authentication manager
     * @param null|\ZfcRbac\Service\AuthorizationService $auth    AuthorizationService
     */
    public function __construct(\VuFind\Auth\Manager $manager, $auth = null)
    {
        $this->manager = $manager;
        $this->auth = $auth;
    }

    /**
     * Checks if a given permission is granted
     * 
     * @param string $permission Permission to be checked
     * 
     * @return bool
     */
    public function checkPermission ($permission)
    {
        if (!($user = $this->isLoggedIn())) {
            return false;
        }

        return $this->auth != null ? $this->auth->isGranted($permission) : false;
    }
    
    /**
     * Get manager
     *
     * @return \VuFind\Auth\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Checks whether the user is logged in.
     *
     * @return \VuFind\Db\Row\User|bool Object if user is logged in, false
     * otherwise.
     */
    public function isLoggedIn()
    {
        return $this->getManager()->isLoggedIn();
    }
}
