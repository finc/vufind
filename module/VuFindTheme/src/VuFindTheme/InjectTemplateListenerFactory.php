<?php
/**
 * Factory for InjectTemplateListener
 *
 * PHP version 7
 *
 * Copyright (C) 2019 Leipzig University Library
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @category VuFind
 * @package  Theme
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU GPLv2
 * @link     https://vufind.org Main Site
 */
namespace VuFindTheme;

use Psr\Container\ContainerInterface;

/**
 * Factory for InjectTemplateListener
 *
 * @category VuFind
 * @package  Theme
 * @author   Sebastian Kehr <kehr@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU GPLv2
 * @link     https://vufind.org Main Site
 */
class InjectTemplateListenerFactory
{
    /**
     * Create an InjectTemplateListener object
     *
     * @param ContainerInterface $container Service manager
     *
     * @return InjectTemplateListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $prefixes = $config['vufind']['template_injection'] ?? [];
        return new InjectTemplateListener($prefixes);
    }
}
