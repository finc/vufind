<?php

namespace BszTheme;

use Zend\ServiceManager\ServiceManager;

/**
 * VuFind creates its ThemeInfo in a dynamic way. We use a factory here
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Create ThemeInfo instance
     * @param ServiceManager $sm
     * @return \BszTheme\ThemeInfo
     */
    public static function getThemeInfo(ServiceManager $sm) {
        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee');
    }
}
