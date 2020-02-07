<?php
/**
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
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


namespace Bsz\RecordTab;

use VuFind\RecordTab\AbstractBase;
use VuFind\Search\SearchRunner;

/**
 * Tab for Notes ("Enthaltene Werke")
 *
 * @author Stefan Winkler <stefan.winkler@bsz-bw.de>
 */
class Notes extends AbstractBase
{
    protected $visible;

    /**
     * Notes constructor.
     * @param bool $visible
     */
    public function __construct($visible = true)
    {
        $this->visible = (bool)$visible;
        $this->accessPermission = 'access.NotesViewTab';
    }
    /**
     * Get the on-screen description for this tab.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'ContainedNotes';
    }

    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * Decide, if we show the Tab or not
     *
     * @return boolean
     */
    public function isActive()
    {
        $parent = parent::isActive();
        $content = $this->getContent();

        if($parent && !empty($content)) {
            return true;
        }
        return false;
    }

    /**
     * Do we have content for the tab?
     *
     * @return array|null
     */
    public function getContent()
    {
        return $this->driver->getNotes();
    }
}