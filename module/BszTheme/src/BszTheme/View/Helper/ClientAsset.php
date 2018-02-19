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
    
    protected $library;
    
    protected $website;
    
    /**
     * The first part of the domain name
     * 
     * @param string $tag
     */
    public function __construct($tag, $website, $library = null) {
        
        $this->tag = $tag;
        $this->library = $library;
        $this->website = $website;

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
        $filename = '';
        if ($this->library === null) {
            $filename = 'logo/'.$this->tag.'.png';            
        } else if ($this->library instanceof \Bsz\Config\Library) {
            $filename = $this->library->getLogo();
        }

        if (file_exists('/usr/local/boss/themes/bodensee/images/'.$filename)) {
            return $filename;
        }
        return '';
    }
    
    public function getLogoHtml() {
        
        return $this->getView()->render('bsz/logo.phtml', [
            'website' => $this->website, 
            'imglink' => $this->getLogo()
        ]);
    }
    
}

