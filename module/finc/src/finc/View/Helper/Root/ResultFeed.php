<?php
/**
 * "Results as feed" view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;
use DateTime,
    Zend\Feed\Writer\Feed;

/**
 * "Results as feed" view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ResultFeed extends \VuFind\View\Helper\Root\ResultFeed
{

    /**
     * Support method to turn a record driver object into an RSS entry.
     *
     * @param Feed                              $feed   Feed to update
     * @param \VuFind\RecordDriver\AbstractBase $record Record to add to feed
     *
     * @return void
     */
    protected function addEntry($feed, $record)
    {
        $entry = $feed->createEntry();
        $title = $record->tryMethod('getTitle');
        $publishPlace = $record->tryMethod('getPlacesOfPublication');
        if (!empty($publishPlace)) {
            $title .= ' / ' . implode(', ', $publishPlace);
        }
        $publisher = $record->tryMethod('getPublishers');
        if (!empty($publisher) && is_array($publisher)) {
            $title .= ' / ' . implode(', ', $publisher);
        }
        $publishDateSort = $record->tryMethod('getPublishDateSort');
        if (!empty($publishDateSort)) {
            $title .= ' / ' . $publishDateSort;
        }
        $entry->setTitle(empty($title) ? $record->getBreadcrumb() : $title);
        $serverUrl = $this->getView()->plugin('serverurl');
        $recordLink = $this->getView()->plugin('recordlink');
        try {
            $url = $serverUrl($recordLink->getUrl($record));
        } catch (\Zend\Mvc\Router\Exception\RuntimeException $e) {
            // No route defined? See if we can get a URL out of the driver.
            // Useful for web results, among other things.
            $url = $record->tryMethod('getUrl');
            if (empty($url) || !is_string($url)) {
                throw new \Exception('Cannot find URL for record.');
            }
        }
        $entry->setLink($url);
        $date = $this->getDateModified($record);
        if (!empty($date)) {
            $entry->setDateModified($date);
        }
        $author = $record->tryMethod('getPrimaryAuthor');
        if (!empty($author)) {
            $entry->addAuthor(['name' => $author]);
        }
        /*$formats = $record->tryMethod('getFormats');
        if (is_array($formats)) {
            foreach ($formats as $format) {
                $entry->addDCFormat($format);
            }
        }
        $date = $record->tryMethod('getPublicationDates');
        if (isset($date[0]) && !empty($date[0])) {
            $entry->setDCDate($date[0]);
        }*/

        $feed->addEntry($entry);
    }

    /**
     * Support method to extract modified date from a record driver object.
     *
     * @param \VuFind\RecordDriver\AbstractBase $record Record to pull date from.
     *
     * @return int|DateTime|null
     */
    protected function getDateModified($record)
    {
        $date = $record->tryMethod('getDateIsil');
        if (!empty($date)) {
            $rawDate = DateTime::createFromFormat('Y-m-d\TH:i:sZ', $date);
            return strtotime($rawDate->format('d.m.Y H:i'));
        }

        // If we got this far, no date is available:
        return null;
    }
}
