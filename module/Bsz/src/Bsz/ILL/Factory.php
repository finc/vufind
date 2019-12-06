<?php

/*
 * Copyright (C) 2019 Bibliotheksservice Zentrum Baden-Württemberg, Konstanz
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bsz\ILL;

use Interop\Container\ContainerInterface;

/**
 * FActory for inter-library loan logic
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
    
    public static function getIllLogic(ContainerInterface $container) 
    {
        $config = $container->get('VuFind\Config')->get('ILL');
        return new \Bsz\ILL\Logic(
            $config,
            $container->get('Bsz\Holding'),
            $container->get(\Bsz\Config\Client::class)->getIsilAvailability()               
        );
    }
    
}
