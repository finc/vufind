<?php
namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for SWB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE576 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'SWB';
    }

    /**
     * Get container pages
     *
     * @return string
     */
    public function getContainerPages()
    {
        $fields = [936 => ['h']];
        $pages = $this->getFieldsArray($fields);
        return array_shift($pages);
    }

    /**
     * get container year
     *
     * @return string
     */
    public function getContainerYear()
    {
        $fields = [
            260 => ['c'],
            936 => ['j'],
            773 => ['t', 'd']
        ];
        $years = $this->getFieldsArray($fields);
        foreach ($years as $k => $year) {
            preg_match('/\d{4}/', $year, $tmp);
            if (isset($tmp[0])) {
                $years[$k] = $tmp[0];
            } else {
                unset($years[$k]);
            }
        }
        return array_shift($years);
    }

    /**
     * Get the Container issue
     *
     * @return string
     */
    public function getContainerIssue()
    {
        $issue = $this->getFieldsArray([936 => ['e']]);
        return array_shift($issue);
    }

    /**
     * Get container volume
     *
     * @return string
     */
    public function getContainerVolume()
    {
        $volume = $this->getFieldsArray([936 => ['d']]);
        return array_shift($volume);
    }
}
