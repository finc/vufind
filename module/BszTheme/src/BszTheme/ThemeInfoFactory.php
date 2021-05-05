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
     *
     * @param ServiceManager $sm
     *
     * @return ThemeInfo
     */
    public static function getThemeInfo(ServiceManager $sm)
    {
        $request = $sm->get('Request');
        $tag = 'swb';
        if ($request instanceof \Zend\Http\Request ) {
            $host = $request->getHeaders()->get('host')->getFieldValue();
            $parts = explode('.', $host);

            // spacial case ireon-portal.de
            if ($parts[0] == 'ireon-portal') {
                $parts[0] = 'ireon';
            }

            $tag = $parts[0] ?? 'swb';
        }
        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee', $tag);
    }
}
