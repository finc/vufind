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
                ThemeInfo::class => 'Factory::getThemeInfosss',
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
                'client' =>         'BszTheme\View\Helper\Factory::getClient',
                'clientAsset' =>    'BszTheme\View\Helper\Factory::getClientAsset',
                'IllForm' =>        'BszTheme\View\Helper\Bodensee\Factory::getIllForm',
                'libraries' =>      'BszTheme\View\Helper\Factory::getLibraries',
            ],
            'invokables' => [
                'mapper'        => 'BszTheme\View\Helper\FormatMapper',
                'string'        => 'BszTheme\View\Helper\StringHelper',
            ],
        ];
    }
}
