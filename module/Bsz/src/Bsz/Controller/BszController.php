<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Bsz\Controller;
use Zend\Session\Container;
/**
 * Für statische Seiten etc. 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class BszController extends \VuFind\Controller\AbstractBase {
   
    public function __construct() {
        // you need this constructor even if it's empty
        // error_reporting(E_ALL);
    }    
    
    
    /**
     * Write isil into Session 
     */
    public function saveIsilAction() {
        
        $sl = $this->getServiceLocator();
        $client = $sl->get('Bsz\Client');
        // $client = $this->getServiceLocator()->get('Bsz\Client');
        $libraries = $this->getServiceLocator()->get('Bsz\Libraries');  
        
        $isilsRoute = explode(',', $this->params()->fromRoute('isil'));       
        $isilsGet = (array)$this->params()->fromQuery('isil');
        $isils = array_merge($isilsRoute, $isilsGet);

        if(!is_array($isils)) {
            $isils = (array)$isils;
        }
        foreach ($isils as $key => $isil) {
            if (strlen($isil) < 1) {
                unset($isils[$key]);
            } 
        }
        if (count($isils) == 0) {
            throw new \Bsz\Exception('parameter isil missing');
        }
        if (count($isils) > 0) {
            $container = new \Zend\Session\Container(
                'fernleihe', $this->getServiceLocator()->get('VuFind\SessionManager')
            );
            $container->offsetSet('isil', $isils);     
            $uri= $this->getRequest()->getUri();
            $cookie = new \Zend\Http\Header\SetCookie(
                    'isil', 
                    implode(',', $isils), 
                    time() + 14 * 24* 60 * 60, 
                    '/',
                    $uri->getHost() );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);
        } 
        $referer = $this->params()->fromQuery('referer');
        // try to get referer from param
        if (empty($referer)) {
            $referer = $this->params()->fromHeader('Referer');  
        } 
        if (is_object($referer)) {
            $referer = $referer->getFieldValue();
        }
        if (!empty($referer) && strpos($referer, 'saveIsil') === FALSE
                && ( strpos($referer, '.boss') > 0 
                    || strpos($referer, '.localhost') > 0)
        ) {
            return $this->redirect()->toUrl($referer);
        } else {
            return $this->forwardTo('search', 'home');            
        }
        //Forward back to home action
    }
    
    /*
     * Curls an url and returns html
     */
    public function curlAction() {
        $url = $this->params()->fromPost('url');
        $config = [
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => [
                CURLOPT_FOLLOWLOCATION => true,
            ],
        ];
        $Client = new Zend\Http\Client($url, $config);
        return $this->getResponse();
    }
    
   
    /**
     * Show Privacy information
     */
    public function privacyAction() {

    }
    
    /**
     * Offers a searchbox only layout for iframe embedding
     */
    public function frameAction() {
        
       $view = $this->createViewModel();
       $view->setTerminal(true);
       return $view;
    }   
    
    public function dedupAction() {
        
        $config = $this->getServiceLocator()->get('VuFind/Config')->get('config')->get('Index');
        
        $container = $this->restoreFromCookie();        
        
       
        $post = $this->params()->fromPost();     
        
        // store form date in session and cookie
        if (isset($post['submit_dedup_form'])) {
           
            $uri= $this->getRequest()->getUri();
            $cookie = new \Zend\Http\Header\SetCookie(
                    'group', 
                    $post['group'], 
                    time() + 14 * 24* 60 * 60, 
                    '/',
                    $uri->getHost() );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);
            $cookie = new \Zend\Http\Header\SetCookie(
                    'group_field', 
                    $post['group_field'], 
                    time() + 14 * 24* 60 * 60, 
                    '/',
                    $uri->getHost() );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);
            $cookie = new \Zend\Http\Header\SetCookie(
                    'group_limit', 
                    $post['group_limit'], 
                    time() + 14 * 24* 60 * 60, 
                    '/',
                    $uri->getHost() );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);
            
            
            $container->offsetSet('group', $post['group']);
            $container->offsetSet('group_field', $post['group_field']);     
            $container->offsetSet('group_limit', $post['group_limit']);     
            
            $this->FlashMessenger()->addSuccessMessage('dedup_settings_success');
            
            $params = [
               'group' => $post['group'],
               'field' => $post['group_field'],
               'limit' => $post['group_limit'],            
            ];            
        } else {
            // Load default values from session or config
            $params = [
               'group' => $container->offsetExists('group') ? $container->offsetGet('group') : $config->get('group'),
               'field' => $container->offsetExists('group_field') ? $container->offsetGet('group_field') : $config->get('group.field'),
               'limit' => $container->offsetExists('group_limit') ? $container->offsetGet('group_limit') : $config->get('group.limit'),            
            ];            
        }
        
        $view = $this->createViewModel();
        $view->setVariables($params);
        
        return $view;
        
    }
    
    public function restoreFromCookie() 
    {
        $container = new \Zend\Session\Container(
            'dedup', $this->getServiceLocator()->get('VuFind\SessionManager')
        );
        $cookie = $this->getRequest()->getCookie();
        
        if (isset($cookie->group)) {
            $container->offsetSet('group', $cookie->group);
        }
        if (isset($cookie->group_field)) {
            $container->offsetSet('group_field', $cookie->group_field);
        }
        if (isset($cookie->group_limit)) {
            $container->offsetSet('group_limit', $cookie->group_limit);
        }
        return $container;
    }

    
    
}

