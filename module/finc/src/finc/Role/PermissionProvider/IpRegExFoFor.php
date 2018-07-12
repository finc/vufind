<?php
/**
 * IpRegExFoFor permission provider for VuFind.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
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
 * @package  Authorization
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace finc\Role\PermissionProvider;

/**
 * IpRegExFoFor permission provider for VuFind.
 *
 * @category VuFind
 * @package  Authorization
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class IpRegExFoFor extends \VuFind\Role\PermissionProvider\IpRegEx
{
    /**
     * Return an array of roles which may be granted the permission based on
     * the options.
     *
     * Checks server variable HTTP_X_FORWARDED_FOR
     *
     * @param mixed $options Options provided from configuration.
     *
     * @return array
     */
    public function getPermissions($options)
    {
        // Check if any regex matches....
        $ip = $this->request->getServer()->get('HTTP_X_FORWARDED_FOR') != null
            ? $this->request->getServer()->get('HTTP_X_FORWARDED_FOR')
            : $this->request->getServer()->get('REMOTE_ADDR');
        foreach ((array)$options as $current) {
            if (preg_match($current, $ip)) {
                // Match? Grant to all users (guest or logged in).
                return ['guest', 'loggedin'];
            }
        }

        //  No match? No permissions.
        return [];
    }
}
