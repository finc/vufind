<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\Stdlib\Glob;

class DirectoryCallback implements CallbackInterface
{
    public function __invoke(array $args, array $opts, \Closure $run): \Generator
    {
        if ('messages' !== $args['task']) {
            return;
        }

        list ($dir, $ext) = [realpath($opts['dir']), $opts['ext']];
        list ($locale, $textDomain) = [$args['locale'], $args['textDomain']];
        $dir = $textDomain === 'default' ? $dir : "$dir/$textDomain";

        $args['task'] = 'file';
        foreach (Glob::glob("$dir/$locale.{{$ext}}", Glob::GLOB_BRACE) as $file) {
            yield from $run(compact('file') + $args);
        }
    }
}
