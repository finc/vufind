<?php

namespace VuFind\Config;

use Symfony\Component\Yaml\Yaml as YamlParser;
use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\Config\Reader\Ini as IniReader;
use Zend\Config\Reader\Yaml as YamlReader;

const CONFIG_PATH = APPLICATION_PATH . '/config/config.php';

class Manager implements PluginManager
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct()
    {
        $this->registerIniReader();
        $this->registerYamlReader();
        $this->config = new Config((require CONFIG_PATH)());
    }

    public function get($name)
    {
        $config = $this->config->{$name};
        return $config;
    }

    protected function registerYamlReader()
    {
        $reader = new YamlReader([YamlParser::class, 'parse']);
        Factory::registerReader('yaml', $reader);
    }

    protected function registerIniReader()
    {
        // IMHO: configuration files containing special characters in section
        // names should probably be written in YAML instead of effectivly
        // preventing nested structures alltogether.
        $reader = new IniReader();
        $reader->setNestSeparator(chr(0));
        Factory::registerReader('ini', $reader);
    }
}