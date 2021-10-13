<?php

/*
 * Copyright 2021 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace BszGrouping\Config;

use Interop\Container\ContainerInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\Container;

/**
 * Description of Factory
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    public static function getDedup(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config')->get('Index');
        $sesscontainer = new Container(
            'dedup',
            $container->get('VuFind\SessionManager')
        );
        $response = $container->get('Response');
        $cookie = $container->get('Request')->getCookie();
        return new Dedup($config, $sesscontainer, $response, $cookie);
    }
}
