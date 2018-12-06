<?php

namespace VuFind\I18n\Translator\Loader;


use Zend\EventManager\Event;

class FileLoaderEvent extends Event
{
    public function __construct(string $file)
    {
        parent::__construct(static::class);
    }
}