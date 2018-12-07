<?php

namespace VuFind\I18n\Translator\Loader;

interface CallbackInterface
{
    public function __invoke(array $args, array $opts, \Closure $run): \Generator;
}