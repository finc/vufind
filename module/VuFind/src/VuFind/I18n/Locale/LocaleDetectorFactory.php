<?php
namespace VuFind\I18n\Locale;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use SlmLocale\Locale\Detector;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\CookieStrategy;
use SlmLocale\Strategy\QueryStrategy;
use VuFind\Cookie\CookieManager;

class LocaleDetectorFactory implements DelegatorFactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        array $options = null
    ) {
        /** @var Detector $detector */
        $detector = call_user_func($callback);
        /** @var LocaleSettings $settings */
        $settings = $container->get(LocaleSettings::class);
        $detector->setDefault($settings->getDefaultLocale());
        $detector->setSupported($settings->getEnabledLocales());
        $detector->setMappings($settings->getMappedLocales());

        foreach ($this->getStrategies() as $strategy) {
            $detector->addStrategy($strategy);
        }

        /** @var CookieManager $cookies */
        $cookies = $container->get(CookieManager::class);
        $detector->getEventManager()->attach(LocaleEvent::EVENT_FOUND,
            function (EventInterface $event) use ($cookies) {
                $cookies->set('language', $event->getParam('locale'));
            });

        return $detector;
    }

    protected function getStrategies()
    {
        yield new LocaleDetectorParamStrategy();
        yield $queryStrategy = new QueryStrategy();
        yield $cookieStrategy = new CookieStrategy();
        $queryStrategy->setOptions(['query_key' => 'lng']);
        $cookieStrategy->setCookieName('language');
    }
}
