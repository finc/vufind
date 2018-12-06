<?php

namespace VuFind\I18n\Translator\Loader;

interface FileLoaderInterface
{
    const EVENT = FileLoaderEvent::class;

    public function __invoke(string $file): \Generator;
}