<?php

namespace BszTheme;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * VuFind creates its ThemeInfo in a dynamic way. We use a factory here
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ThemeInfoFactory extends \VuFindTheme\ThemeInfoFactory
{
    
    
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        return new $requestedName(
            realpath(APPLICATION_PATH . '/themes'), 'bodensee'
        );
    }
    
    /**
     * Create ThemeInfo instance
     * @param ServiceManager $sm
     * @return \BszTheme\ThemeInfo
     */
//    public static function getThemeInfo(ServiceManager $sm) 
//    {
//        $host = $sm->get('Request')->getHeaders()->get('host')->getFieldValue();
//        $parts = explode('.', $host);
//        $tag = isset($parts[0]) ? $parts[0] : 'swb';     
//        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee', $tag);
//    }
}
