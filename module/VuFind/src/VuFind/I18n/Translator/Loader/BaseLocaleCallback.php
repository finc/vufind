<?php

namespace VuFind\I18n\Translator\Loader;

class BaseLocaleCallback implements CallbackInterface
{
    public function __invoke(array $args, array $opts, \Closure $run): \Generator
    {
        if ('messages' !== $args['task']) {
            return;
        }

        if ($args['locale'] = $this->getBaseLocale($args['locale'])) {
            yield from $run($args);
        }
    }

    protected function getBaseLocale(string $locale)
    {
        $parts = array_slice(array_reverse(explode('-', $locale)), 1);
        return implode('-', array_reverse($parts));
    }
}