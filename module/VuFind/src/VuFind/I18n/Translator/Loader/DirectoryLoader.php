<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Stdlib\Glob;

class DirectoryLoader implements MessageLoaderInterface, EventManagerAwareInterface
{
    use EventManagerAwareTrait;
    /**
     * @var string
     */
    protected $dir;

    /**
     * @var string
     */
    protected $ext;

    public function __construct(string $dir, string $ext)
    {
        $this->dir = realpath($dir);
        $this->ext = "{{$ext}}";
    }

    public function __invoke(string $locale, string $textDomain): \Generator
    {
        $dir = $textDomain === 'default' ? "$this->dir" : "$this->dir/$textDomain";
        foreach (Glob::glob("$dir/$locale.$this->ext", Glob::GLOB_BRACE) as $file) {
            yield from $this->getEventManager()
                ->triggerEvent(new FileLoaderEvent($file));
        }
    }

}