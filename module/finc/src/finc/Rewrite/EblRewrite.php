<?php
/**
 * Ebl/ Schweitzer Rewrite service for VuFind.
 *
 * PHP version 5.3
 *
 * Copyright (C) Leipzig University Library 2016.
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
 * @package  Rewrite
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace finc\Rewrite;

use ZfcRbac\Service\AuthorizationServiceAwareTrait;

/**
 * Ebl / Schweitzer Rewrite service for VuFind.
 *
 * @category VuFind
 * @package  Rewrite
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class EblRewrite
{
    use AuthorizationServiceAwareTrait;

    /**
     * Config object
     *
     * @var object $config
     * @access private
     */
    private $config;

    /**
     *  Timestamp
     *
     * @var null/int $time
     * @access private
     */
    private $time = null;

    /**
     *  User identifier as hashed value
     *
     * @var null/string $user_id
     * @access private
     */
    private $userid = null;

    /**
     * Constructor
     *
     * @param $config
     * @access public
     *
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Resolve link for EBL
     *
     * @param string $link Link as url.
     * @param object $user User object
     *
     * @return string $link Link as url.
     * @access public
     * @throws \Exception No user object exists
     * @throws \Exception Authorization service missing
     */
    public function resolveLink($link, $user)
    {
        $this->accessPermission = 'access.EblLink';

        if (!isset($user->username) && strlen($user->username) > 0) {
            throw new \Exception('No user object exists');
        }
        $auth = $this->getAuthorizationService();
        if (!$auth) {
            throw new \Exception('Authorization service missing');
        }

        // Logged in user with no permission get resolver link for view of already
        // purchased e-books by institution
        // @link https://intern.finc.info/issues/1813#note-8
        $statusUser = ''; // Default value 'intern' equal to keep value blank
        if (!$auth->isGranted($this->accessPermission)) {
            $params['patrontype'] = 'extern';
            $statusUser = 'extern';
        }
        $params['userid'] = $this->getHashedUser($user->username);
        $params['tstamp'] = $this->getTimeStamp();
        $params['id'] = $this->getEblIdentifier(
            $params['userid'],
            $params['tstamp'],
            $statusUser
        );
        $query = http_build_query($params);
        return $link . '&' . $query;
    }

    /**
     * Get EBL identifier
     *
     * @param string $userid Hashed user identifier
     * @param int $timestamp Current timestamp of interaction
     * @param string $statususer Status of user internally|externally
     *
     * @return string
     * @access private
     */
    private function getEblIdentifier($userid, $timestamp, $statususer)
    {
        return sha1($userid . $timestamp . $this->getSecretKey() . $statususer);
    }

    /**
     * Get hashed user
     *
     * @param string $userid User identifier
     *
     * @return string
     * @access private
     */
    private function getHashedUser($userid)
    {
        return ($this->userid == null)
            ? hash('sha384', $userid) : $this->userid;
    }

    /**
     * Get secret key of provider Schweitzer
     *
     * @return string
     * @access private
     * @throws \Exception There is no secret key defined in configuration
     */
    private function getSecretKey()
    {
        if (isset($this->config->Ebl->secret_key)
            && strlen($this->config->Ebl->secret_key) > 0
        ) {
            return $this->config->Ebl->secret_key;
        }
        throw new \Exception('There is no secret key defined in configuration.');
    }

    /**
     * Get one timestamp per processing
     *
     * @return int|null
     * @access private
     */
    private function getTimeStamp()
    {
        return ($this->time == null) ? time() : $this->time;
    }
}