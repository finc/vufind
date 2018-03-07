<?php
/**
 * VuFind Config YAML Reader
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
namespace VuFind\Config\Reader;

use Symfony\Component\Yaml\Yaml as Parser;
use VuFind\Config\Manager;
use Zend\Config\Reader\Yaml as Base;

class Yaml extends Base
{
    /**
     * @var Manager
     */
    protected $manager;

    public function __construct(Manager $manager)
    {
        parent::__construct([Parser::class, 'parse']);
        $this->manager = $manager;
    }

    public function fromFile($filename)
    {
        $data = parent::fromFile($filename);
        if (!isset($data['@parent_yaml'])) {
            return $data;
        }

        $parentData = parent::fromFile($data['@parent_yaml']);
        unset($data['@parent_yaml']);
        return array_replace($parentData, $data);
    }

    public function get($filename)
    {
        return $this->manager->get($filename);
    }
}