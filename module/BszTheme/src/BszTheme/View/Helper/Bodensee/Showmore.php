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

namespace BszTheme\View\Helper\Bodensee;

use Zend\View\Helper\AbstractHelper;

/**
 * Class Showmore
 *
 * View Helper to generate showmore buttons with bootstrap collapse
 *
 * @package  BszTheme\View\Helper\Bodensee
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Showmore extends AbstractHelper
{

    /**
     * @param string $selector
     * @param string $text
     *
     * @return mixed
     */
    public function __invoke(string $selector, string $text = 'Showmore', bool $btn = true)
    {
        return $this->getView()->render('Helpers/showmore.phtml', [
            'selector' => $selector,
            'text' => $text,
            'btn' => $btn
        ]);
    }
}
