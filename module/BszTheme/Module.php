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
     * Here, we override the VuFindTheme module with our own module
     * @return []
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'VuFindTheme\MixinGenerator' =>
                    'VuFindTheme\Module::getMixinGenerator',
                'VuFindTheme\ThemeCompiler' =>
                    'VuFindTheme\Module::getThemeCompiler',
                'VuFindTheme\ThemeGenerator' =>
                    'VuFindTheme\Module::getThemeGenerator',
                'VuFindTheme\ThemeInfo' => 'BszTheme\Factory::getThemeInfo',
            ],
            'invokables' => [
                'VuFindTheme\Mobile' => 'VuFindTheme\Mobile',
                'VuFindTheme\ResourceContainer' => 'VuFindTheme\ResourceContainer',
            ],
        ];
    }  
    
        /**
     * Get view helper configuration.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'Client' =>         'BszTheme\View\Helper\Factory::getClient',
                'ClientAsset' =>    'BszTheme\View\Helper\Factory::getClientAsset',
                'IllForm' =>        'BszTheme\View\Helper\Bodensee\Factory::getIllForm',
                'openurl' =>        'BszTheme\View\Helper\Bodensee\Factory::getOpenZrl',
                'Libraries' =>      'BszTheme\View\Helper\Factory::getLibraries',
            ],
            'invokables' => [
                'mapper'        => 'BszTheme\View\Helper\FormatMapper',
                'string'        => 'BszTheme\View\Helper\StringHelper',
            ],
        ];
    }
}
