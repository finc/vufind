<?php

namespace BszTheme;

use Zend\ServiceManager\ServiceManager;

/**
 * VuFind creates its ThemeInfo in a dynamic way. We use a factory here
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ThemeInfoFactory extends \VuFindTheme\ThemeInfoFactory
{
    
   
    /**
     * Create ThemeInfo instance
     * @param ServiceManager $sm
     * @return \BszTheme\ThemeInfo
     */
    public static function getThemeInfo(ServiceManager $sm) 
    {
        $host = $sm->get('Request')->getHeaders()->get('host')->getFieldValue();
        $parts = explode('.', $host);
        $tag = isset($parts[0]) ? $parts[0] : 'swb';     
        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee', $tag);
    }
}
