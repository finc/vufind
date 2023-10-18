<?php

/**
 * Base class for sitemap generator plugins
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Sitemap
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */

namespace VuFind\Sitemap\Plugin;

/**
 * Base class for sitemap generator plugins
 *
 * @category VuFind
 * @package  Sitemap
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
abstract class AbstractGeneratorPlugin implements GeneratorPluginInterface
{
    /**
     * Verbose message callback
     *
     * @var callable
     */
    protected $verboseMessageCallback = null;

    /**
     * Set plugin options.
     *
     * @param array $options Options
     *
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->verboseMessageCallback = $options['verboseMessageCallback'] ?? null;
    }

    /**
     * Whether the URLs generated by the plugin support VuFind's lng parameter
     *
     * @return bool
     */
    public function supportsVuFindLanguages(): bool
    {
        return true;
    }

    /**
     * Get update frequency. Empty string implies that the default from sitemap
     * configuration should be used.
     *
     * @return string
     */
    public function getFrequency(): string
    {
        return '';
    }

    /**
     * Get the name of the sitemap used to create the sitemap file. This will be
     * appended to the configured base name, and may be blank to use the base
     * name without a suffix.
     *
     * @return string
     */
    abstract public function getSitemapName(): string;

    /**
     * Generate urls for the sitemap.
     *
     * May yield a string per URL or an array that defines language versions and/or
     * frequency in addition to url.
     *
     * @return \Generator
     */
    abstract public function getUrls(): \Generator;

    /**
     * Write a verbose message (if callback is available and configured to do so)
     *
     * @param string $msg Message to display
     *
     * @return void
     */
    protected function verboseMsg(string $msg): void
    {
        if ($this->verboseMessageCallback) {
            ($this->verboseMessageCallback)($msg);
        }
    }
}
