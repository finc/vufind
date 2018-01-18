<?php

namespace Bsz\View\Helper;
use Zend\View\Helper\AbstractHelper;


/**
 * Stellt Funktionen für den Mandant/die Sichten 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class FormatMapper extends AbstractHelper
{
    protected $_Mapper;
    
    public function __construct()
    {
        $this->_Mapper = new \Bsz\FormatMapper();
    }
    
    public function __invoke() {
        return $this->_Mapper;
    }
}
