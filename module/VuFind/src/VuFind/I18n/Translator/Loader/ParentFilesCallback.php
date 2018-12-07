<?php

namespace VuFind\I18n\Translator\Loader;

use VuFind\I18n\Translator\TranslatorRuntimeException;
use Zend\Uri\Uri;

class ParentFilesCallback implements CallbackInterface
{
    public function __invoke(array $args, array $opts, \Closure $run): \Generator
    {
        if ($args['task'] !== 'parents') {
            return;
        }

        $file = $args['file'];
        $args['task'] = 'file';

        foreach ($args['parents'] as $parent) {
            $args['file'] = (string)Uri::merge(new Uri($file), $parent);
            foreach ($run($args) as $ancestor => $data) {
                if ($file === $ancestor) {
                    throw new TranslatorRuntimeException("Circular chain of language files at $file");
                }
                yield $ancestor => $data;
            }
        }
    }
}