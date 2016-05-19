<?php
/**
 * Finc specific LiberoDing trait providing all the functions necessary for
 * communicating with the LiberoDing.
 *
 * PHP version 5
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace finc\ILS\Driver;
use VuFind\Exception\ILS as ILSException,
    Zend\Log\LoggerAwareInterface as LoggerAwareInterface;

/**
 * Finc specific LiberoDing trait providing all the functions necessary for
 * communicating with the LiberoDing.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
trait LiberoDingTrait
{
    /**
     * Get connection timeout of Libero request.
     *
     * @return int Connection timeout
     * @access protected
     */
    protected function getConnectTimeout ( $connectTimeout = 500 )
    {
        return $test = (isset($this->config['LiberoDing']['connectTimeout'])
            && is_numeric($this->config['LiberoDing']['connectTimeout']))
            ? $this->config['LiberoDing']['connectTimeout']
            : $connectTimeout;
    }

    /**
     * Get response timeout of Libero request.
     *
     * @return int Response timeout.
     * @access protected
     */
    protected function getResponseTimeout( $responseTimeout = 1000 )
    {
        return (isset($this->config['LiberoDing']['responseTimeout'])
            && is_numeric($this->config['LiberoDing']['responseTimeout']))
            ? $this->config['LiberoDing']['responseTimeout']
            : $responseTimeout;
    }

    /**
     * gets the webscraper url from config
     *
     * @return string
     * @throws Exception if not defined
     */
    protected function getWebScraperUrl()
    {
        if (!isset($this->config['LiberoDing']['webScraperUrl'])) {
            throw new \Exception('no webscraper url defined');
        }

        return $this->config['LiberoDing']['webScraperUrl'];
    }

    /**
     * gets the databasename from config
     *
     * @return string
     * @throws Exception if not defined
     */
    protected function getDbName()
    {
        if (!isset($this->config['LiberoDing']['databaseName'])) {
            throw new \Exception('no database name defined');
        }

        return $this->config['LiberoDing']['databaseName'];
    }

    /**
     * Check if there exists a connection to a url.
     *
     * @access public
     * @return boolean             Retruns true if a connection exists.
     */
    public function checkLiberoDingConnection ()
    {
        $http_header['User-Agent']
            = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';

        $params = array(
            'dbName' => $this->getDbName(),
            'connectionTimeout' => $this->getConnectTimeout(),
            'soTimeout' => $this->getResponseTimeout()
        );

        try {
            $result = $this->httpService->post(
                $this->getWebScraperUrl() .'liberoPing.jsp',
                http_build_query($params),
                'application/json; charset=UTF-8',
                null,
                $http_header
            );
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // log error for debugging
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received'
            );
            return false;
        }

        // get response as array
        $details = json_decode($result->getBody(), true);

        return !($details['liberoPing']["soTimeout"] || $details['liberoPing']["connectionTimeout"]);
    }

    /**
     * Get Libero system messages for a given patron
     *
     * @param $patron
     * @return bool
     * @throws Exception
     * @throws ILSException
     */
    protected function getSystemMessages($patron)
    {
        $params                 = $this->_getLiberoDingRequestParams();
        $params['memberCode']   = $patron['cat_username'];
        $params['password']     = $patron['cat_password'];

        try {
            $result = $this->httpService->get(
                $this->getWebScraperUrl() .'getMySystemMessages.jsp',
                $params,
                null,
                $this->_getLiberoDingRequestHeaders()
            );
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // log error for debugging
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received'
            );
            return false;
        }

        return $this->_getLiberoDingResult($result, 'getMySystemMessages');
    }

    /**
     * Remove libero system messages. No native function in vufind.
     *
     * @param array  $inval      Associative array of key => value. Keys are:
     *                           dbName : databaseName of libero
     *                           memberCode : User ID returned by patronLogin
     *                           password : password of user
     * @param array $messageIdList  Comma separated list of IDs which have to remove.
     * @param string $toDate     Deadline till all dates should be removed before
     *
     * @return boolean
     */
    public function removeMySystemMessages($patron, $messageIdList = null, $toDate = null) {

        $params                 = $this->_getLiberoDingRequestParams();
        $params['memberCode']   = $patron['cat_username'];
        $params['password']     = $patron['cat_password'];

        if (!is_null($messageIdList)) {
            $params['messageIdList'] = implode(',', $messageIdList);
        }
        if (!is_null($toDate)) {
            $params['toDate'] = $toDate;
        }

        try {
            $result = $this->httpService->get(
                $this->getWebScraperUrl() .'removeMySystemMessages.jsp',
                $params,
                null,
                $this->_getLiberoDingRequestHeaders()
            );
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // log error for debugging
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received'
            );
            return false;
        }

        return $this->_getLiberoDingResult($result, 'removeMySystemMessages');
    }

    /**
     * Change values of users profile.
     *
     * @param array $inval Associative array of key => value. Keys are:
     *     - memberCode   : User ID returned by patronLogin
     *     - street       : street and number
     *     - additional   : optional address value
     *     - city         : city/village
     *     - zipCode      : location zip code
     *     - emailAddress : email address
     *     - reason       : reason of change
     * @return boolean true OK, false FAIL
     * @access public
     */
    public function setMyProfile($inval, $patron)
    {
        $map = self::_profileDataMapper(true);

        $params                 = $this->_getLiberoDingRequestParams();
        $params['memberCode']   = $patron['cat_username'];
        $params['password']     = $patron['cat_password'];

        $data = [];
        if (is_array($inval) && (count($inval) > 0)) {
            foreach ($inval as $k => $v) {
                if (isset($map[$k])) {
                    $data[$map[$k]] = $v;
                } else {
                    $data[$k] = $v;
                }
            }
        }

        $params = array_merge($params, $data);

        try {
            $result = $this->httpService->get(
              $this->getWebScraperUrl() .'setMyProfile.jsp',
              $params,
              null,
              $this->_getLiberoDingRequestHeaders()
            );
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // log error for debugging
            $this->debug(
              'HTTP status ' . $result->getStatusCode() .
              ' received'
            );
            return false;
        }

        return $this->_getLiberoDingResultBool($result);
    }

    /**
     * Returns Array with profile fields that are never allowed to be edited
     *
     * @return array
     */
    public function getIgnoredProfileFields()
    {
        return isset($this->config['LiberoDing']['ignoredProfileFields']) ?
          $this->config['LiberoDing']['ignoredProfileFields'] : [];
    }

    /**
     * Returns Array with user groups not allowed to edit profile data
     *
     * @return array
     */
    public function getRestrictedUserGroups()
    {
        return isset($this->config['LiberoDing']['restrictedUserGroups']) ?
            $this->config['LiberoDing']['restrictedUserGroups'] : [];
    }

    /**
     * This method queries the LiberoDing-ILS for a patron's current profile
     * information.
     *
     * @param array   $patron Patron array returned by patronLogin method.
     * @param boolean $mapped Flag whether the response should be mapped or not
     *                        (default true)
     *
     * @return array An associative array
     * @see For content variables see method _profileDataMapper
     */
    protected function getLiberoDingProfile($patron, $mapped = true)
    {
        $params                 = $this->_getLiberoDingRequestParams();
        $params['memberCode']   = $patron['cat_username'];
        $params['password']     = $patron['cat_password'];

        try {
            $result = $this->httpService->get(
                $this->getWebScraperUrl() .'getMyProfile.jsp',
                $params,
                null,
                $this->_getLiberoDingRequestHeaders()
            );
        } catch (\Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // log error for debugging
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received'
            );
            return false;
        }

        if ($mapped) {
            // define of disabled fields
            $mappeddata = array();
            $map = self::_profileDataMapper();
            $data = $this->_getLiberoDingResult($result, 'getMyProfile');

            foreach ($data as $key => $value) {
                if ($key == 'disabledInputs') {
                    // map it
                    foreach ($data['disabledInputs'] as $fields) {
                        if (isset($map[$fields])){
                            $mappeddata['disabled'][$map[$fields]] = true;
                        }
                    }
                } else {
                    if (isset($map[$key])){
                        $mappeddata[($map[$key])] = $value;
                    }
                }
            }
            return $mappeddata;
        }

        return $this->_getLiberoDingResult($result, 'getMyProfile');
    }


    /**
     * Get a mapping table to exchange general terms
     * of Libero and VuFind.
     *
     * @param boolean   $reverse  If true swap key and value pairs.
     *
     * @return array    Return array of mappings.
     * @access private
     */
    private static function _profileDataMapper( $reverse = false )
    {
        $array = array(
            "GNAM" => 'firstname',
            "SUR" => 'lastname',
            "FOA" => 'title',
            "HPHONE" => "phone",
            "BPHONE" => 'phone2',
            "EMAIL" => 'email',
            "group" => 'group',
            "ADDR1" => 'address1',
            "ADDR2" => 'additional',
            "ADDR3" => 'city',
            "ADDR4" => 'zip',
            "ADDR5" => 'country',
            "RADR1" => 'street2',
            "RADR2" => 'additional2',
            "RADR3" => 'city2',
            "RADR4" => 'zip2',
            "RADR5" => 'country2',
            "ALTA1" => 'street3',
            "ALTA2" => 'additional3',
            "ALTA3" => 'city3',
            "ALTA4" => 'zip3',
            "ALTA5" => 'country3'
        );

        return ($reverse === true) ? array_flip($array) : $array;
    }

    /**
     * Private Helper function to return LiberoDing request parameters
     *
     * @return array
     * @throws Exception
     */
    private function _getLiberoDingRequestParams()
    {
        return [
            'dbName'            => $this->getDbName(),
            'connectionTimeout' => $this->getConnectTimeout(),
            'soTimeout'         => $this->getResponseTimeout()
        ];
    }

    /**
     * Private Helper function to return LiberoDing request headers
     *
     * @return array
     */
    private function _getLiberoDingRequestHeaders()
    {
        return [
            'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
        ];
    }

    /**
     * Check the given result for key errorcode and return the requested resultKey
     * value
     *
     * @param $result array
     * @return array
     */
    private function _getLiberoDingResult($result, $resultKey)
    {
        // get result as array
        $details = json_decode($result->getBody(), true);

        if (!is_array($details) || !isset($details["errorcode"]) || $details["errorcode"] != 0) {
            return false;
        }

        return isset($details[$resultKey]) ? $details[$resultKey] : [];
    }

    /**
     * Check the given result for key errorcode and return the requested resultKey
     * value
     *
     * @param $result array
     * @return array
     */
    private function _getLiberoDingResultBool($result)
    {
        // get result as array
        $details = json_decode($result->getBody(), true);

        if (!is_array($details) || !isset($details["errorcode"]) || $details["errorcode"] != 0) {
            return false;
        }

        return true;
    }
}