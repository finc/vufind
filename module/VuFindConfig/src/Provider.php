<?php

namespace VuFind\Config;

use Zend\Config\Config;
use Zend\Config\Factory;
use Zend\ConfigAggregator\GlobTrait;

class Provider
{
    use GlobTrait;

    protected $baseGlob;

    protected $fileGlob;

    public function __construct($basePattern, $filePattern)
    {
        $this->baseGlob = array_reverse($this->glob($basePattern));
        $this->fileGlob = $this->glob($basePattern . $filePattern);
    }

    public function __invoke()
    {
        $keys = array_map([$this, 'getKey'], $this->fileGlob);
        $entries = array_combine($keys, $this->fileGlob);
        $result = array_map([$this, 'load'], $entries);
        return $result;
    }

    protected function getKey($path)
    {
        foreach ($this->baseGlob as $base) {
            if (strpos($path, $base) === 0) {
                $path = substr_replace($path, "", 0, strlen($base));
                $suffix = pathinfo($path, PATHINFO_FILENAME);
                $prefix = basename($path) === $path
                    ? '' : pathinfo($path, PATHINFO_DIRNAME) . '/';
                return $prefix . $suffix;
            }
        }
    }

    protected function load($path)
    {
        $config = new Config(Factory::fromFile($path), true);
        $parentOpts = $config->Parent_Config ?: new Config([]);
        $parentPath = $parentOpts->path ?: $parentOpts->relative_path
            ? dirname($path) . '/' . $parentOpts->relative_path : null;
        return $parentPath ? $this->merge($config, $this->load($parentPath)) : $config;
    }

    // merge logic formerly found within PluginFactory::load
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
