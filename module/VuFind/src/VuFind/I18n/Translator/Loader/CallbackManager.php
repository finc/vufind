<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;

class CallbackManager extends AbstractPluginManager
{
    protected $factories = [
        BaseLocaleCallback::class => InvokableFactory::class,
        DirectoryCallback::class => InvokableFactory::class,
        IniFileCallback::class => InvokableFactory::class,
        ParentFilesCallback::class => InvokableFactory::class,
        YamlFileCallback::class => InvokableFactory::class,
    ];

    protected $instanceOf = CallbackInterface::class;
}