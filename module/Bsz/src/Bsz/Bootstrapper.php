<?php

namespace Bsz;

use Zend\Console\Console;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\RouteMatch;
use BszTheme\Initializer;

/**
 * Description of Bootstrapper
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Bootstrapper extends \Vufind\Bootstrapper
{
      
     /**
     * This method must be declared in this instance because the init methods 
      * are called in alphabetical order. 
     *
     * @return void
     */
    protected function initConfig()
    {
        // Create the configuration manager:
        $app = $this->event->getApplication();
        $serviceManager = $app->getServiceManager();
        $this->config = $serviceManager->get('VuFind\Config')->get('config');
    }
     /**
     * Set up theme handling.
     *
     * @return void
     */
    protected function initTheme()
    {
        // Themes not needed in console mode:
        if (Console::isConsole()) {
            return;
        }

        // Attach template injection configuration to the route event:
        $this->events->attach(
            'route', ['VuFindTheme\Initializer', 'configureTemplateInjection']
        );

        // Attach remaining theme configuration to the dispatch event at high
        // priority (TODO: use priority constant once defined by framework):
        $config = $this->config->Site;
        $callback = function ($event) use ($config) {
            $theme = new \BszTheme\Initializer($config, $event);
            $theme->init();
        };
        $this->events->attach('dispatch.error', $callback, 9000);
        $this->events->attach('dispatch', $callback, 9000);
    }
}
