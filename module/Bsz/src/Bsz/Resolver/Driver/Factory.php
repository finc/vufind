<?php

namespace Bsz\Resolver\Driver;

use Zend\ServiceManager\ServiceManager;
/**
 * Factory for Resolver Drivers
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Factory for Ezb record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Ezb
     */
    public static function getEzb(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        return new Ezb(
            $config->OpenURL->url,
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }
    
     /**
     * Factory for Redi record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Redi
     */
    public static function getRedi(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        return new Redi(
            $config->OpenURL->url,
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }
    
        /**
     * Factory for Ezb record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Ezb
     */
    public static function getIll(ServiceManager $sm)
    {
        $libraries = $sm->getServiceLocator()->get('Bsz\Config\Libraries');
        // This is a special solution for UB Heidelberg
        $library = $libraries->getByIsil('DE-16');
        return new Ill(
            $library->getCustomUrl(),
            $sm->getServiceLocator()->get('VuFind\Http')->createClient()
        );
    }
}
