<?php

namespace BszTheme\View\Helper;
use Zend\View\Helper\AbstractHelper,
    Zend\View\Renderer\RendererInterface as Renderer,
    Zend\Session\Container;


/**
 * This view helper is used to provide client specific assets, like images. 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ClientAsset extends AbstractHelper
{
    protected $tag;
    
    protected $isil = null;
    
    /**
     * The first part of the domain name
     * 
     * @param string $tag
     */
    public function __construct($tag) {
        
        $this->tag = $tag;
        
        $container = new Container('fernleihe');
        if ($container->offsetExists('isil')) {
            $this->isil = (array)$container->offsetGet('isil');
        }
    }
    
    public function __invoke() {
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function getHeader() {
        return 'header/'.$this->tag.'.jpg';
    }
    
    /**
     * 
     * @return string
     */
    public function getLogo() {
        return 'logo/'.$this->tag.'.png';
    }
    
}

