<?php

namespace VuFind\I18n\Translator\Loader;

use Zend\EventManager\Filter\FilterIterator;
use Zend\EventManager\FilterChain;
use Zend\I18n\Translator\TextDomain;

class PriorityChainLoader implements LoaderInterface
{
    /**
     * @var FilterChain
     */
    protected $chain;

    public function __construct()
    {
        $this->chain = new FilterChain();
    }

    public function attach(callable $callback, array $opts, int $prio)
    {
        return $this->chain->attach($this->adjust($callback, $opts), $prio);
    }

    /**
     * @param string $locale
     * @param string $textDomain
     * @return \Generator|TextDomain[]
     */
    public function load(string $locale, string $textDomain): \Generator
    {
        $task = 'messages';
        $args = compact('task', 'locale', 'textDomain');
        yield from $this->chain->run($this->chain, $args);
    }

    protected function adjust(callable $callback, array $opts): \Closure
    {
        return function (FilterChain $chain, array $args, FilterIterator $rest) use ($callback, $opts) {
            yield from call_user_func($callback, $args, $opts, function ($args) use ($chain) {
                yield from $chain->run($chain, $args);
            });
            yield from $rest->next($chain, $args, $rest) ?? [];
        };
    }
}