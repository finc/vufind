<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\EventManager\Event;

class MessageLoaderEvent extends Event
{
    /**
     * LoadMessagesEvent constructor.
     * @param string $locale
     * @param string $textDomain
     */
    public function __construct(string $locale, string $textDomain)
    {
        parent::__construct(static::class, null, compact('locale', 'textDomain'));
    }
}