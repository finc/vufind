<?php

namespace VuFind\I18n\Translator\Loader;

use Symfony\Component\Yaml\Yaml as Parser;
use Zend\I18n\Translator\TextDomain;

class YamlFileCallback implements CallbackInterface
{
    public function __invoke(array $args, array $opts, \Closure $run): \Generator
    {
        if (!$this->isValidArgs($args)) {
            return;
        }

        yield $file = $args['file'] => $data = new TextDomain(Parser::parseFile($file) ?? []);

        $args['task'] = 'parents';
        $args['parents'] = (array)($data['@extends'] ?? []);

        yield from $run($args);
    }

    protected function isValidArgs(array $args): bool
    {
        return ($args['task'] ?? null) === 'file'
            && in_array(pathinfo($args['file'], PATHINFO_EXTENSION), ['yml', 'yaml']);
    }
}