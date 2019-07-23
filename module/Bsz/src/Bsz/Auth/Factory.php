<?php

namespace Bsz\Auth;

use Zend\ServiceManager\ServiceManager;


/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
        /**
     * Construct the authentication manager.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Manager
     */
    public static function getManager(ServiceManager $sm)
    {
        // Set up configuration:
        $config = $sm->get('VuFind\Config')->get('config');
        $client = $sm->get('Bsz\Config\Client');
        $libraries = $sm->get('Bsz\Config\Libraries');
        $library = null;
        if ($client->isIsilSession()) {
            $library = $libraries->getFirstActive($client->getIsils());            
        }
        try {
            // Check if the catalog wants to hide the login link, and override
            // the configuration if necessary.
            $catalog = $sm->get('VuFind\ILSConnection');
            if ($catalog->loginIsHidden()) {
                $config = new \Zend\Config\Config($config->toArray(), true);
                $config->Authentication->hideLogin = true;
                $config->setReadOnly();
            }
        } catch (\Exception $e) {
            // Ignore exceptions; if the catalog is broken, throwing an exception
            // here may interfere with UI rendering. If we ignore it now, it will
            // still get handled appropriately later in processing.
            error_log($e->getMessage());
        }

        // Load remaining dependencies:
        $userTable = $sm->get('VuFind\DbTablePluginManager')->get('user');
        $sessionManager = $sm->get('VuFind\SessionManager');
        $pm = $sm->get('VuFind\AuthPluginManager');
        $cookies = $sm->get('VuFind\CookieManager');
        $csrf = $sm->get(\VuFind\Validator\Csrf::class);

        // Build the object and make sure account credentials haven't expired:
        $manager = new Manager($config, $userTable, $sessionManager, $pm, $cookies, $csrf, $library);
        $manager->checkForExpiredCredentials();
        return $manager;
    }
        /**
     * Construct the Shibboleth plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Shibboleth
     */
    public static function getShibboleth(ServiceManager $sm)
    {
        return new Shibboleth(
            $sm->getServiceLocator()->get('VuFind\SessionManager'),
            $sm->getServiceLocator()->get('Bsz\Config\Libraries')
        );
    }
    
}
