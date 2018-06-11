<?php

namespace Bsz\Config;
use Zend\Session\Container;

/**
 * Class for setting dedup options
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Dedup
{
    protected $config;
    /**
     *
     * @var Container
     */
    protected $container;
    protected $response;
    protected $cookiedata;
    
    public function __construct($config, Container $container, $response, $cookiedata)
    {
        $this->config = $config;
        $this->container = $container;
        $this->response = $response;
        $this->cookie = $cookiedata;
        $this->restoreFromCookie();
    }

    public function restoreFromCookie() 
    {
       
        if (isset($this->cookie->group)) {
            $this->container->offsetSet('group', $this->cookie->group);
        }
        if (isset($this->cookie->group_field)) {
            $this->container->offsetSet('group_field', $this->cookie->group_field);
        }
        if (isset($this->cookie->group_limit)) {
            $this->container->offsetSet('group_limit', $this->cookie->group_limit);
        }
    }
    
    public function store($post)
    {
        $cookie = new \Zend\Http\Header\SetCookie(
                'group', 
                $post['group'], 
                time() + 14 * 24* 60 * 60, 
                '/');
        $header = $this->response->getHeaders();
        $header->addHeader($cookie);
        $cookie = new \Zend\Http\Header\SetCookie(
                'group_field', 
                $post['group_field'], 
                time() + 14 * 24* 60 * 60, 
                '/');
        $header = $this->response->getHeaders();
        $header->addHeader($cookie);
        $cookie = new \Zend\Http\Header\SetCookie(
                'group_limit', 
                $post['group_limit'], 
                time() + 14 * 24* 60 * 60, 
                '/');
        $header = $this->response->getHeaders();
        $header->addHeader($cookie);


        $this->container->offsetSet('group', $post['group']);
        $this->container->offsetSet('group_field', $post['group_field']);     
        $this->container->offsetSet('group_limit', $post['group_limit']);     

        $params = [
           'group' => $post['group'],
           'field' => $post['group_field'],
           'limit' => $post['group_limit'],            
        ]; 
        return $params;
    }
    
    public function getCurrentSettings() 
    {
        $params = [
           'group' => $this->container->offsetExists('group') ? $this->container->offsetGet('group') : $this->config->get('group'),
           'field' => $this->container->offsetExists('group_field') ? $this->container->offsetGet('group_field') : $this->config->get('group.field'),
           'limit' => $this->container->offsetExists('group_limit') ? $this->container->offsetGet('group_limit') : $this->config->get('group.limit'),            
        ];
        return $params;
    }
}
