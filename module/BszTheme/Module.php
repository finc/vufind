<?php


namespace BszTheme;
use Zend\Mvc\MvcEvent;
/**
 * Bsz theme adaption
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Module extends \VuFindTheme\Module
{
        /**
     * Get autoloader configuration
     *
     * @return void
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
    /**
     * Factory function for ThemeInfo object.
     *
     * @return ThemeInfo
     */
    public static function getThemeInfo()
    {
        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee');
    }
}
