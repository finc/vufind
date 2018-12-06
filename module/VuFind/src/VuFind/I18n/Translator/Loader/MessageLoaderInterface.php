<?php

namespace VuFind\I18n\Translator\Loader;

interface MessageLoaderInterface
{
    const EVENT = MessageLoaderEvent::class;

    public function __invoke(string $locale, string $textDomain): \Generator;
}