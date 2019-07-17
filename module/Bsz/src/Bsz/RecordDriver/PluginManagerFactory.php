<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\RecordDriver;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;


/**
 * Description of PluginManagerFactory
 *
 * @author amzar
 */
class PluginManagerFactory extends \VuFind\ServiceManager\AbstractPluginManagerFactory {
    
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
        $configKey = $this->getConfigKey($requestedName);
        if (empty($configKey)) {
            $error = 'Problem determining config key for ' . $requestedName;
            throw new \Exception($error);
        }
        $config = $container->get('Config');
        return new PluginManager(
            $container, $config['vufind']['plugin_managers'][$configKey]
        );
    }
}
