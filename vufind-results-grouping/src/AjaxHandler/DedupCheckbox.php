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

namespace VuFindResultsGrouping\AjaxHandler;

use VuFindResultsGrouping\Config\Dedup;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Laminas\Mvc\Controller\Plugin\Params;

/**
 * Class DedupCheckbox
 * @package  VuFindResultsGrouping\AjaxHandler
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class DedupCheckbox extends \VuFind\AjaxHandler\AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     *
     * @var Dedup
     */
    protected $dedup;

    /**
     * Constructor
     *
     * @param Dedup  $dedup
     */
    public function __construct(Dedup $dedup)
    {
        $this->dedup = $dedup;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $status = $params->fromPost('status');
        $status = $status == 'true' ? true : false;
        $this->dedup->store(['group' => $status]);
        return $this->formatResponse([], 200);
    }
}
