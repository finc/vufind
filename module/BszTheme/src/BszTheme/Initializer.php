<?php

namespace BszTheme;

use Zend\Config\Config;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\View\Http\InjectTemplateListener as BaseInjectTemplateListener;
use Zend\Stdlib\RequestInterface as Request;

/**
 * Bsz implemantation of VuFindTheme initializer
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Initializer extends \VuFindTheme\Initializer
{
        /**
     * Constructor
     *
     * @param Config   $config Configuration object containing these keys:
     * <ul>
     *   <li>theme - the name of the default theme for non-mobile devices</li>
     *   <li>mobile_theme - the name of the default theme for mobile devices
     * (omit to disable mobile support)</li>
     *   <li>alternate_themes - a comma-separated list of alternate themes that
     * can be accessed via the ui GET parameter; each entry is a colon-separated
     * parameter-value:theme-name pair.</li>
     *   <li>selectable_themes - a comma-separated list of themes that may be
     * selected through the user interface; each entry is a colon-separated
     * name:description pair, where name may be 'standard,' 'mobile,' or one of
     * the parameter-values from the alternate_themes array.</li>
     *   <li>generator - a Generator value to display in the HTML header
     * (optional)</li>
     * </ul>
     * @param MvcEvent $event  Zend MVC Event object
     */
    public function __construct(Config $config, MvcEvent $event)
    {
        // Store parameters:
        $this->config = $config;
        $this->event = $event;

        // Grab the service manager for convenience:
        $this->serviceManager = $this->event->getApplication()->getServiceManager();

        // Get the cookie manager from the service manager:
        $this->cookieManager = $this->serviceManager->get('VuFind\CookieManager');

        // Get base directory from tools object:
        $this->tools = $this->serviceManager->get('BszTheme\ThemeInfo');

        // Set up mobile device detector:
        $this->mobile = $this->serviceManager->get('VuFindTheme\Mobile');
        $this->mobile->enable(isset($this->config->mobile_theme));
    }
}
