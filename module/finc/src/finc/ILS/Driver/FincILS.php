<?php
/**
 * Finc specific ILS Driver for VuFind, using PAIA and DAIA services.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
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
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace finc\ILS\Driver;

use Herrera\Json\Exception\Exception;
use VuFind\Exception\ILS as ILSException,
    VuFindSearch\Query\Query, VuFindSearch\Service as SearchService,
    ZfcRbac\Service\AuthorizationServiceAwareInterface,
    ZfcRbac\Service\AuthorizationServiceAwareTrait,
    Zend\Log\LoggerAwareInterface as LoggerAwareInterface,
    DateTime, DateInterval, DateTimeZone,
    Sabre\VObject;
use VuFind\Exception\ILS;
use Zend\Escaper\Escaper;

/**
 * Finc specific ILS Driver for VuFind, using PAIA and DAIA services.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class FincILS extends PAIA implements LoggerAwareInterface
{

    // vCard ADR is an ordered list of the following values
    // 0 - the post office box;
    // 1 - the extended address (e.g., apartment or suite number);
    // 2 - the street address;
    // 3 - the locality (e.g., city);
    // 4 - the region (e.g., state or province);
    // 5 - the postal code;
    // 6 - the country name;
    // (cf. https://tools.ietf.org/html/rfc6350#section-6.3.1)
    public static $vcard_address_parameter_map = array(
            'address1' => '2',
            'additional' => '1',
            'city' => '3',
            'country' => '6',
            'zip' => '5',
    );

    protected $root_username;
    protected $root_password;

    /**
     * Array that stores the mapping of VuFind record_id to the ILS-specific
     * identifier retrieved by @_getILSRecordId()
     *
     * @var array
     */
    private $_idMapper = [];

    /**
     * Identifier used for interaction with ILS
     *
     * @var string
     */
    protected $ilsIdentifier;

    /**
     * Array containing known dynamic fields that need to be extended by
     * indexExtension if used for search
     *
     * @var array
     */
    protected $dynamicFields = ['barcode'];

    /**
     * ISIL used for identifying the correct ILS-identifier if array is returned
     *
     * @var string
     */
    protected $isil;

    /**
     * Connection timeout in seconds used for _testILSConnection()
     *
     * @var int
     */
    protected $ilsTestTimeout = 1;

    /**
     * Flag to save online status.
     *
     * @var boolean
     */
    protected $isOnline = false;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * Connection used when searching for fincid
     *
     * @var SearchService
     */
    protected $searchService;

    /**
     * Date converter object
     *
     * @var \VuFind\Date\Converter
     */
    protected $dateConverter;

    /**
     * Main Config
     *
     * @var null|\Zend\Config\Config
     */
    protected $mainConfig;

    /**
     * Authorization object
     *
     * @var null|\ZfcRbac\Service\AuthorizationService
     */
    protected $auth;

    /**
     * Constructor
     *
     * @param \VuFind\Date\Converter $converter  Date converter
     * @param \VuFind\Record\Loader  $loader     Record loader
     * @param \Zend\Config\Config    $mainConfig VuFind main configuration (omit for
     * built-in defaults)
     */
    public function __construct(\VuFind\Date\Converter $converter, \Zend\Session\SessionManager $sessionManager,
        \VuFind\Record\Loader $loader, SearchService $ss, $mainConfig = null,
        $auth = null
    ) {
        parent::__construct($converter, $sessionManager);
        $this->recordLoader = $loader;
        $this->searchService = $ss;
        $this->mainConfig = $mainConfig;
        $this->auth = $auth;
    }

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        parent::init();

        // set the ILS-specific recordId for interaction with ILS

        // get the ILS-specific identifier
        if (!isset($this->config['DAIA']['ilsIdentifier'])) {
            $this->debug(
                "No ILS-specific identifier configured, setting ilsIdentifier=default."
            );
            $this->ilsIdentifier = "default";
        } else {
            $this->ilsIdentifier = $this->config['DAIA']['ilsIdentifier'];
        }

        // get PAIA root credentials if configured
        if (isset($this->config['PAIA']['root_username'])
            && isset($this->config['PAIA']['root_username'])
        ) {
            $this->root_username = $this->config['PAIA']['root_username'];
            $this->root_password = $this->config['PAIA']['root_password'];
        }

        // get ISIL from config if ILS-specific recordId is barcode for
        // interaction with ILS
        if (!isset($this->mainConfig['InstitutionInfo']['isil'])) {
            $this->debug("No ISIL defined in section InstitutionInfo in config.ini.");
            $this->isil = [];
        } else {
            $this->isil = $this->mainConfig['InstitutionInfo']['isil']->toArray();
        }

        // get General Settings
        // get ilsTestTimeout setting if set otherwise use default of 1 second
        $this->ilsTestTimeout = isset($this->config['General'])
            && isset($this->config['General']['ilsTestTimeout'])
            ? $this->config['General']['ilsTestTimeout'] : 1;

    }

    /**
     * This optional method (introduced in VuFind 1.4) gets the online status of the
     * ILS – “ils-offline” for systems where the main ILS is offline, “ils-none” for
     * systems which do not use an ILS, false for systems that are fully online. If
     * not implemented, the value defaults to false
     *
     * @return bool
     */
    public function getOfflineMode()
    {
        try {
            if ($this->isOnline) {
                // prior test succeeded
                return false;
            }
            // test again
            $this->_testILSConnections();
            return false;
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
            return "ils-offline";
        }
    }

    /**
     * Check if email hold is valid
     *
     * This is responsible for determining if an item is requestable
     *
     * @param string $id     The Bib ID
     * @param array  $data   An Array of item data
     * @param patron $patron An array of patron data
     *
     * @return bool True if request is valid, false if not
     */
    public function checkEmailHoldIsValid($id, $data, $patron)
    {
        // without item_id we cannot check if the item is available for email holding
        if (!isset($data['item_id'])) {
            return false;
        }

        // get status information
        $status = $this->getStatus($id);
        foreach ($status as $item) {
            // search for status information for given item_id
            if (isset($item['item_id']) && $item['item_id'] == $data['item_id']) {
                return $this->checkEmailHoldValidationCriteria($item);
            }
        }

        // if we have come so far no criteria matched and email holds are not allowed
        return false;
    }

    /**
     * Helper for checking the given item for the configured Email Hold validation
     * criteria
     *
     * @param $item
     * @return bool
     */
    protected function checkEmailHoldValidationCriteria($item)
    {
        $criteria = $this->getEmailHoldValidationCriteria();
        foreach($criteria as $key => $value) {
            if (isset($item[$key])
                && ((is_array($item[$key]) && in_array($value, $item[$key]))
                    || ($value == $item[$key]))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether hold should be placed via Email for the current item based on
     * settings in FincILS.ini.
     *
     * @param $item
     * @return array
     */
    protected function getEmailHoldValidationCriteria()
    {
        $criteria = [];
        if (isset($this->config['EmailHold']['emailHoldValidationCriteria'])) {
            foreach ($this->config['EmailHold']['emailHoldValidationCriteria'] as $value) {
                $criteria[
                explode('::', $value)[0]
                ] = explode('::', $value)[1];
            }
        }
        return $criteria;
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     *                           method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.  The driver must not add new options to the return array
     * based on this data or other areas of VuFind may behave incorrectly.
     *
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     */
    public function getPickupLocations($patron = null, $holdDetails = null)
    {
        if (isset($details['id']) && isset($details['item_id'])) {
            // getHolding information for given item_id
            $info = $this->getHolding($details['id']);
            // now extract pickupLocations for each returned item
            foreach ($info as $item) {
                if (isset($item['item_id'])
                    && $item['item_id'] == $details['item_id']
                ) {
                    return isset($item['location'])
                        ? [[
                            'locationID' =>
                                ($item['locationid']!=''
                                    ? $item['locationid']
                                    : $item['location']
                                ),
                            'locationDisplay' => $item['location']
                        ]]
                        : [];
                }
            }
        }
        return [];
    }

    /*********************************************
     * Custom DAIA methods
     *********************************************/

    /**
     * PAIA support method - try to find fincid for last segment of PAIA id
     *
     * @param string $id     itemId
     * @param string $idType id type to override ILS settings
     *
     * @return string $id
     */
    protected function getAlternativeItemId($id, $idType = null)
    {
        $array = explode(":", $id);
        return $this->_getFincId(end($array), $idType);
    }

    /**
     * Get Status
     *
     * Wrapper implementation of @getStatus($id) to retrieve the status
     * information of a certain record by using ILS-specific identifier.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        if ($this->_hasILSData($id)) {
            return $this->_replaceILSId(
                parent::getStatus($this->_getILSRecordId($id)), $id
            );
        } else {
            return $this->_getStaticStatus($id);
        }
    }

    /**
     * Get Statuses
     *
     * Wrapper implementation of @getStatuses($id) to retrieve status
     * information for several records by using ILS-specific identifier.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return array    An array of status information values on success.
     */
    public function getStatuses($ids)
    {
        $retval = [];

        foreach ($ids as $num => $id) {
            if (!$this->_hasILSData($id)) {
                $retval[] = $this->_getStaticStatus($id);
                unset($ids[$num]);
            }
        }

        return array_merge(
            $retval,
            $this->_replaceILSIds(
                parent::getStatuses($this->_getILSRecordIds($ids)), $ids
            ));
    }

    /**
     * Override and add to DAIA item status Email Hold availability
     *
     * @param array $item
     * @return array
     *
     * Todo: use $return['addEmailHoldLink'] = 'check'; for patron based service
     * availability
     */
    protected function getItemStatus($item)
    {
        $return = parent::getItemStatus($item);
        $return['addEmailHoldLink'] = $this->checkEmailHoldValidationCriteria($return);
        if ($return['addEmailHoldLink'] == true) {
            $return['addLink'] = false;
            $return['addStorageRetrievalRequestLink'] = false;
        }
        return $return;
    }

    /**
     * Returns the value for "barcode" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemBarcode($item)
    {
        if (isset($item['id']) && preg_match("/^".$this->daiaIdPrefix."([A-Za-z0-9]+):([A-Za-z0-9]+)$/", $item['id'], $matches)) {
            return array_pop($matches);
        }
        return parent::getItemBarcode($item);
    }

    /*********************************************
     * Custom PAIA methods
     *********************************************/

    /**
     * Gets additional array fields for the item.
     * Override this method in your custom PAIA driver if necessary.
     *
     * @param array $fee The fee array from PAIA
     *
     * @return array Additional fee data for the item
     */
    protected function getAdditionalFeeData($fee, $patron = null)
    {
        $additionalData = [];
        // The title is always displayed to the user in fines view if no record can
        // be found for current fee. So always populate the title with content of
        // about field.
        if (isset($fee['about'])) {
            $additionalData['title'] = $fee['about'];
        }

        // custom PAIA fields
        // fee.about 	0..1 	string 	textual information about the fee
        // fee.item 	0..1 	URI 	item that caused the fee
        // fee.feeid 	0..1 	URI 	URI of the type of service that
        // caused the fee
        $additionalData['feeid']      = (isset($fee['feeid'])
            ? $fee['feeid'] : null);
        $additionalData['about']      = (isset($fee['about'])
            ? $fee['about'] : null);
        $additionalData['item']       = (isset($fee['item'])
            ? $fee['item'] : null);

        return $additionalData;
    }
    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @return array Array of the patron's profile data on success,
     * @throws Exception
     * @to-do  Build back of redundancy of default values by vcard sub-types.
     */
    public function getMyProfile($patron)
    {
        //todo: make fields more configurable
        if (is_array($patron)) {
            if (isset($patron['address'])) {
                try {
                    $vcard = VObject\Reader::read($patron['address']);
                    if (isset($vcard->ADR)) {
                        foreach ($vcard->ADR as $adr) {
                            $address[(
                            (isset($adr->parameters['ALTID']))
                                ? (string)$adr->parameters['ALTID'] : 'p' //sets default key 'p' for private address
                            )] = $adr->getParts();
                        }
                    }
                    if (isset($vcard->TEL)) {
                        foreach ($vcard->TEL as $tel) {
                            $phone[(
                            (isset($tel->parameters['TYPE']))
                                ? (string)$tel->parameters['TYPE'] : 'home' //sets default key 'home' for private phone number
                            )] = (string)$tel;
                        }
                    }
                    if (isset($vcard->EMAIL)) {
                        foreach ($vcard->EMAIL as $email) {
                            $emails[] = $email;
                        }
                    }
                    if (isset($vcard->ROLE)) {
                        $group = (string)$vcard->ROLE;
                    }
                    if (isset($vcard->{'X-LIBRARY-BORROWER-BRANCH'})) {
                        $home_library
                            = (string)$vcard->{'X-LIBRARY-BORROWER-BRANCH'};
                    }
                    if (isset($vcard->{'X-LIBRARY-ILS-PATRON-EDIT-ALLOW'})) {
                        $editable = $this->getEditableProfileFields(
                            (string)$vcard->{'X-LIBRARY-ILS-PATRON-EDIT-ALLOW'}
                        );
                    }

                } catch (Exception $e) {
                    throw $e;
                }
            }

            $addressParameterMap = self::$vcard_address_parameter_map;

            $replace = isset($this->config['PAIA']['profileFormEmptyInputReplacement'])
                ? $this->config['PAIA']['profileFormEmptyInputReplacement']
                : NULL;

            if (isset($address)) {
                foreach ($address as $key => $altid) {
                    foreach ($addressParameterMap as $parameter => $pos) {
                        $var = strtolower($parameter . '-' . $key);
                        $profile[$var] = ($altid[$pos] != $replace)
                            ? $altid[$pos] : '';
                        // keep backward compatibility to old forms
                        // vcard ALTID p is default value
                        if (strtolower($key) == 'p') {
                            $profile[$parameter] = ($altid[$pos] != $replace)
                                ? $altid[$pos] : '';
                        }
                    }
                }
            }

            if (isset($phone)) {
                // fill phone types to profile
                foreach ($phone as $parameter => $val) {
                    if ($val == $replace) $val = '';
                    $var = 'phone-' . $parameter;
                    $profile[$var] = $val;
                    // keep backward compatibility to old forms
                    // vcard TYPE home is default value
                    if (strtolower($parameter) == "home") {
                        $profile['phone'] = $val;
                    }
                }
            }

            $idm = [
                'firstname'  => $patron['firstname'],
                'lastname'   => $patron['lastname'],
                'group' => (!empty($group)) ? $group : null,
                // PAIA specific custom values
                'expires'    => isset($patron['expires'])
                    ? $this->convertDate($patron['expires']) : null,
                'statuscode' => isset($patron['status'])
                    ? $patron['status'] : null,
                'canWrite'   => in_array(self::SCOPE_WRITE_ITEMS, $this->getSession()->scope),
                // fincILS and PAIA specific custom values
                'email'      => !empty($patron['email'])
                    ? $patron['email'] : (!empty($emails[0]) ? $emails[0] : null),
                'editableFields' => (!empty($editable)) ? $editable : null,
                'home_library' => (!empty($home_library)) ? $home_library : null
            ];
            return (isset($profile)) ? array_merge($idm, $profile) : $idm;

        }
        return [];
    }

    /**
     * Get disabled profile fields which cannot modified by user
     *
     * @param array $vcard_fields Fields of vcard which can be edited.
     *
     * @return array $fields     Translated form Field which can be modified
     * @access private
     */
    private function getEditableProfileFields($vcard_fields)
    {

        $address_fields = ['address1', 'address2', 'city', 'country', 'zip'];
        $vcard_fields = explode(',', $vcard_fields);
        $fields = [];

        foreach ($vcard_fields as $fld) {

            $match = [];
            if (0 < preg_match('/(TEL|ADR)-(\w+)/', $fld, $match)) {
                if ($match[1] == "ADR") {
                    foreach ($address_fields as $part) {
                        $fields[] = strtolower($part . '-' . $match[2]);
                    }
                    // backward compatibility
                    if ($match[2] == 'P') {
                        $fields
                            = array_merge_recursive($fields, $address_fields);
                    }
                } elseif ($match[1] == "TEL") {
                    $fields[] = 'phone-' . $match[2];
                    // backward compatibility
                    if ($match[2] == 'home') {
                        $fields[] = 'phone';
                    }
                }
            } elseif ($fld == "N") {
                array_push($fields, 'firstname', 'lastname');
            } else {
                $fields[] = strtolower($fld);
            }
        }
        return $fields;
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

        $params['memberCode']   = $patron['cat_username'];
        $params['password']     = $patron['cat_password'];

        if (isset($patron['address']) && strpos($patron['address'],'BEGIN:VCARD') === 0) {
            $vcard = \Sabre\VObject\Reader::read($patron['address']);
        } else {
            $vcard = new VObject\Component\VCard();
        }

        //handle name
        $params['name'] = '';
        $name_array = array_fill(0,2,null);
        $noname = TRUE;
        if (isset($inval['firstname'])) {
            $params['name'] .= $inval['firstname'];
            $name_array[1] = $inval['firstname'];
            $noname = FALSE;
        }
        if (isset($inval['lastname'])) {
            $params['name'] .= ' '.$inval['lastname'];
            $name_array[0] = $inval['lastname'];
            $noname = FALSE;
        }
        if (!$noname) {
            $this->setVCardValue($vcard, 'FN', $params['name']);
            $this->setVCardValue($vcard, 'N', $name_array);
        }

        //handle e-mail
        if (isset($inval['email'])) {
            $params['email'] = $inval['email'];
            $this->setVCardValue($vcard,'EMAIL',$inval['email']);
        }

        $addressParameterMap = self::$vcard_address_parameter_map;

        $addressBaseRegex = '(' . implode('|', array_keys($addressParameterMap)) . ')';

        // keep parts of address to process in the end of foreach
        $address_array = []; // array_fill(0,7,NULL);

        //the empty-field marker in the used ILS
        $replace = isset($this->config['PAIA']['profileFormEmptyInputReplacement'])
            ? $this->config['PAIA']['profileFormEmptyInputReplacement'] : NULL;

        foreach ($inval as $key => $val) {

            if (empty($val) && !is_null($replace)) $val = $replace;

            $match = [];
            // add phone inputs to vcard
            if (0 < preg_match('/phone-(\w+)/', $key, $match)) {
                $this->setVCardValue(
                    $vcard, 'TEL', $val, ['type' => $match[1]]
                );
            }

            // backward compatibility for old forms
            // push default phone input to vcard TYPE home
            if ($key == 'phone') {
                $this->setVCardValue(
                    $vcard, 'TEL', $inval['phone'], ['type' => 'home']
                );
            }

            // add address inputs to vcard
            // @to-do reduce redundancy of special address1 treatment case
            if (0 < preg_match('/' . $addressBaseRegex . '-(\w+)/', $key, $match)) {
                // match[0] inval
                // match[1] base of key
                // match[2] extension of key -> vcard ALTID

                // treat address1 as exceptional case
                if ($match[1] == 'address1') {
                    if ($adr = $this->splitAddress($val)) {
                        $address_array[($match[2])][2]
                            = $adr[($addressParameterMap[$match[1]])];
                        if (isset($adr[2])) {
                            $address_array[($match[2])][1] = $adr[2];
                        }
                    } else {
                        $address_array[($match[2])][2] = $val;
                    }
                } else {
                    $address_array[($match[2])][$addressParameterMap[($match[1])]]
                        = $val;
                }
            }

            // backward compatibility for old forms
            // push default address input to vcard TYPE p
            if (array_key_exists($key, $addressParameterMap) && !empty($val)) {
                if ($key == 'address1') {
                    if ($adr = $this->splitAddress($val)) {
                        $address_array['p'][2] = $adr[1];
                        if (isset($adr[2])) {
                            $address_array['p'][1] = $adr[2];
                        }
                    } else {
                        $address_array['p'][2] = $val;
                    }
                } else {
                    $address_array['p'][$addressParameterMap[$key]] = $val;
                }
            }
        }

        // prepare address for set vcard value
        foreach ($address_array as $type => $address) {
            for ($i = 0; $i < 7; $i++) {
                if (!array_key_exists($i, $address)) {
                    $address[$i] = null;
                }
            }
            ksort($address);
            $this->setVCardValue(
                $vcard, 'ADR', $address, ['ALTID' => strtoupper($type)]
            );
        }

        // process vcard
        $vcard = $vcard->convert(VObject\Component\VCard::VCARD40);
        $n = $vcard->select('N');
        if ($address = $vcard->serialize()) {
            $params['address'] = $address;
        }

        $result = $this->paiaUpdatePatron($params,$patron);
        if ($result['success']) return TRUE;

        $this->debug(__FUNCTION__.' '.$result['sysMessage']);
        return FALSE;
    }

    /**
     * helper function for addresses
     */
    private function splitAddress($address) {

        //the inval address might consist of street address and extended address
        //pattern "<street_address> <extended_address>" with a space in between
        //@see $this->getMyProfile() on that
        //however, we cannot assume a consistent pattern in any address part, so
        //it cannot be split correctly, at least we might try

        //the pattern accepts a typical german street address
        //like "Musterweg 22a" or "Jemand-Bekanntes-Ring 1 b, Hinterhaus"
        //where everything behind the comma is assumed to be the extension
        $conf = $this->config;
        $regex = '(\D+\d[^\,]*)(?:\,\s*(.*))?';
        $matches = array();
        if (preg_match('/'.$regex.'/',$address,$matches)) {
            return $matches;
        }
        return FALSE;
    }

    /**
     * Sets value in VCard-Object that may also be somewhere in a sub-component
     * @param VObject\Component\VCard $vcard
     * @param string $key
     * @param string $value
     * @param array $type Subtype of vcard field as e.g. cell for phone
     */
    private function setVCardValue(
        VObject\Component\VCard $vcard, $key, $value, $type = null
    )
    {
        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        } elseif (is_array($value)) {
            array_walk_recursive($value, function (&$value, $key) {
                $value = str_replace(',', '', $value);
            });
        }
        $children = $vcard->select($key);
        if (empty($children)) {

            // if the key is unknown, we add a new property with the value
            if (in_array($key, array('TEL', 'ADR'))) {
                $vcard->createComponent($key);
            } else {
                $vcard->createProperty($key);
            }
            $vcard->add($key, $value, $type);
        } else {
            if (isset($type)) {
                foreach ($children as &$child) {
                    foreach ($type as $type_key => $type_value) {
                        if (in_array(
                            $type_value,
                            $child->parameters[strtoupper($type_key)]->getParts()
                        )) {
                            $child->setValue($value);
                            return;
                        }
                    }
                }
            // refs #10912
            // if key exists in first dimension VCARD object return update true
            } elseif (isset($key)) {
                foreach ($children as $child) {
                    if ($key == $child->name) {
                        $vcard->{$key}->setValue($value);
                        return;
                    }
                }
            }
            $vcard->add($key, $value, $type);
        }
    }

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron's username
     * @param string $password The patron's login password
     *
     * @return mixed Associative array of patron info on successful login,
     * null on unsuccessful login.
     * @access public
     * @throws \Exception
     * @throws ILSException
     */
    public function patronLogin($username, $password)
    {
        if (!empty($this->root_username) && !empty($this->root_password)) {
            if ($username == '') {
                throw new ILSException('Invalid Login, Please try again.');
            }

            $session = $this->getSession();

            // if we already have a session with access_token and patron id, try to get
            // patron info with session data
            if (isset($session->expires) && $session->expires > time()) {
                try {
                    return $this->enrichUserDetails(
                        $this->paiaGetUserDetails(($session->patron === 'root' ? $username : $session->patron)),
                        $password,
                        $username
                    );
                } catch (\Exception $e) {
                    // TODO? $this->debug('Session expired, login again', 'info');
                    // all error handling is done in paiaHandleErrors so pass on the excpetion
                    throw $e;
                }
            }

            try {
                if($this->paiaLogin($this->root_username, $this->root_password)) {
                    return $this->enrichUserDetails(
                        $this->paiaGetUserDetails(($session->patron === 'root' ? $username : $session->patron)),
                        $password,
                        $username
                    );
                }
            } catch (\Exception $e) {
                // all error handling is done in paiaHandleErrors so pass on the excpetion
                throw $e;
            }
        } else {
            return parent::patronLogin($username, $password);
        }
    }

    /**
     * PAIA helper function to map session data to return value of patronLogin()
     *
     * @param $details  Patron details returned by patronLogin
     * @param $password Patron cataloge password
     * @return mixed
     */
    protected function enrichUserDetails($details, $password, $username = null)
    {
        $details = parent::enrichUserDetails($details, $password);

        // overwrite cat_username if we logged in as root
        $session = $this->getSession();
        $details['cat_username'] = $session->patron === 'root' && !empty($username)
            ? $username : $session->patron;

        return $details;
    }

    /**
     * Customized PAIA support method for PAIA core method 'items' returning only
     * filtered items.
     * Available filters:
     *      - key=>value : PAIA document.key must contain value
     *      - exclude => [key=>value] : PAIA document.key must not contain value
     *      - regex => [key=>value] : PAIA document.key must preg_match(value)
     *
     * @param array $patron Array with patron information
     * @param array $filter Array of properties identifying the wanted items
     *
     * @return array|mixed Array of documents containing the given filter properties
     */
    protected function paiaGetItems($patron, $filter = [])
    {
        // check for existing data in cache
        if ($this->paiaCacheEnabled) {
            $itemsResponse = $this->getCachedData($patron['cat_username']);
        }

        if (!isset($itemsResponse) || $itemsResponse == null) {
            try {
                $itemsResponse = $this->paiaGetAsArray(
                    'core/'.$patron['cat_username'].'/items'
                );
            } catch (Exception $e) {
                // all error handling is done in paiaHandleErrors so pass on the excpetion
                throw $e;
            }

            if ($this->paiaCacheEnabled) {
                $this->putCachedData($patron['cat_username'], $itemsResponse);
            }
        }

        if (isset($itemsResponse['doc'])) {
            if (count($filter)) {
                $filteredItems = [];
                foreach ($itemsResponse['doc'] as $doc) {
                    $filterCounter = 0;
                    foreach ($filter as $filterKey => $filterValue) {
                        switch ($filterKey) {
                            case 'exclude' :
                                // check exclude filters
                                $excludeCounter = 0;
                                foreach ($filterValue as $excludeKey => $excludeValue) {
                                    if ((isset($doc[$excludeKey]) && in_array($doc[$excludeKey], (array) $excludeValue))
                                        || ($excludeValue == null && !isset($doc[$excludeKey]))
                                    ) {
                                        $excludeCounter++;
                                    }
                                }
                                // exclude is a negative filter, so the item might be
                                // selected if exclude does NOT match
                                if ($excludeCounter != count($filterValue)) {
                                    $filterCounter++;
                                }
                                break;
                            case 'regex' :
                                // check regex filters
                                $regexCounter = 0;
                                foreach ($filterValue as $regexField => $regexPattern) {
                                    if (isset($doc[$regexField])
                                        && preg_match($regexPattern, $doc[$regexField]) === 1
                                    ) {
                                        $regexCounter++;
                                    }
                                }
                                // regex is a positive filter, so the item might be
                                // selected if regex does match
                                if ($regexCounter == count($filterValue)) {
                                    $filterCounter++;
                                }
                                break;
                            default:
                                // any other filter is a positive filter, so the item
                                // might be selected if the key-value pair does match
                                if ((isset($doc[$filterKey]) && in_array($doc[$filterKey], (array) $filterValue))
                                    || ($filterValue == null && !isset($doc[$filterKey]))
                                ) {
                                    $filterCounter++;
                                }
                                break;
                        }
                    }
                    // check if all filters applied
                    if ($filterCounter == count($filter)) {
                        $filteredItems[] = $doc;
                    }
                }
                return $filteredItems;
            } else {
                return $itemsResponse;
            }
        } else {
            $this->debug(
                "No documents found in PAIA response. Returning empty array."
            );
        }
        return [];
    }

    /**
     * PAIA helper function to allow customization of mapping from PAIA response to
     * VuFind ILS-method return values.
     *
     * @param array  $items   Array of PAIA items to be mapped
     * @param string $mapping String identifying a custom mapping-method
     *
     * @return array
     */
    protected function mapPaiaItems($items, $mapping)
    {
        return $this->postprocessPaiaItems(
            parent::mapPaiaItems($items, $mapping)
        );
    }

    /**
     * Helper function to postprocess the PAIA items for display in catalog (e.g. retrieve
     * fincid etc.).
     *
     * @param array $items Array of PAIA items to be postprocessed
     *
     * @return mixed
     */
    protected function postprocessPaiaItems($items)
    {
        // regex pattern for item_id (e.g. UBL:barcode:0008911555)
        $idPattern = '/^([]A-Za-z0-9_\-]*):(%s):(.*)$/';

        // item_id identifier - Solr field mapping
        $identifier = [
            'barcode' => 'barcode' .
                (isset($this->mainConfig->CustomIndex->indexExtension)
                    ? '_'.$this->mainConfig->CustomIndex->indexExtension : ''),
            'fincid'  => 'id',
            'ppn'     => 'record_id'
        ];

        // try item_id with defined regex pattern and identifiers and use Solr to
        // retrieve fincid on match
        $ilsIdentifier = function ($itemId) use ($identifier, $idPattern) {
            foreach ($identifier as $key => $value) {
                $matches = [];
                if (preg_match(sprintf($idPattern, $key), $itemId, $matches)) {
                    return $this->_getFincId($matches[3], $value);
                }
            }
        };

        // iterate trough given items
        foreach ($items as &$item) {
            if (isset($item['id']) && empty($item['id']) && !empty($item['item_id'])) {
                $ilsId = $ilsIdentifier($item['item_id']);
                if ($ilsId != null) {
                    $item['id'] = $ilsId;
                    $item['source'] = 'Solr';
                }
            }
        }

        return $items;
    }

    /*********************************************
     * Finc-ILS specific methods
     *********************************************/

    /**
     * Sorts given array items in the given sortOrder using the given array
     * fieldName. If the array contains elements without the given fieldName those
     * elements will get appended unsorted to the sorted array elements.
     * Sorting is done by asort() if sortOrder==SORT_ASC and arsort() if
     * sortOrder==SORT_DESC.
     *
     * @param array  $items     Array containing the items to be sorted
     * @param string $fieldName Fieldname whose content will be used for sorting
     * @param mixed  $sortOrder The order used to sort the item array. Either
     *                          SORT_ASC to sort ascendingly or SORT_DESC to sort
     *                          descendingly (defaults to SORT_ASC).
     * @return array
     */
    protected function itemFieldSort ($items, $fieldName, $sortOrder = SORT_ASC)
    {
        // array with items to be sorted
        $sortArray = [];
        // array with items that do not contain the sort key
        $noSortArray = [];
        // returned array with all items
        $returnArray = [];

        if (count($items) && !empty($fieldName)) {
            for ($i=0; $i<count($items); $i++) {
                if (isset($items[$i][$fieldName])) {
                    $sortArray[$i]=$items[$i][$fieldName];
                } else {
                    array_push($noSortArray, $items[$i]);
                }
            }

            // sort according to given sortOrder
            switch ($sortOrder) {
                case SORT_DESC:
                    arsort($sortArray);
                    break;
                case SORT_ASC:
                default:
                    asort($sortArray);
                    break;
            }

            // recombine all items
            $sortArray = $sortArray + $noSortArray;

            // build the return array with sorted items
            foreach ($sortArray as $key => $item) {
                $returnArray[] = $items[$key];
            }
            return $returnArray;
        }

        return $items;
    }

    /**
     * finc-specific function to count items/entries in return values of given
     * functions in order to be shown as numbers in MyReSearch-Menu
     *
     * @param $functions Array of function names that will get called and the
     *                   count of their return values being returned
     * @param $patron    Patron details returned by patronLogin
     * @return array     Array in the format [function => count]
     */
    public function countItems($functions, $patron) {
        $retval = [];
        if (is_array($functions)) {
            foreach ($functions as $function) {
                if (method_exists($this, $function)) {
                    $retval[$function] = count($this->$function($patron));
                }
            }
        }
        return $retval;
    }

    /**
     * finc-specific function to total the fines (e.g. for use in MyResearch menu)
     *
     * @param array $patron Patron details returned by patronLogin
     *
     * @return float
     */
    public function getFinesTotal($patron) {
        $fines = $this->getMyFines($patron);
        $total = 0;
        foreach ($fines as $fee) {
            $total += (int) $fee['amount'];
        }
        return $total / 100;
    }

    /**
     * FincILS specific helper function to add time period
     *
     * @param string   $date    Date in DateTime format to used as base.
     * @param null|int $years   Years to be added to DateTime.
     * @param null|int $months  Months to be added to DateTime.
     * @param null|int $days    Days to be added to DateTime.
     * @param null|int $hours   Hours to be added to DateTime.
     * @param null|int $minutes Minutes to be added to DateTime.
     * @param null|int $seconds Seconds to be added to DateTime.
     *
     * @return string
     */
    protected function addTime($date, $years = null, $months = null, $days = null,
                               $hours = null, $minutes = null, $seconds = null)
    {
        try {
            // Get time zone
            $timezone = isset($this->mainConfig->Site->timezone)
                ? $this->mainConfig->Site->timezone : 'America/New_York';

            // create DateTime object
            $dateObj = new DateTime($date, new DateTimeZone($timezone));

            $intervalSpec = 'P' .
                ($years   != null ? $years . 'Y'         : '') .
                ($months  != null ? $months . 'M'        : '') .
                ($days    != null ? $days . 'D'          : '') .
                ($hours   != null ? $hours . 'H'         : '') .
                ($minutes != null ? 'T' . $minutes . 'M' : '') .
                ($seconds != null ? $seconds . 'S'       : '');
            $dateInterval = new DateInterval($intervalSpec);

            return $dateObj->add($dateInterval)->format('Y-m-d\TH:i:s');
        } catch (\Exception $e) {
            $this->debug('Date adition failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Helper function to create static ils response
     *
     * @param string $id Record id to return static status data
     *
     * @return array
     */
    private function _getStaticStatus($id)
    {
        if (!$this->auth) {
            $this->debug('Authorization service missing for checking availability ' .
                'of record ' . $id
            );
            return '';
        }

        $permission = $this->_getRecord($id)->tryMethod('getRecordPermission');

        $isGranted = $permission != null
            ? $this->auth->isGranted($permission) : true;

        return [[
            'id'           => $id,
            'availability' => $isGranted,
            'status'       => $isGranted ? 'available' : $permission,
            'reserve'      => 'false',
            'location'     => '',
            'callnumber'   => '',
            'services'     => !$isGranted ? [$permission] : []
        ]];
    }

    /**
     * Helper function to check whether the record with the given id qualifies for
     * querying the ILS
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return bool
     */
    private function _hasILSData($id)
    {
        $retVal = [];
        foreach ($this->config['General']['queryIls'] as $value) {
            list($methodName, $methodReturn) = explode(':', $value);
            // if we have one mismatch we can already stop as this record does
            // not qualify for querying the ILS
            if (!in_array($methodReturn, (array) $this->_getRecord($id)->tryMethod($methodName))) {
                return false;
            }
        }
        // if we got this far the record qualifies for querying the ILS
        return true;
    }

    /**
     * Get the Record-Object from the RecordDriver.
     *
     * @param string $id ID of record to retrieve
     *
     * @return \VuFind\RecordDriver\AbstractBase
     */
    private function _getRecord($id)
    {
        return $this->recordLoader->load($id);
    }

    /**
     * Function to replace the custom ILS specific identifier
     * with the VuFind record_id, provided by the mapping array
     * $idMapper.
     *
     * @param array  $array Array with status information.
     * @param string $id    VuFind record_id.
     *
     * @return mixed
     */
    private function _replaceILSId($array, $id)
    {
        $statuses = [];
        foreach ($array as $status) {
            if (isset($status['item_id'])
                && $status['item_id'] == $this->_idMapper[$id]
            ) {
                $status['item_id'] = $id;
            }
            if (isset($status['id'])
                && $status['id'] == $this->_idMapper[$id]
            ) {
                $status['id'] = $id;
            }
            $statuses[] = $status;
        }

        return $statuses;
    }

    /**
     * Function to replace the custom ILS specific identifier
     * with the VuFind record_id in several status information
     * arrays.
     *
     * @param array $array Array with status information from several records.
     *                          records.
     * @param array $ids   Array with VuFind record_ids.
     *
     * @return array
     */
    private function _replaceILSIds($array, $ids)
    {
        $results = [];
        foreach ($array as $statuses) {
            foreach ($ids as $id) {
                if ($this->_containsILSid($statuses, $id)) {
                    // save the result if _replaceILSId had some effect
                    $results[] = $this->_replaceILSId($statuses, $id);
                }
            }
        }

        return $results;
    }

    /**
     * Function to check whether the given array with status information
     * contains an ILS-specific identifier, provided by idMapper($id)
     *
     * @param array  $array Array with status information.
     * @param string $id    VuFind record_id.
     *
     * @return bool
     */
    private function _containsILSid($array, $id)
    {
        foreach ($array as $status) {
            if ($status['item_id'] == $this->_idMapper[$id]
                || $status['id'] == $this->_idMapper[$id]
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the identifier for the record which will be used for ILS interaction
     *
     * @param string $id            Document to look up.
     * @param string $ilsIdentifier Identifier to override config settings.
     *
     * @return string $ilsRecordId
     */
    private function _getILSRecordId($id, $ilsIdentifier = null)
    {
        // override ilsIdentifier with the ilsIdentifier set in ILS driver config
        if ($ilsIdentifier == null) {
            $ilsIdentifier = $this->ilsIdentifier;
        }

        //get the ILS-specific recordId
        if ($ilsIdentifier != "default") {

            try {
                $ilsRecordId = $this->_getRecord($id)
                    ->getILSIdentifier($ilsIdentifier);
            } catch (\Exception $e) {
                $this->debug($e);
                $this->_idMapper[$id] = $id;
                return $id;
            }

            if ($ilsRecordId == '') {
                $this->_idMapper[$id] = $id;
                return $id;
            } else {
                if (is_array($ilsRecordId)) {
                    // use ISIL for identifying the correct ILS-identifier if
                    // array is returned
                    $isils = implode("|", $this->isil);
                    foreach ($ilsRecordId as $recordId) {
                        if (preg_match(
                            "/^\((" . $isils . ")\)(.*)$/", $recordId, $match
                        )
                        ) {
                            $recordId = (isset($match[2]) && strlen($match[2] > 0))
                                ? $match[2] : null;
                            $this->_idMapper[$id] = $recordId;
                            return $recordId;
                        }
                    }
                    // no match was found for the given ISIL, therefore return $id
                    return $id;
                }
                $this->_idMapper[$id] = $ilsRecordId;

                return $ilsRecordId;
            }
        }
        $this->_idMapper[$id] = $id;

        return $id;
    }

    /**
     * Get the identifiers for multiple records
     *
     * @param array  $ids           Documents to look up.
     * @param string $ilsIdentifier Identifier to override config settings.
     *
     * @return array $ilsRecordIds
     */
    private function _getILSRecordIds($ids, $ilsIdentifier = null)
    {
        $ilsRecordIds = [];

        // override ilsIdentifier with the ilsIdentifier set in ILS driver config
        if ($ilsIdentifier == null) {
            $ilsIdentifier = $this->ilsIdentifier;
        }

        if (is_array($ids)) {
            foreach ($ids as $id) {
                $ilsRecordIds[] = $this->_getILSRecordId($id, $ilsIdentifier);
            }

            return $ilsRecordIds;
        }

        return $ids;
    }

    /**
     * Get the finc id of the record with the given ilsIdentifier value
     *
     * @param string $ilsId         Document to look up.
     * @param string $ilsIdentifier Identifier to override config settings.
     *
     * @return string $fincId if ilsIdentifier is configured, otherwise $ilsId
     */
    private function _getFincId($ilsId, $ilsIdentifier = null)
    {
        // override ilsIdentifier with the ilsIdentifier set in ILS driver config
        if ($ilsIdentifier == null) {
            $ilsIdentifier = $this->ilsIdentifier;
        }

        if ($ilsIdentifier != "default") {
            // different ilsIdentifier is configured, retrieve fincid

            // if the given ilsIdentifier is known as a dynamic field it is suffixed
            // with the isil
            if (in_array($ilsIdentifier, $this->dynamicFields)) {
                if (isset($this->mainConfig->CustomIndex->indexExtension)) {
                    $ilsIdentifier .= "_"
                        . trim($this->mainConfig->CustomIndex->indexExtension);
                }
            }
            try {
                // todo: compatible implementation for any SearchBackend (currently Solr only)
                $query = $ilsIdentifier . ':' . $ilsId;
                $result = $this->searchService->search(
                    'Solr',
                    new Query($query)
                );
                if (count($result) === 0) {
                    throw new \Exception(
                        'Problem retrieving finc id for record with ' . $query
                    );
                }
                return current($result->getRecords())->getUniqueId();
            } catch (\Exception $e) {
                $this->debug($e);
                // refs #12318 return falls if no main identifier can delivered
                // null will logically correct but throws exceptions in
                // subsequential core methods
                return false;
            }
        }
        // todo: check if return $ilsId is reasonable in context.
        // return will be only processed if $ilsIdentifier is defined as
        // 'default'. therefore method hasn't been called properly.
        return $ilsId;
    }

    /**
     * Private service test method
     *
     * @return void
     * @throws ILSException
     */
    private function _testILSConnections()
    {
        try {
            // test DAIA service
            preg_match(
                "/^(http[s:\/0-9\.]*(:[0-9]*)?\/[a-z]*)/",
                $this->baseUrl,
                $daiaMatches
            );
            $this->httpService->get($daiaMatches[1], [], $this->ilsTestTimeout);
            // test PAIA service
            preg_match(
                "/^(http[s:\/0-9\.]*(:[0-9]*)?\/[a-z]*)/",
                $this->paiaURL,
                $paiaMatches
            );
            $this->httpService->get($paiaMatches[1], [], $this->ilsTestTimeout);

            // test succeeded, save state
            $this->isOnline = true;
        } catch (\Exception $e) {
            $this->isOnline = false;
            throw new ILSException($e->getMessage());
        }
    }
}
