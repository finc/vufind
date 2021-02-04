<?php
namespace VuFind\I18n\Translator\Loader\Handler;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

class HandlerManager extends AbstractPluginManager
{
    protected $factories = [
        DirectoryHandler::class => InvokableFactory::class,
        ExtensionHandler::class => InvokableFactory::class,
        IniFileHandler::class => InvokableFactory::class,
        InitialHandler::class => InvokableFactory::class,
        YamlFileHandler::class => InvokableFactory::class,
    ];

    protected $instanceOf = HandlerInterface::class;
}
