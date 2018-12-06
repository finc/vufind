<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\I18n\Translator\TextDomain;

class BaseLocaleLoader implements MessageLoaderInterface, EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @param string $file
     * @return \Generator|TextDomain[]
     */
    public function __invoke(string $locale, string $textDomain): \Generator
    {

        if ($locale = $this->getBaseLocale($locale)) {
            $event = new MessageLoaderEvent($locale, $textDomain);
            yield from $this->getEventManager()->triggerEvent($event);
        }
    }

    protected function getBaseLocale(string $locale)
    {
        $parts = array_slice(array_reverse(explode('-', $locale)), 1);
        return implode('-', array_reverse($parts));
    }
}