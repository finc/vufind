<?php

namespace VuFind\I18n\Translator\Loader;

use Interop\Container\ContainerInterface;

use Zend\ServiceManager\Factory\FactoryInterface;

class CallbackManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName($container);
    }
}