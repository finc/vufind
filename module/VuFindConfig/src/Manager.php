<?php
/**
 * VuFind Config Manager
 *
 * Copyright (C) 2010 Villanova University,
 *               2018 Leipzig University <info.ub.uni-leipzig.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc. 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category VuFind
 * @package  VuFindConfig
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU GPLv2
 * @link     https://vufind.org/wiki/development Wiki
 */
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