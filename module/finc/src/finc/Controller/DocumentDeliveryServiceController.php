<?php
/**
 * Document Delivery Service Controller
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Controller
 *
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace finc\Controller;

//use ZfcRbac\Service\AuthorizationServiceAwareInterface as AuthorizationServiceAwareInterface;
//use ZfcRbac\Service\AuthorizationServiceAwareTrait as AuthorizationServiceAwareTrait;
use finc\Exception\DDS as DDSException;
use finc\Mailer\Mailer as Mailer;
use Zend\Validator as Validator;

/**
 * Controller for Document Delivery Service
 *
 * @category VuFind2
 * @package  Controller
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class DocumentDeliveryServiceController extends \VuFind\Controller\AbstractBase implements
    \Zend\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;


    /**
     * VuFind configuration extended with Document Delivery Service configuration.
     *
     * @var $config
     * @access protected
     */
    protected $config = array();

    /**
     * Departments
     *
     * @var $department
     * @access protected
     */
    protected $department = array();

    /**
     * Divisions
     *
     * @var $division
     * @access protected
     */
    protected $division = array();

    /**
     * HTTP client
     *
     * @var \Zend\Http\Client
     * @access protected
     */
    protected $httpClient;

    /**
     * Session container
     *
     * @var \Zend\Session\Container
     */
    protected $session;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     * @param \Zend\Config\Config $dds Document Delivery Service configuration
     * @param \Zend\Http\Client $client Http client
     */
    public function __construct(\Zend\Config\Config $config,
                                \Zend\Config\Config $dds,
                                \Zend\Session\Container $session
    ) {
        $this->config = array_merge($dds->toArray(), $config->toArray());
        $this->session = $session;
    }

    /**
     * @var $content
     * @access protected
     */
    protected $content = array();

    /**
     * Build department taxonomy for options of select box.
     *
     * @return json $department
     * @access protected
     */
    protected function getDepartments()
    {
        return json_encode($this->department);
    }


    /**
     * Build department taxonomy for options of select box.
     *
     * @return array $division
     * @access protected
     */
    protected function getDivisions()
    {
        return $this->division;
    }


    /**
     * Display Feedback home form.
     *
     * @return \Zend\View\Model\ViewModel
     * @throws Exception
     */
    public function homeAction()
    {
        $this->accessPermission = 'access.DDSForm';

        // If accessPermission is set, check for authorization to enable form view
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $auth = $this->getAuthorizationService();
        if (!$auth) {
            throw new Exception('Authorization service missing');
        }

        if (!$auth->isGranted($this->accessPermission)) {
            $this->flashMessenger()->addMessage('DDS::dds_restriction_text', 'error');
            $view = $this->createViewModel();
            $view->loadForm = false;
            $view->setTemplate('documentdeliveryservice/home');
            return $view;
        }

        return $this->createDDSViewModel($this->getContent());
    }

    /**
     * Display Feedback home form.
     *
     * @return \Zend\View\Model\ViewModel
     * @access public
     * @throws MailException
     * @throws DDSException
     */
    public function emailAction()
    {
        $content = $this->getContent();

        // Validation
        $isError = false;
        $fields = ['author', 'division', 'email', 'journal', 'username',
            'number', 'publishdate','pages'
        ];
        $departmentfield = ($content['division'] == '15')
            ? 'inputdepartment' : 'department';
        array_push($fields, $departmentfield);

        $validator = new Validator\NotEmpty();
        foreach ($fields as $field) {
            if (false === $validator->isValid($content[$field])) {
                $isError = true;
                $error[$field] =  ucfirst($field) . ' should not be blank';
            }
        }
        $validator = new Validator\EmailAddress();
        if (false === $validator->isValid($content['email'])) {
            $isError = true;
            $error['email'] = 'The email is not valid ';
        }
        if (true === $isError) {
            $content['error'] = (object) $error;
            return $this->createDDSViewModel($content);
        }

        // Prepare Email Template
        $body = $this->buildEmailTemplates($content);

        // Prepare Email Header
        $departmentdetails =
            $this->getDetailsOfDepartment($content['department'], $content['division']);

        $email['from'] = $this->getConfigVar('DDS','from');
        $email['subject'] = $this->setSubjectEmail($departmentdetails);
        $email['to'] = $this->setRecipientEmail($departmentdetails);
        $email['body'] = $body['order'];
        $email['reply'] = $content['email'];
        $email['replyname'] = $content['name'];

        try {
            // Send Email
            $mailer = new Mailer(
                $this->getServiceLocator()
                    ->get('VuFind\Mailer')->getTransport()
            );
            $mailer->sendTextHtml(
                $email['to'],
                $email['from'],
                $email['reply'],
                $email['replyname'],
                $email['subject'],
                '', //$bodyHtml,
                $email['body']
            );

            $this->sendOrderToApi($content);

            $this->flashMessenger()->addMessage(
                'DDS::dds_order_success', 'success'
            );

        } catch (MailException $e) {
            $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            return $this->createDDSViewModel($content);
        }
        // Save to database
        /*if (false === $this->sendOrderToApi($content)) {
            throw new DDSException('Cannot send successful order to api.');
        }*/

        return $this->redirect()->toRoute(
            'dds-home'
        );
    }

    /**
     * Private authentication function - DocDeliS JTW authentication
     *
     * @params auth_path    Substitute of path for authentication.
     * @return mixed        True if authentication successful, null if not
     *                      successful, PEAR_Error on error.
     * @throws Exception
     * @throws DDSException
     * @access protected
     */
    protected function authenticateDDSService($auth_path = '/authorize')
    {

        $method = 'POST';
        $auth_data['email'] = isset($this->config['DDS']['username'])
            ? $this->config['DDS']['username'] : '';
        $auth_data['password'] = isset($this->config['DDS']['password'])
            ? $this->config['DDS']['password'] : '';

        $api_url = isset($this->config['DDS']['url'])
            ? $this->config['DDS']['url'] : '';

        $params = http_build_query($auth_data);

        $client = $this->createHttpClient();
        try {
            $response = $client
                ->setUri($api_url . $auth_path)
                ->setMethod($method)
                ->setParameterPost($auth_data)
                ->setEncType('application/x-www-form-urlencoded')
                ->send();

            if (!$response->isSuccess()) {
                if ($response->getContent()) {
                    $responseArray = $this->parseJsonAsArray($response->getContent());
                    if (array_key_exists('error', $responseArray)) {
                        $message = $responseArray['error'];
                    }
                } else {
                    $message = 'HTTP status ' . $response->getStatusCode() . ' received';
                }
                throw new DDSException ($message);
            }

            $responseArray = $this->parseJsonAsArray($response->getBody());

            if (array_key_exists('token', $responseArray)) {
                //$_SESSION['dssToken'] = $responseArray['token'];
                $this->session->ddsToken = $responseArray['token'];
                return true;
            } else {
                throw new DDSException('Token not found! Access denied.');
            }
        } catch (DDSException $e) {
            // mute if credentials are not correct.
            if ($e->getMessage() === 'invalid_credentials') {
                return null;
            }
            throw new DDSException($e->getCode() . ':' . $e->getMessage());
        }
    }

    /**
     * Private method for build and fill the email templates.
     *
     * @params array $details   Details to build template
     *
     * @return array [order|customer] Body texts for email
     * @access protected
     */
    protected function buildEmailTemplates($details)
    {
        // Get subito url
        $details['subito_url'] = $this->buildSubitoUrl($details);
        // Get department name instead of identifier
        $details['department'] = ($details['division'] == '15')
            ? $details['inputdepartment'] : $this->getDepartmentName(
                $details['department'], $details['division']
            );
        // Set time
        $details['time'] = date('d.m.Y H:i');

        // Build email templates
        $renderer = $this->getViewRenderer();

        // Custom template for emails
        $body['order'] = $renderer->render(
            'Email/dds-text.phtml', $details
        );
        /*$body['customer'] = $renderer->render(
            'Email/dds-confirmation-text.phtml', $details
        );*/
        return $body;
    }

    /**
     * Map form fields parameter to Subito url.
     *
     * @param array $fields     List of parameters to build Subito url.
     *
     * @access private
     * @return mixed            Return Subito $url otherwise false.
     * @throws DDSException     Exceptions for no sufficient delivered parameters.
     */
    private function buildSubitoUrl( $fields )
    {
        if (count($fields) == 0) {
            return false;
        }
        // Set map table
        $map = [
            'article' => 'ATI',
            'author' => 'AAU',
            'journal' => 'JT',
            'issn' => 'SS',
            'number' => 'VOL',
            'publishdate' => 'APY',
            'pages' => 'PG'
        ];
        try {
            // Get config
            $serviceconfig = ['broker_id', 'url'];

            $auth_data['email'] = isset($this->config['DDS']['username'])
                ? $this->config['DDS']['username'] : '';


            foreach ($serviceconfig as $var) {
                if (!isset($this->config['SubitoService'][$var]) ||
                    strlen($this->config['SubitoService'][$var]) == 0
                ) {
                    throw new DDSException (
                        'Do not found ' . $var . ' at [SubitoService] DDS.ini.'
                    );
                }
                $$var = $this->config['SubitoService'][$var];
            }
            // user ci cp
            // Define obligated fields
            // obligated at least one of ss or jt
            if (empty($fields['issn']) && empty($fields['journal'])) {
                throw new DDSException (
                    'At least issn or title of journal is necessary for an order at
                    Subito service.'
                );
            }
            // all fields of vol, apy, pg
            if (empty($fields['number']) ||
                empty($fields['publishdate']) ||
                empty($fields['pages'])
            ) {
                throw new DDSException (
                    'Pages, publish date and volume are binding statements., '
                );
            }
            // build subito url
            $subito_url = $url . "?BI=" . urlencode($broker_id);
            foreach ($map as $key => $param) {
                if (isset($fields[$key]) && !empty($fields[$key])) {
                    $subito_url = $subito_url . '&' . $param . '='
                        . urlencode($fields[$key]);
                }
            }
            return $subito_url;

        } catch (DDSException $e) {
            throw new DDSException($e->getCode() . ':' . $e->getMessage());
        }
    }

    /**
     * Create a new ViewModel to use as a Document Delivery Service-Email form.
     *
     * @param array  $params         Parameters to pass to ViewModel constructor.
     *
     * @return object $view
     */
    protected function createDDSViewModel($params = null) {

        $view = $this->createViewModel();
        // Assign vars to view.
        foreach ($params as $key => $value) {
            $view->$key = $value;
        }
        // Assign vars for select menu to view.
        $view->departments = $this->getDepartments();
        $view->divisions = $this->getDivisions();
        $view->loadForm = true;
        $view->setTemplate('documentdeliveryservice/home');
        return $view;
    }

    /**
     * Create http client if it is not already exists.
     *
     * @return \Zend\Http\Client
     * @access protected
     */
    protected function createHttpClient()
    {
        return $this->getServiceLocator()->get('VuFind\Http')->createClient();
    }

    /**
     * Get comprehensive data and transfer it to an adaptable format for further
     * proceedings.
     *
     * @access private
     * @return array $content
     * @throws DDSException     Cannot load taxonomy of departments and divisions
     */
    private function getContent()
    {
        if (false === $this->getDepartmentTaxonomy()) {
            throw new DDSException ('Cannot load taxonomy of departments and ' .
                'divisions');
        }

        $post = [];
        if ($this->getRequest()->isPost()) {
            $getPost = $this->getRequest()->getPost()->toArray();
            $post = $getPost['subito'];
        }

        // populate the view with data given by User catalog account
        $user = $this->getDDSUserData();

        if (false !== ($get = $this->getOpenUrlParameters())) {
            $this->content = array_merge($this->content, $get);
        }

        $structure = array('username', 'phone', 'email', 'userid', 'division',
            'department', 'author', 'article', 'journal', 'issn',
            'publishdate', 'number', 'pages', 'inputdepartment', 'remarks');

        foreach ($structure as $attribute) {

            switch ($attribute) {
                case 'department':
                    $this->content[$attribute] =
                        (isset($post[$attribute]) && strlen($post[$attribute]) > 0)
                            ? $post[$attribute]
                            : (
                        (isset($user['department_id']) && strlen($user['department_id']) > 0)
                            ? $user['department_id'] : ''
                        );
                    break;
                case 'division':
                    $this->content[$attribute] =
                        (isset($post[$attribute]) && strlen($post[$attribute]) > 0)
                            ? $post[$attribute]
                            : (
                        (isset($user['division_id']) && strlen($user['division_id']) > 0)
                            ? $user['division_id'] : ''
                        );
                    break;
                case 'email':
                    $this->content[$attribute] =
                        (isset($user['email']) && strlen($user['email']) > 0)
                            ? $user['email'] : $post[$attribute];
                    break;
                case 'inputdepartment':
                    $this->content[$attribute] =
                        (isset($post[$attribute]) && strlen($post[$attribute]) > 0)
                            ? $post[$attribute]
                            : (
                        (isset($user['department']) && strlen($user['department']) > 0)
                            ? $user['department'] : ''
                        );
                    break;
                case 'username':
                    $this->content[$attribute] =
                        (isset($user['username']) && strlen($user['username']) > 0)
                            ? trim($user['username']) : trim($post[$attribute]);
                    break;
                case 'userid':
                    $this->content[$attribute] =
                        (isset($user['libraryCard']) && strlen($user['libraryCard']) > 0)
                            ? $user['libraryCard'] : $post[$attribute];
                    break;
                default:
                    if (!isset($this->content[$attribute])) {
                        $this->content[$attribute] =
                            (isset($post[$attribute]) && strlen($post[$attribute]) > 0)
                                ? $post[$attribute] : '';
                    }
                    break;
            }
        }
        // clean up department_id if division == 15
        if (isset($this->content['division']) && $this->content['division'] == '15') {
            $this->content['department'] = '';
        } else {
            $this->content['inputdepartment'] = '';
        }
        return $this->content;
    }



    /**
     * Get config setting before checking if value exists at config file.
     *
     * @param string $identifier Identifier of ini-file
     * @param string $var Variable of ini-file
     *
     * @return string
     * @access protected
     * @throws DDSException  No variable set at DDS.ini.
     */
    protected function getConfigVar($identifier, $var)
    {

        if (isset($this->config[$identifier][$var])) {
            return $this->config[$identifier][$var];
        } else {
            throw new DDSException ('Variable [' . $identifier . '] ' . $var . 'is '
                . 'not set at DDS.ini.');
            return '';
        }
    }

    /**
     * Retrieve data after successful sending previous record. Beware and keep
     * user data as division and department id.
     *
     * @private
     * @return array $heap
     */
    private function preserveVarsAfterSuccessfulOrder()
    {
        $heap = [];
        foreach (['department', 'inputdepartment', 'division', 'username', 'phone',
                     'email', 'userid'] as $k) {
            $heap[$k] = $this->content[$k];
        }
        $this->content = [];;
        return $heap;
    }

    /**
     * Get access token
     *
     * @return mixed        If isset hashed token as string otherwise false.
     * @access protected
     */
    protected function getDDSToken() {
        return (isset($this->session->ddsToken)) ? $this->session->ddsToken : false;
    }


    /**
     * Get all necessary user data for document delivery service
     *
     * @return mixed
     */
    protected function getDDSUserData()
    {
        // get most userdata from catalog login
        $patron = $this->getILSAuthenticator()->storedCatalogLogin();

        $ddsUserData['username']    = $patron['firstname'] . ' ' . $patron['lastname'];
        $ddsUserData['phone']       = isset($patron['phone']) ? $patron['phone'] : '';
        $ddsUserData['email']       = isset($patron['email']) ? $patron['email'] : '';
        $ddsUserData['libraryCard'] = $patron['cat_username'];

        return array_merge(
            ($this->getDDSUserDetail($ddsUserData['libraryCard'])),
            $ddsUserData
        );
    }

    /**
     * Get session data from user_session table
     *
     * @param string $user_id Identifier of user.
     *
     * @access private
     * @return array $session
     */
    private function getDDSUserDetail($user_id)
    {
        return $this->httpServiceRequest(
            '/user-details/' . $user_id,
            'get'
        );

    }

    /**
     * Get department name from id. Regarding division exception for faculty of
     * medicine
     *
     * @param mixed $department Department id or string.
     * @param int $division Faculty / division id
     *
     * @return string              Name of department
     * @access protected
     */
    protected function getDepartmentName($department, $division)
    {

        // if medicine faculty return back department
        if (isset($division) && $division == '15') {
            return $department;
        }
        if (is_array($this->department)
            && isset($this->department[$division][$department])
        ) {
            return $this->department[$division][$department];
        }
        return '';
    }

    /**
     * Build department taxonomy for options of select box.
     *
     * @return boolean true
     * @access protected
     */
    protected function getDepartmentTaxonomy()
    {
        $t = $this->httpServiceRequest('/departments', 'get');
        foreach ($t as $arr) {

            // Build and normalize dataset of division
            if (FALSE === array_key_exists($arr['fakultaetid'], $this->division)) {
                $this->division[$arr['fakultaetid']] = $arr['fakultaet'];
            }
            // Build options dataset
            $this->department[$arr['fakultaetid']][$arr['institutid']]
                = $arr['institut'];
        }
        return true;
    }

    /**
     * Get details of department
     *
     * @param mixed $department Department id
     * @param int $division Division / faculty id
     *
     * @return array $details         Details of departement
     * @access protected
     */
    protected function getDetailsOfDepartment($department, $division)
    {
        // if medicine faculty return back static department code 160
        // @to-do resolve static dependency
        $department = (isset($division) && $division == '15')
            ? 160 : $department;

        return $this->httpServiceRequest(
            '/department-details/' . $department,
            'get'
        );
    }

    /**
     * Select OpenUrl field from array list regarding to any special cases as
     * build line with more than one value. Get back first possible value.
     *
     * @param array $getvars $_GET container
     * @param string $field Form element name
     * @param string $index Field parameter to evaluate
     *
     * @access protected
     * @return string $retval   Return string for form field
     */
    protected function getOpenUrlField($getvars, $field, $index)
    {
        $retval = '';

        $array = explode('|', $index);

        if (count($array) == 1) {
            if (isset($getvars[$array[0]])) {
                return $getvars[$array[0]];
            }
        } else {
            $first = true;
            foreach ($array as $val) {
                if (isset($getvars[$val])) {
                    switch ($field) {
                        case 'author':
                            $retval = ($first === true)
                                ? $getvars[$val] : $retval . ' ' . $getvars[$val];
                            break;
                        case 'number':
                            $retval = ($first === true)
                                ? $getvars[$val] : $retval . '/' . $getvars[$val];
                            break;
                        case 'pages':
                            $retval = ($first === true)
                                ? $getvars[$val] : $retval . '-' . $getvars[$val];
                            break;
                    } // end switch
                    $first = false;
                } // end if
            } // end foreach
            return $retval;
        }
        return null;
    }

    /**
     * Get OpenUrl parameters
     *
     * @access private
     * @return mixed       False if no get vars exist, otherwise
     *                     array with vars for OpenUrl and not.
     *
     */
    protected function getOpenUrlParameters()
    {

        $retvars = [];
        $query = $this->getRequest()->getQuery()->toArray();

        if (count($query) == 0) {
            return false;
        }

        // if array filled then proceed
        if (false !== ($rft = $this->getOpenUrlParametersMapping($query))) {
            foreach ($rft as $line) {
                foreach ($line as $field => $index) {
                    if (is_array($index)) {
                        $retvars[$field] = '';
                        foreach ($index as $param) {
                            //if (isset($getvars[$param])) {
                            if (null != ($result = $this->getOpenUrlField($query, $field, $param))) {
                                $retvars[$field] = $result;
                                break;
                            }
                        } // end foreach
                    } else {
                        if (null != ($result = $this->getOpenUrlField($query, $field, $index))) {
                            $retvars[$field] = $result;
                        }
                    } // end else
                } // end foreach
            } // end foreach
            return $retvars;
        } else {
            return $query;
        }
    }

    /**
     * Get Open URL Parameter for processing.
     *
     * @param object $getvars GET variables.
     *
     * @access private
     * @return mixed Return array with parameters by success, false by none.
     */
    private function getOpenUrlParametersMapping($getvars)
    {
        // get open url standard
        $version = $this->getOpenUrlVersion($getvars);
        // check if article
        //if (true === $this->_isOpenUrlGenreArticle($getvars, $version)) {
        if ($version == 'v1_0') {
            return array(
                array('article' => 'rft_atitle'),
                array('author' => array('rft_au', 'rft_aufirst|rft_aulast', 'rft_aucorp')),
                array('journal' => array('rft_jtitle', 'rft_title')),
                array('issn' => 'rft_issn'),
                array('number' => 'rft_volume|rft_issue'),
                array('publishdate' => 'rft_date'),
                array('pages' => array('rft_pages', 'rft_spage|rft_epage'))
            );
        } else {
            return array(
                array('article' => 'atitle'),
                array('author' => array('aufirst|aulast')),
                array('journal' => array('title')),
                array('issn' => 'issn'),
                array('number' => 'volume|issue'),
                array('publishdate' => 'date'),
                array('pages' => array('pages', 'spage|epage'))
            );
        }
        //}
        return false;
    }

    /**
     * Get Open URL Version.
     *
     * @param array $getvars GET variables.
     *
     * @access private
     * @return string   Return version number of openurl v1_0 or v0_1. By default
     *                  assume standard v0_1
     */
    private function getOpenUrlVersion($getvars)
    {
        // check openurl version 1.0 standards
        foreach (['url_ver', 'ctx_ver'] as $param) {
            if (isset($getvars[$param])
                && (strtolower($getvars[$param]) == "z39.88-2004")
            ) {
                return "v1_0";
            }
        }
        foreach (['rft_id', 'rft_genre'] as $param) {
            if (isset($getvars[$param])) {
                return "v1_0";
            }
        }
        // check openurl version 0.1 standards
        if (isset($getvars['pid'])) {
            return "v0_1";
        }
        return "v0_1";
    }

    /**
     * Check if token is expired.
     *
     * @param array $context Context of error response message
     *
     * @return boolean          True if token expired.
     * @access protected
     */
    protected function isDDSTokenExpired($context)
    {
        $responseArray = json_decode($context, true);

        return (isset($responseArray['error'])
            && $responseArray['error'] == 'token_expired') ? true : false;
    }

    /**
     * Post something to a foreign host
     *
     * @param string $request_path target URL
     * @param string $request_type request type [get|post|put]
     * @param string $data         dynamic data
     *
     * @return string POST response
     * @throws  DDSException
     */
    protected function httpServiceRequest(
        $request_path, $request_type, $data = ''
    )
    {
        $http_headers = [];
        if (false === $this->getDDSToken()) {
            if (true !== $this->authenticateDDSService()) {
                throw new DDSException ('Access denied for HTTP request.');
            }
        }

        try {
            if (false === isset($this->config['DDS']['url'])) {
                throw new DDSException('Set of url in [DDS] DDS.ini is binding.');
            }
            $api_url = $this->config['DDS']['url'];
            if (false === isset($this->config['DDS']['ns'])) {
                throw new DDSException('Set of namespace ns in [DDS] DDS.ini is binding.');
            }
            $api_ns = $this->config['DDS']['ns'];

            $client = $this->createHttpClient();
            $client->setUri($api_url . DIRECTORY_SEPARATOR . $api_ns . $request_path);
            $client->setMethod($request_type);
            $client->setRawBody($data);
            $client->setHeaders(
                ['Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Bearer ' . $this->getDDSToken()]
            );
            //$client->setAdapter($adapter);
            $client->setAdapter(new \Zend\Http\Client\Adapter\Curl());
            $response = $client->send();

            if (!$response->isSuccess()) {
                if ($response->getContent()) {
                    // If token expired create new one.
                    if (true === $this->isDDSTokenExpired($response->getContent())) {
                        $this->authenticateDDSService();
                        $this->httpServiceRequest(
                            $request_path, $request_type, $data
                        );
                        exit;
                    }

                    $responseArray = $this->parseJsonAsArray($response->getContent());
                    if (array_key_exists('error', $responseArray)) {
                        $message = $responseArray['error'];
                    }
                } else {
                    $message = 'HTTP status ' . $response->getStatusCode() . ' received';
                }
                throw new DDSException ($message);
            }

            return $this->parseJsonAsArray($response->getBody());

        } catch (DDSException $e) {
            throw new DDSException($e->getCode() . ':' . $e->getMessage());
        }
    }

    /**
     * Private helper function for DDS to uniformely parse JSON
     *
     * @param string $json JSON data
     *
     * @return mixed
     * @throws DDSException
     */
    protected function parseJsonAsArray($json)
    {
        $responseArray = json_decode($json, true);

        if (isset($responseArray['error'])) {
            throw new DDSException(
                $responseArray['error'],
                $responseArray['code']
            );
        }
        return $responseArray;
    }

    /**
     * Refresh token if it is e.g. expired.
     *
     * @params path     Substitute of path for refresh token.
     *
     * @return mixed    True if token refreshed.
     * @access protected
     * @throws DDSException
     */
    protected function refreshDDSToken($path = '/token')
    {
        $method = "GET";
        $api_url = isset($this->config['DDS']['url'])
            ? $this->config['DDS']['url'] : '';

        $client = $this->createHttpClient();
        try {
            $response = $client
                ->setUri($api_url . $path)
                ->setMethod($method)
                ->setEncType('application/x-www-form-urlencoded')
                ->send();

            if (!$response->isSuccess()) {
                if ($response->getContent()) {
                    $responseArray = $this->parseJsonAsArray($response->getContent());
                    if (array_key_exists('error', $responseArray)) {
                        $message = $responseArray['error'];
                    }
                } else {
                    $message = 'HTTP status ' . $response->getStatusCode() . ' received';
                }
                throw new DDSException ($message);
            }

            $responseArray = $this->parseJsonAsArray($response->getBody());

            if (array_key_exists('token', $responseArray)) {
                //$_SESSION['dssToken'] = $responseArray['token'];
                $this->session->ddsToken = $responseArray['token'];
                return true;
            } else {
                throw new DDSException('Refreshed Token not delivered! Access denied.');
            }
        } catch (DDSException $e) {
            throw new DDSException($e->getCode() . ':' . $e->getMessage());
        }
    }

    /**
     * Store triggered order to database
     *
     * @params array $content Content
     *
     * @access private
     * @return boolean true
     * @throws DDSException
     */
    private function sendOrderToApi($content)
    {
        $boolOrder = $this->storeOrderOfItem($content);
        if (true !== $boolOrder['saved']) {
            throw new DDSException('Order not saved in database.');
        }

        $boolUserDetail = $this->storeUserDetail($content);
        return true;
    }

    /**
     * Set field inputdepartment to department if set to make postprocessing more
     * flexible.
     *
     * @param array $content Container of field variables
     *
     * @return array $content
     * @access private
     * @deprecated
     */
    private function setInputDepartmentToDepartment($content)
    {

        if (isset($content['division']) && $content['division'] == 15) {
            if (isset($content['inputdepartment'])) {
                $content['department'] = $content['inputdepartment'];
            }
        }
        return $content;
    }

    /**
     * Method to set recipient email for Subito order. Places two possibilities:
     * 1) If exists email address at subito.ini 2) from mysql database.
     *
     * @param array $departmentdetails  Details of department
     *
     * @return string
     * @access private
     * @throws DDSException             No or false variable is set.
     */
    private function setRecipientEmail($departmentdetails)
    {
        try {
            if (true === $this->useConfigFromDatabase()) {
                if (isset($departmentdetails['branchEmail'])) {
                    return $departmentdetails['branchEmail'];
                }
                throw new DDSException ('No email recipient address is set at '.
                    ' at database.');
            }
            if (isset($this->config['DDS']['to'])) {
                return $this->config['DDS']['to'];
            }
            throw new DDSException ('No email recipient address is set at DDS.ini.');
        } catch (DDSException $e) {
            throw new DDSException($e->getMessage());
        }
    }

    /**
     * Method to set subject for Subito order. Places two possibilities:
     * 1) If exists subject from mysql database 2) if not from subito.ini.
     *
     * @params array $departmentdetails     Details of department.
     *
     * @return string
     * @access private
     * @throws DDSException                 No or false variables set at DDS.ini.
     */
    private function setSubjectEmail($departmentdetails)
    {
        try {
            if (true === $this->useConfigFromDatabase()) {
                if (isset($departmentdetails['branchSubject']) &&
                    $departmentdetails['branchSubject'] != ''
                ) {
                    return $departmentdetails['branchSubject'];
                }
            }
            if (isset($this->config['DDS']['subject'])) {
                return $this->config['DDS']['subject'];
            }
            throw new DDSException ('No subject for document delivery service is set.');
        } catch (DDSException $e) {
            throw new DDSException($e->getMessage());
        }
    }

    /**
     * Insert order of an article
     *
     * @param array $data     data to insert
     *
     * @access public
     * @return mixed $notice  True if insert worked, error message if failed
     */
    public function storeOrderOfItem($data)
    {
        $apimap = [
            'userid' => 'user_id',
            'department' => 'department_id',
            'inputdepartment' => 'department',
            'journal' => 'article',
            'publishdate' => 'publishing_date'
        ];

        foreach ($apimap as $key => $apikey) {
            $query[$apikey] = $data[$key];
        }

        return $this->httpServiceRequest(
            '/order',
            'post',
            http_build_query($query)
        );
    }

    /**
     * Store a setting (department & division) of an user for reusing.
     *
     * @param array $data Data to insert
     *
     * @return mixed $notice   True if insert worked, error message if failed
     * @access public
     */
    public function storeUserDetail($data)
    {
        $apimap = [
            'userid' => 'user_id',
            'department' => 'department_id',
            'inputdepartment' => 'department',
            'division' => 'division_id'
        ];

        foreach ($apimap as $key => $apikey) {
            $query[$apikey] = $data[$key];
        }

        return $this->httpServiceRequest(
            '/user-details/' . $data['userid'],
            'put',
            http_build_query($query)
        );
    }



    /**
     * Check if config data should be defined in database or DDS.ini.
     *
     * @return boolean      True if recipient data should taken from database.
     * @access private
     */
    private function useConfigFromDatabase()
    {
        return (isset($this->config['DDS']['use_database']) &&
            $this->config['DDS']['use_database'] != '')
            ? true : false;
    }

}