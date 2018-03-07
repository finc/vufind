<?php
/**
 * VuFind Config Provider
 *
 * Copyright (C) 2010 Villanova University,
 *               2018 Leipzig University Library <info@ub.uni-leipzig.de>
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

use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\ConfigAggregator\GlobTrait;

class Provider
{
    use GlobTrait;

    const PARSE_PARENT_CONFIG = 1;

    protected $baseGlob;

    protected $fileGlob;

    protected $parseParentConfig;

    public function __construct($basePattern, $filePattern, $flags = 0)
    {
        $this->baseGlob = $this->glob($basePattern);
        $this->fileGlob = array_reverse($this->glob($basePattern . $filePattern));
        $this->parseParentConfig = $flags & static::PARSE_PARENT_CONFIG;
    }

    public function __invoke()
    {
        $keys = array_map([$this, 'getKey'], $this->fileGlob);
        $entries = array_combine($keys, $this->fileGlob);
        return array_map([$this, 'load'], $entries);
    }

    protected function getKey($path)
    {
        foreach ($this->baseGlob as $base) {
            if (strpos($path, $base) === 0) {
                $path = substr_replace($path, "", 0, strlen($base));
                $prefix = basename($path) === $path
                    ? '' : pathinfo($path, PATHINFO_DIRNAME) . '/';
                return $prefix . pathinfo($path, PATHINFO_FILENAME);
            }
        }
    }

    protected function load($path)
    {
        $config = new Config(Factory::fromFile($path), true);
        $parentOpts = $config->Parent_Config ?: new Config([]);
        $parentPath = $parentOpts->path ?: $parentOpts->relative_path
            ? dirname($path) . '/' . $parentOpts->relative_path : null;
        return $parentPath && $this->parseParentConfig
            ? $this->merge($config, $this->load($parentPath)) : $config;

    }

    // merge logic formerly found within PluginFactory::loadConfig
    protected function merge(Config $child, Config $config)
    {
        $overrideSections = isset($child->Parent_Config->override_full_sections)
            ? explode(
                ',', str_replace(
                    ' ', '', $child->Parent_Config->override_full_sections
                )
            ) : [];
        foreach ($child as $section => $contents) {
            // Check if arrays in the current config file should be merged with
            // preceding arrays from config files defined as Parent_Config.
            $mergeArraySettings
                = !empty($child->Parent_Config->merge_array_settings);

            // Omit Parent_Config from the returned configuration; it is only
            // needed during loading, and its presence will cause problems in
            // config files that iterate through all of the sections (e.g.
            // combined.ini, permissions.ini).
            if ($section === 'Parent_Config') {
                continue;
            }
            if (in_array($section, $overrideSections)
                || !isset($config->$section)
            ) {
                $config->$section = $child->$section;
            } else {
                foreach (array_keys($contents->toArray()) as $key) {
                    // If a key is defined as key[] in the config file the key
                    // remains a Zend\Config\Config object. If the current
                    // section is not configured as an override section we try to
                    // merge the key[] values instead of overwriting them.
                    if (is_object($config->$section->$key)
                        && is_object($child->$section->$key)
                        && $mergeArraySettings
                    ) {
                        $config->$section->$key = array_merge(
                            $config->$section->$key->toArray(),
                            $child->$section->$key->toArray()
                        );
                    } else {
                        $config->$section->$key = $child->$section->$key;
                    }
                }
            }
        }
        return $config;
    }
}
