<?php
/**
 * Record Controller
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
 * @category VuFind2
 * @package  Controller
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Controller;
use VuFind\RecordDriver\AbstractBase as AbstractRecordDriver;
use Zend\View\Model\ViewModel;

/**
 * This class was created to make a default record tab behavior possible
 */
class RecordController extends \VuFind\Controller\RecordController
{
    use \VuFind\Controller\HoldsTrait;
    use \VuFind\Controller\ILLRequestsTrait;
    use \VuFind\Controller\StorageRetrievalRequestsTrait;
    
    const TIMEOUT = 30;
    
     /**
     * Default tab for Solr is holdings, excepts its a collection, then volumes. 
     *
     * @param AbstractRecordDriver $driver Record driver
     *
     * @return string
     */
    protected function getDefaultTabForRecord(AbstractRecordDriver $driver)
    {
        // Load configuration:
        $config = $this->getTabConfiguration();

        // Get the current record driver's class name, then start a loop
        // in case we need to use a parent class' name to find the appropriate
        // setting.
        $className = get_class($driver);
        while (true) {
            $multipart = $driver->tryMethod('getMultipartLevel');
            if(isset($multipart)) {
                if($multipart == \Bsz\RecordDriver\SolrMarc::MULTIPART_COLLECTION) {
                    return 'Volumes';
                }
                else {
                    return 'Holdings';
                }
            }
            elseif (isset($config[$className]['defaultTab'])) {
                return $config[$className]['defaultTab'];
            }
            $className = get_parent_class($className);
            if (empty($className)) {
                // No setting found...
                return null;
            }
        }
    }
    
     /**
     * Render ILL form, check password and submit
     */
    public function ILLFormAction()
    {
        $isils = $this->params()->fromQuery('isil');
        if (count($isils) > 0) {
            $this->processIsil();
        }
        $config = $this->getServiceLocator()->get('bsz\client')->get('ILL');
        // If Request does not have this param, we should not use collapsible 
        // panels
        $success = null;
        $this->driver = $this->loadRecord();
        $this->log = $this->getServiceLocator()->get('vufind\logger');
        $this->baseUrl = $this->isTestMode() ? $config->get('baseurl_test') :
                $config->get('baseurl_live');

        $params = $this->params()->fromPost();
        
        $authManager = $this->getServiceLocator()->get('VuFind\AuthManager');
        $client = $this->getServiceLocator()->get('Bsz\Client');
        if ($client->isIsilSession() && !$client->hasIsilSession()) {
            throw new \Bsz\Exception('You must select a library to continue');
            $this->FlashMessenger()->addErrorMessage('missing_isil');
        } 
        $libraries = $this->getServiceLocator()->get('bsz\libraries');
        $first = $libraries->getFirst($client->getIsils());
        $submitDisabled = false;
        
        if ($authManager->loginEnabled() 
                && !$authManager->isLoggedIn()
                && $first->getAuth() == 'shibboleth') {
            $this->FlashMessenger()->addErrorMessage('You must be logged in first');
            $submitDisabled = true;
        }

        // validate form data
        if (isset($params['Bestellform'])) {

            // use regex to trim username
            if (isset($first) && strlen($first->getRegex()) > 0 && $first->getAuth() == 'shibboleth') {
                $params['BenutzerNummer'] = preg_replace($first->getRegex(), "$1", $params['BenutzerNummer']);         
            }
            // response is  okay
            if ($this->checkAuth($params)) {
                // remove password from TAN field
                unset($params['Passwort']);
                unset($params['TAN']);
                
                // send real order
                $client = new \Zend\Http\Client();
                try {
                    $client->setAdapter('\Zend\Http\Client\Adapter\Curl')
                            ->setUri($this->baseUrl . "/flcgi/pflauftrag.pl")
                            ->setMethod('POST')
                            ->setOptions(['timeout' => static::TIMEOUT])
                            ->setParameterPost($params)
                            ->setAuth($config->get('basic_auth_user'), str_rot13($config->get('basic_auth_pw')));
                    $response = $client->send();
                    // Parse response HTML
                    $dom = new \Zend\Dom\Query($response->getContent());
                    $message = $dom->queryXPath('ergebnis')->getDocument();
                    $success = $this->parseResponse($message);
                } catch (\Exception $ex) {
                    $this->log->logException($ex, $this->getRequest()->getServer());
                    $this->FlashMessenger()->addErrorMessage('ill_request_error_technical');
                }
            } else { // wrong credentials
                $this->FlashMessenger()->addErrorMessage('ill_request_error_blocked');
                $success = false;
            }
        }
        $uri= $this->getRequest()->getUri();
        $cookie = new \Zend\Http\Header\SetCookie(
            'orderStatus', 
            $success, 
            time()+ 60 * 60 * 2, 
            '/',
            $uri->getHost() );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);

        $view = $this->createViewModel([
                    'driver' => $this->driver,
                    'success' => $success,
                    'test' => $this->isTestMode(),
                    'params' => $params,
                    'submitDisabled' => $submitDisabled,
                ])->setTemplate('record/illform');
        return $view;
    }
    
    public function freeFormAction() {
        // if one accesses this form with a library that uses custom form, 
        // redirect. 
        $client = $this->getServiceLocator()->get('Bsz\Client');
                $authManager = $this->getServiceLocator()->get('VuFind\AuthManager');
        $isils = $this->params()->fromQuery('isil');
        if ($client->isIsilSession() && !$client->hasIsilSession() && count($isils) == 0) {
            $this->FlashMessenger()->addErrorMessage('missing_isil');
            throw new \Bsz\Exception('You must select a library to continue');
        }
        if (count($isils) > 0) {
            $this->processIsil();
        }
        $libraries = $this->getServiceLocator()->get('bsz\libraries');
        $first = $libraries->getFirst($client->getIsils());
        if ($first !== null && $first->hasCustomUrl()) {
            return $this->redirect()->toUrl($first->getCustomUrl());
        }
        $submitDisabled = false;
        if ($first !== null && $authManager->loginEnabled() 
                && !$authManager->isLoggedIn()
                && $first->getAuth() == 'shibboleth') {
            $this->FlashMessenger()->addErrorMessage('You must be logged in first');
            $submitDisabled = true;
        }      
        
                
        $view = $this->createViewModelWithoutRecord([
            'success' => null,
            'driver' => null,
            'test' => $this->isTestMode(),
            'submitDisabled' => $submitDisabled
        ]);
        $view->setTemplate('record/illform.phtml');
        return $view;
    }

    /**
     * Determin if we should use the test or live url. 
     * @return boolean
     */
    public function isTestMode()
    {
        $client = $this->getServiceLocator()->get('Bsz\Client');
        $libraries = $this->getServiceLocator()->get('bsz\libraries');
        $libraries = $libraries->getActive($client->getIsils());
        $test = true;
        foreach ($libraries as $library) {
            if ($library->isLive()) {
                $test = false;
            }
        }
        return $test;
    }

    /**
     * Determin if we should use the test or live url. 
     * @return boolean
     */
    public function getCustomUrl()
    {
        $client = $this->getServiceLocator()->get('Bsz\Client');
        $libraries = $this->getServiceLocator()->get('bsz\libraries');
        $libraries = $libraries->getActive($client->getIsils());
        $custom = '';
        foreach ($libraries as $library) {
            if ($library->hasCustomUrl()) {
                return $library->getCustomUrl();
            }
        }
        return '';
    }

    /**
     * 
     * @param string $sigel
     * @return \Bsz\Config\Library
     */
    public function getLibraryBySigel($sigel)
    {
        $client = $this->getServiceLocator()->get('Bsz\Client');
        $libraries = $this->getServiceLocator()->get('bsz\libraries');
        $libraries = $libraries->getActive($client->getIsils());

        foreach ($libraries as $library) {
            if ($library->getSigel() == $sigel) {
                return $library;
            }
        }
        return null;
    }

    /**
     * Check credentials
     * 
     * @param array $params
     * 
     * @return bool
     */
    public function checkAuth($params)
    {
        $library = $this->getLibraryBySigel($params['Sigel']);
        $config = $this->getServiceLocator()->get('bsz\client')->get('ILL');        

        if (isset($library)) {
            // is shibboleth auth is used, we do not need to check anything. 
            $authManager = $this->getServiceLocator()->get('VuFind\AuthManager');
            if ($authManager->loginEnabled() && $authManager->isLoggedIn()) {
                return true;
            }
            $client = new \Zend\Http\Client();
            $client->setAdapter('\Zend\Http\Client\Adapter\Curl')
                    ->setUri($this->baseUrl . '/flcgi/endnutzer_auth.pl')
                    ->setMethod('POST')
                    ->setParameterPost([
                        'sigel' => $params['Sigel'],
                        'auth_typ' => $library->getAuth(),
                        'user' => $params['BenutzerNummer'],
                        'passwort' => $library->getAuth() == 'tan' ?
                                $params['TAN'] : $params['Passwort'],
                    ])
                    ->setOptions(['timeout' => static::TIMEOUT])
                    ->setAuth($config->get('basic_auth_user'), str_rot13($config->get('basic_auth_pw')));
            $response = $client->send();

            try {
                $xml = simplexml_load_string($response->getContent());
            } catch (\Exception $ex) {
                $this->log->logException($ex, $this->getRequest()->getServer());
                $this->FlashMessenger()->addErrorMessage('ill_request_error_technical');
            }
            return (isset($xml->status) && $xml->status == 'FLOK');            
        } else {
            $this->FlashMessenger()->addErrorMessage('ill_request_error_blocked');
            return false;
        }
    }

    /**
     * Parse HTML response from server and output message
     * 
     * @param $html
     * 
     * @return boolean
     */
    public function parseResponse($html)
    {
        if (strpos($html->textContent, 'Bestell-Id') !== FALSE) {
            $this->FlashMessenger()->addSuccessMessage('ill_request_submit_ok');
            return true;
        } else {
            // Three matches, the last is the correct message string. 
            try {
                $error_reporting = error_reporting();
                error_reporting(0);
                preg_match_all('/(Fehler \([a-zA-z]*\): )(.*)/', $html->textContent, $matches);
                $msgText = end($matches);
                $msgText = array_shift($msgText);
                $this->FlashMessenger()->addInfoMessage($msgText);
                error_reporting($error_reporting);
            } catch (\Exception $ex) {
                $this->FlashMessenger()->addErrorMessage('ill_request_submit_failure');
            }
            return false;
        }
    }
    /**
     * Abstract method implementations
     */
    public function getBreadcrumb()
    {
        return parent::getBreadcrumb();
    }

    public function getUniqueID()
    {
        return parent::getUniqueID();
    }
    
    /**
     * Redirect to saveIsil Action
     * 
     * @return redirect
     */
    public function processIsil() 
    {
        $isils = $this->params()->fromQuery('isil');
        $uri = $this->getRequest()->getUri();
        // remove isil from params - otherwise we get a redirection loop
        $params = $this->params()->fromQuery();
        unset($params['isil']);
        
        $referer = sprintf("%s://%s%s?%s", $uri->getScheme(), $uri->getHost(),
            $uri->getPath(), http_build_query($params));
        
        $params = [                
            'referer' => $referer,
            'isil' => $isils,
        ];           
        /**
         * TODO: Get this working with toRoute Redirect
         */
        return $this->redirect()->toUrl('/Bsz/saveIsil?'.
                http_Build_query($params));

        
    }
    
    public function createViewModelWithoutRecord($params = null) 
    {
        $layout = $this->params()
            ->fromPost('layout', $this->params()->fromQuery('layout', false));
        if ('lightbox' === $layout) {
            $this->layout()->setTemplate('layout/lightbox');
        }
        $view = new ViewModel($params);
        $this->layout()->searchClassId = $view->searchClassId = $this->searchClassId;
        // we don't use a driver in this action
        // $view->driver = $this->loadRecord();
        return $view;
    }

}
