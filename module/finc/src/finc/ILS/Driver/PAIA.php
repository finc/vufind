<?php
/**
 * ILS Driver for VuFind to get information from PAIA
 *
 * Authentication in this driver is handled via LDAP, not via normal PICA!
 * First check local vufind database, and if no user is found, check LDAP.
 * LDAP configuration settings are taken from vufinds config.ini
 *
 * PHP version 5
 *
 * Copyright (C) Oliver Goldschmidt, Magda Roos, Till Kinstler 2013, 2014.
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
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */

namespace finc\ILS\Driver;
use DOMDocument, VuFind\Exception\ILS as ILSException;
//use VuFind\ILS\Driver\DAIA as DAIA;

/**
 * ILS Driver for VuFind to get information from PICA
 *
 * Holding information is obtained by DAIA, so it's not necessary to implement those
 * functions here; we just need to extend the DAIA driver.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
class PAIA extends DAIA
{
    private $_username;
    private $_password;

    protected $baseURL;
    protected $paiaURL;

    /**
     * Constructor
     *
     * @access public
     */
    public function init()
    {
        parent::init();

        if (!(isset($this->config['PAIA']['baseUrl']))) {
            throw new ILSException('PAIA/URL configuration needs to be set.');
        }

        $this->paiaURL = $this->config['PAIA']['baseUrl'];

    }

    // public functions implemented to satisfy Driver Interface

    /*

        cancelHolds X
        checkRequestIsValid
        findReserves
        getCancelHoldDetails
        getCancelHoldLink
        getConfig
        getCourses
        getDefaultPickUpLocation
        getDepartments
        getFunds
        getHolding
        getHoldings -- DEPRECATED
        getHoldLink
        getInstructors
        getMyFines
        getMyHolds
        getMyProfile
        getMyTransactions
        getNewItems
        getOfflineMode
        getPickUpLocations
        getPurchaseHistory
        getRenewDetails
        getStatus
        getStatuses
        getSuppressedAuthorityRecords
        getSuppressedRecords
        hasHoldings
        loginIsHidden
        patronLogin
        placeHold
        renewMyItems
        renewMyItemsLink

    */

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron's username
     * @param string $password The patron's login password
     *
     * @throws ILSException
     * @return mixed          Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($username, $password)
    {
        if ($username == '' || $password == '') {
            return new PEAR_Error('Invalid Login, Please try again.');
        }
        $this->_username = $username;
        $this->_password = $password;

        try {
            return $this->_paiaLogin($username, $password);
        } catch (ILSException $e) {
            throw new ILSException($e->getMessage());
        }
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $user The patron array
     *
     * @return mixed      Array of the patron's profile data on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyProfile($user)
    {
        // we are already having all possible PAIA user data in $user
        $userinfo['firstname'] = $user['firstname'];
        $userinfo['lastname'] = $user['lastname'];
        // fill up all possible return values
        $userinfo['address1'] = null;
        $userinfo['address2'] = null;
        $userinfo['city'] = null;
        $userinfo['country'] = null;
        $userinfo['zip'] = null;
        $userinfo['phone'] = null;
        $userinfo['group'] = null;
        return $userinfo;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's transactions on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyTransactions($patron)
    {
        $loans_response = $this->_getAsArray('/core/'.$patron['cat_username'].'/items');
        $holds = count($loans_response['doc']);
        for ($i = 0; $i < $holds; $i++) {
            if ($loans_response['doc'][$i]['status'] == '3') { //status: held (the document is on loan by the patron)
                // TODO: set renewable dynamically (not yet supported by PAIA)
                $renewable = true;
                $renew_details = $loans_response['doc'][$i]['item'];
                /*
                 * if ($loans_response['doc'][$i]['cancancel'] == 1) {
                 *   $renewable = true;
                 *   $renew_details = $loans_response['doc'][$i]['item'];
                 * } */

                // hook for retrieving alternative ItemId in case PAIA does not
                // the needed id
                $alternativeItemId = $this->_getAlternativeItemId($loans_response['doc'][$i]['item']);

                if ($loans_response['doc'][$i]['status'] == '4') { //status: provided (the document is ready to be used by the patron)
                    $message = "hold_available";
                }

                $transList[] = array(
                    'id'             => $alternativeItemId ? $alternativeItemId : $loans_response['doc'][$i]['item'],
                    'duedate'        => $loans_response['doc'][$i]['endtime'],
                    'dueTime'        => null,
                    'dueStatus'      => null,
                    'barcode'        => $loans_response['doc'][$i]['item'],
                    'renew'          => $loans_response['doc'][$i]['renewals'],
                    'renewLimit'     => "1",
                    'request'        => $loans_response['doc'][$i]['queue'],
                    'volume'         => null,
                    'publication_year' => null,
                    'renewable'      => $renewable,
                    'renew_details'  => $renew_details,
                    'message'        => $message ? $message : $loans_response['doc'][$i]['label'],
                    'title'          => $loans_response['doc'][$i]['about'],
                    'item_id'        => $loans_response['doc'][$i]['item'],
                    'institution_name' => null,
                    'isbn'           => null,
                    'issn'           => null,
                    'oclc'           => null,
                    'upc'            => null,
                    'callnumber'     => $loans_response['doc'][$i]['label'], //non-standard
                    'borrowingLocation' => $loans_response['doc'][$i]['storage'],
                );
            }
        }
        return $transList;
    }

    /**
     * This method renews a list of items for a specific patron.
     *
     * @param array $details - An associative array with two keys:
     *      patron - array returned by patronLogin method
     *      details - array of values returned by the getRenewDetails method
     *                identifying which items to renew
     *
     * @return  array - An associative array with two keys:
     *     blocks - An array of strings specifying why a user is blocked from
     *              renewing (false if no blocks)
     *     details - Not set when blocks exist; otherwise, an array of
     *               associative arrays (keyed by item ID) with each subarray
     *               containing these keys:
     *                  success – Boolean true or false
     *                  new_date – string – A new due date
     *                  new_time – string – A new due time
     *                  item_id – The item id of the renewed item
     *                  sysMessage – A system supplied renewal message (optional)
     */
    public function renewMyItems($details)
    {
        $it = $details['details'];
        $items = array();
        foreach ($it as $item) {
            $items[] = array('item' => stripslashes($item));
        }
        $patron = $details['patron'];
        $post_data = array("doc" => $items);
        $array_response = $this->_postAsArray('/core/'.$patron['cat_username'].'/renew', $post_data);

        $details = array();

        if (array_key_exists('error', $array_response)) {
            $details[] = array('success' => false, 'sysMessage' => $array_response['error_description']);
        }
        else {
            $elements = $array_response['doc'];
            foreach ($elements as $element) {
                $item_id = $element['item'];
                if (array_key_exists('error', $element)) {
                    $details[$item_id] = array(
                        'success' => false,
                        'sysMessage' => $element['error']
                    );
                }
                elseif ($element['status'] == '3') {
                    $details[$item_id] = array(
                        'success'  => true,
                        'new_date' => $element['endtime'],
                        'item_id'  => 0,
                        'sysMessage' => 'Successfully renewed'
                    );
                }
                else {
                    $details[$item_id] = array(
                            'success'  => false,
                            'new_date' => $element['endtime'],
                            'item_id'  => 0,
                            'sysMessage' => 'Request rejected'
                    );
                }
            }
        }
        $returnArray = array('blocks' => false, 'details' => $details);
        return $returnArray;
    }

    /**
     * This method returns a string to use as the input form value for renewing
     * each hold item. (optional, but required if you implement the
     * renewMyItems method) Not supported prior to VuFind 1.2
     *
     * @param $checkOutDetails - One of the individual item arrays returned by
     *                           the getMyTransactions method
     * @return string - A string to use as the input form value for renewing
     *                  each item; you can pass any data that is needed by your
     *                  ILS to identify the transaction to renew – the output
     *                  of this method will be used as part of the input to the
     *                  renewMyItems method.
     */
    public function getRenewDetails($checkOutDetails) {
        return($checkOutDetails['renew_details']);
    }

    /**
     * This method cancels a list of holds for a specific patron.
     *
     * @param array $cancelDetails - An associative array with two keys:
     *      patron - array returned by the driver's patronLogin method
     *      details - an array of strings returned by the driver's
     *        getCancelHoldDetails method
     *
     * @return array - Associative array containing:
     *      count – The number of items successfully cancelled
     *      items – Associative array where key matches one of the item_id
     *              values returned by getMyHolds and the value is an
     *              associative array with these keys:
     *                success – Boolean true or false
     *                status – A status message from the language file
     *                         (required – VuFind-specific message,
     *                          subject to translation)
     *                sysMessage - A system supplied failure message
     */
    public function cancelHolds($cancelDetails)
    {
        $it = $cancelDetails['details'];
        $items = array();
        foreach ($it as $item) {
            $items[] = array('item' => stripslashes($item));
        }
        $patron = $cancelDetails['patron'];
        $post_data = array("doc" => $items);

        $array_response = $this->_postAsArray('/core/'.$patron['cat_username'].'/cancel', $post_data);
        $details = array();

        if (array_key_exists('error', $array_response)) {
            $details[] = array(
                'success' => false,
                'status' => $array_response['error_description'],
                'sysMessage' => $array_response['error']
            );
        }
        else {
            $count = 0;
            $elements = $array_response['doc'];
            foreach ($elements as $element) {
                $item_id = $element['item'];
                if ($element['error']) {
                    $details[$item_id] = array(
                        'success' => false,
                        'status' => $element['error'],
                        'sysMessage' => 'Cancel request rejected'
                    );
                }
                else {
                    $details[$item_id] = array(
                        'success' => true,
                        'status' => 'Success',
                        'sysMessage' => 'Successfully cancelled'
                    );
                    $count++;
                }
            }
        }
        $returnArray = array('count' => $count, 'items' => $details);

        return $returnArray;
    }

    /**
     * This method returns a string to use as the input form value for
     * cancelling each hold item. (optional, but required if you
     * implement cancelHolds). Not supported prior to VuFind 1.2
     *
     * @param $checkOutDetails - One of the individual item arrays returned by
     *                           the getMyHolds method
     * @return string - A string to use as the input form value for cancelling
     *                  each hold item; you can pass any data that is needed
     *                  by your ILS to identify the hold – the output of this
     *                  method will be used as part of the input to the
     *                  cancelHolds method.
     */
    public function getCancelHoldDetails($checkOutDetails) {
        return($checkOutDetails['cancel_details']);
    }

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's fines on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyFines($patron)
    {
        $fees_response = $this->_getAsArray('/core/'.$patron['cat_username'].'/fees');

        $fineList = array();
        foreach ($fees_response['fee'] as $fine) {
            $alternativeItemId = $this->_getAlternativeItemId($fine['item']);
            $fineList[] = array(
                "id"       => $alternativeItemId ? $alternativeItemId : $fine['item'],
                "amount"   => $fine['amount'],
                "checkout" => "",
                "title"    => $fine['about'],
                "createdate"  => $fine['date'],
                "duedate"  => "",
                "fine"     => $fine['feetype'],
                //"balance"  => "",
            );
        }
        $fineList[] = array(
            "balance"  => $fees_response['amount']
        );

        return $fineList;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's holds on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyHolds($patron)
    {
        $loans_response = $this->_getAsArray('/core/'.$patron['cat_username'].'/items');
        $holds = count($loans_response['doc']);
        for ($i = 0; $i < $holds; $i++) {
            // TODO: get date of creation from a reservation
            // this is not yet supported by PAIA
            if ($loans_response['doc'][$i]['status'] == '1' || $loans_response['doc'][$i]['status'] == '2') {
                // get PPN from PICA catalog since it is not part of PAIA
                $alternativeItemId = $this->_getAlternativeItemId($loans_response['doc'][$i]['label']);
                $cancel_details = false;
                if ($loans_response['doc'][$i]['cancancel'] == 1) {
                    $cancel_details = $loans_response['doc'][$i]['item'];
                }
                // As long as PAIA-Server does not set cancancel, always populate $cancel_details
                $cancel_details = $loans_response['doc'][$i]['item'];

                $transList[] = array(
                    'type'           => $loans_response['doc'][$i]['status'],
                    'id'             => $alternativeItemId ? $alternativeItemId : $loans_response['doc'][$i]['item'],
                    'location'       => $loans_response['doc'][$i]['storage'],
                    'reqnum'         => null,
                    'expire'         => isset($loans_response['doc'][$i]['endtime']) ? $loans_response['doc'][$i]['endtime'] : "",
                    'create'         => $loans_response['doc'][$i]['starttime'],
                    'position'       => null,
                    'available'      => null,
                    'item_id'        => $loans_response['doc'][$i]['item'],
                    'volume'         => null,
                    'publication_year' => null,
                    'title'          => $loans_response['doc'][$i]['about'],
                    'isbn'           => null,
                    'issn'           => null,
                    'oclc'           => null,
                    'upc'            => null,
                    'message'        => $loans_response['doc'][$i]['label'],
                    'callnumber'     => $loans_response['doc'][$i]['label'],
                    'cancel_details' => $cancel_details,
                );
            }
        }
        return $transList;
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or a PEAR error on failure of support classes
     *
     * Make a request on a specific record
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function placeHold($holdDetails)
    {
        $item = $holdDetails['item_id'];

        $items = array();
        $items[] = array('item' => stripslashes($item));
        $patron = $holdDetails['patron'];
        $post_data = array("doc" => $items);
        $array_response = $this->_postAsArray('/core/'.$patron['cat_username'].'/request', $post_data);
        $details = array();

        if (array_key_exists('error', $array_response)) {
            $details = array(
                'success' => false,
                'sysMessage' => $array_response['error_description']
            );
        }
        else {
            $elements = $array_response['doc'];
            foreach ($elements as $element) {
                if (array_key_exists('error', $element)) {
                    $details = array(
                        'success' => false,
                        'sysMessage' => $element['error']
                    );
                }
                else {
                    $details = array(
                        'success' => true,
                        'sysMessage' => 'Successfully requested'
                    );
                }
            }
        }
        return $details;
    }

    /**
     * Get Funds
     *
     * Return a list of funds which may be used to limit the getNewItems list.
     *
     * @return array An associative array with key = fund ID, value = fund name.
     * @access public
     */
    public function getFunds()
    {
        // If you do not want or support such limits, just return an empty
        // array here and the limit control on the new item search screen
        // will disappear.
        return array();
    }

    /**
     * Public Function which changes the password in the library system
     * (not supported prior to VuFind 2.4)
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with patron information.
     * @access public
     */
    public function changePassword($patron, $oldPassword, $newPassword)
    {
        $post_data = array(
            "patron"       => $patron['username'],
            "username"     => $patron['firstname']." ".$patron['lastname'],
            "old_password" => $oldPassword,
            "new_password" => $newPassword);

        $array_response = $this->_postAsArray('/auth/change', $post_data);

        $details = array();

        if (array_key_exists('error', $array_response)) {
            $details = array(
                'success' => false,
                'status' => $array_response['error'],
                'sysMessage' => $array_response['error_description']
            );
        }
        else {
            $element = $array_response['patron'];
            if (array_key_exists('error', $element)) {
                $details = array(
                    'success' => false,
                    'status' => 'Failure changing password',
                    'sysMessage' => $element['error']
                );
            }
            else {
                $details = array(
                    'success' => true,
                    'status' => 'Successfully changed'
                );
            }
        }
        return $details;
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.  The driver must not add new options to the return array
     * based on this data or other areas of VuFind may behave incorrectly.
     *
     * @throws ILSException
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        // How to get valid PickupLocations for a PICA LBS?
        return array();
    }

    /**
     * Get Default Pick Up Location
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.
     *
     * @return string       The default pickup location for the patron.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        return false;
    }


    // private functions to connect to PAIA

    /**
     * post something to a foreign host
     *
     * @param string $file         POST target URL
     * @param string $data_to_send POST data
     *
     * @return string              POST response
     * @access private
     */
    private function _postit($file, $data_to_send, $access_token = null)
    {
        // json-encoding
        $postData = stripslashes(json_encode($data_to_send));

        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $this->paiaURL . $file);
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_POSTFIELDS, $postData);
        if (isset($access_token)) {
            curl_setopt($http, CURLOPT_HTTPHEADER, array('Content-type: application/json; charset=UTF-8', 'Authorization: Bearer ' .$access_token));
        } else {
            curl_setopt($http, CURLOPT_HTTPHEADER, array('Content-type: application/json; charset=UTF-8'));
        }
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($http);

        curl_close($http);

        return $data;
    }

    private function _getit($file, $access_token)
    {
        $http = curl_init();
        curl_setopt($http, CURLOPT_URL, $this->paiaURL . $file);
        curl_setopt($http, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' .$access_token, 'Content-type: application/json; charset=UTF-8'));
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($http);
        curl_close($http);
        return $data;
    }

    private function _getAsArray($file)
    {
        $pure_response = $this->_getit($file, $_SESSION['paiaToken']);
        $json_start = strpos($pure_response, '{');
        $json_response = substr($pure_response, $json_start);
        $loans_response = json_decode($json_response, true);

        // if the login auth token is invalid, renew it (this is possible unless the session is expired)
        if (isset($loans_response['error']) && $loans_response['code'] == '401') {
            //TODO: handling of expired auth token
            $this->debug("Auth token invalid - returning empty array");
            return array();
        }

        return $loans_response;
    }

    private function _postAsArray($file, $data)
    {
        $pure_response = $this->_postit($file, $data, $_SESSION['paiaToken']);
        $json_start = strpos($pure_response, '{');
        $json_response = substr($pure_response, $json_start);
        $loans_response = json_decode($json_response, true);

        // if the login auth token is invalid, renew it (this is possible unless the session is expired)
        if ($loans_response['error'] && $loans_response['code'] == '401') {
            //TODO: handling of expired auth token
            $this->debug("Auth token invalid - returning empty array");
            return array();
        }

        return $loans_response;
    }

    /**
     * private authentication function
     * use PAIA for authentication
     *
     * @return mixed Associative array of patron info on successful login,
     * null on unsuccessful login, PEAR_Error on error.
     * @access private
     */
    private function _paiaLogin($username, $password)
    {
        $post_data = array(
                            "username" => $username,
                            "password" => $password,
                            "grant_type" => "password",
                            "scope" => "read_patron read_fees read_items write_items change_password"
                          );
        $login_response = $this->_postit('/auth/login', $post_data);

        $json_start = strpos($login_response, '{');
        $json_response = substr($login_response, $json_start);
        $array_response = json_decode($json_response, true);

        if (array_key_exists('access_token', $array_response)) {
            $_SESSION['paiaToken'] = $array_response['access_token'];
            if (array_key_exists('patron', $array_response)) {
                $patron = $this->_getUserDetails($array_response['patron']);
                $patron['cat_username'] = $array_response['patron'];
                $patron['cat_password'] = $password;
                return $patron;
            }
            else {
                throw new ILSException('Login credentials accepted, but got no patron ID?!?');
            }
        }
        else if (array_key_exists('error', $array_response)) {
            throw new ILSException($array_response['error'].": ".$array_response['error_description']);
        }
        else throw new ILSException('Unknown error! Access denied.');
    }

    /**
     * Support method for _paiaLogin() -- load user details into session and return
     * array of basic user data.
     *
     * @param array $patron                    patron ID
     *
     * @return array
     * @access private
     */
    private function _getUserDetails($patron)
    {
        $pure_response = $this->_getit('/core/' . $patron, $_SESSION['paiaToken']);
        $json_start = strpos($pure_response, '{');
        $json_response = substr($pure_response, $json_start);
        $user_response = json_decode($json_response, true);

        // if the login auth token is invalid, renew it (this is possible unless the session is expired)
        if (isset($user_response['error']) && $user_response['code'] == '401') {
            //TODO: handling of expired auth token
            $this->debug("Auth token invalid - returning empty userdetails");
            return array();
        }

        $username = $user_response['name'];
        $nameArr = explode(',', $username);
        $firstname = $nameArr[1];
        $lastname = $nameArr[0];

        $user = array();
        $user['id'] = $patron;
        $user['firstname'] = $firstname;
        $user['lastname'] = $lastname;
        $user['email'] = isset($user_response['email']) ? $user_response['email'] : "";
        $user['major'] = null;
        $user['college'] = null;

        return $user;
    }

    /**
     * Support method to retrieve needed ItemId in case PAIA-resposne does not
     * contain it
     *
     * @param string $id                    itemId
     *
     * @return string $id
     * @access private
     */
    private function _getAlternativeItemId($id) {
        return $id;
    }

    /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     * @access public
     */
    public function getConfig($function)
    {
        if (isset($this->config[$function]) ) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
        return $functionConfig;
    }
}
?>