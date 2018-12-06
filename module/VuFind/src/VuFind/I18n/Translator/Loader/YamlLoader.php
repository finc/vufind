<?php

namespace VuFind\I18n\Translator\Loader;

use Symfony\Component\Yaml\Yaml as Parser;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\I18n\Translator\TextDomain;

class YamlLoader implements FileLoaderInterface, EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    public function __invoke(string $file): \Generator
    {
        if (!$this->canLoad($file)) {
            return;
        }

        yield $file => $data = new TextDomain(Parser::parseFile($file) ?? []);


        if ($extendedFiles = $data['@extends'] ?? []) {
            yield from $this->getEventManager()
                ->triggerEvent(new ExtendedFilesLoaderEvent($file, $extendedFiles));
        }
        // TODO: put logic into ExtendedFilesLoader
//        foreach ($data['@extends'] ?? [] as $extendedFile) {
//            $files = $this->getEventManager()
//                ->triggerEvent(new FileLoaderEvent($extendedFile));
//            foreach ($files as $loadedFile => $data) {
//                if ($file === $loadedFile) {
//                    // throw
//                }
//                yield $loadedFile => $data;
//            }
//        }
    }


    protected function canLoad(string $file): bool
    {
        return in_array(pathinfo($file, PATHINFO_EXTENSION), ['yml', 'yaml']);
    }
}