<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\I18n\Translator\TextDomain;

interface LoaderInterface
{
    /**
     * @param string $locale
     * @param string $textDomain
     * @return \Generator|TextDomain[]
     */
    public function load(string $locale, string $textDomain): \Generator;
}