<?php
/**
 * Copyright 2020 (C)5 Bibliotheksservice Zentrum, Konstanz, Germany
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

namespace Bsz\RecordTab;

use Bsz\Config\Library;
use Exception;
use VuFind\RecordTab\AbstractBase;
use Bsz\ILL\Logic;


class InterlibraryLoan extends AbstractBase
{
    /**
     * @var Logic
     */
    protected $logic;

    /**
     * @var array
     */
    protected $library;


    /**
     * InterlibraryLoan constructor.
     * @param Logic $logic
     * @param Library $libraries
     * @param bool $active
     */
    public function __construct(Logic $logic, Library $library = null, bool $active = true)
    {
        $this->logic = $logic;
        $this->library = $library;
        $this->active = $active;
        $this->accessPermission = 'access.InterlibraryLoanTab';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Interlibrary Loan';
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActive()
    {
        if ($this->active) {
            return parent::isActive();
        }
        return false;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        $this->logic->attachDriver($this->driver);
        $status = $this->logic->isAvailable();
        $messages = $this->logic->getMessages();
        $ppns = $this->logic->getPPNs();

        return [
            'status' => $status,
            'messages' => $messages,
            'ppns' => $ppns,
            'library' => $this->library
        ];
    }
}