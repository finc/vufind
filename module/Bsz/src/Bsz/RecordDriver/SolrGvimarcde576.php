<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for SWB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde576 extends SolrGvimarc
{
    public function getNetwork() {return 'SWB';}

    /**
     * For rticles: get container title
     *
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [773 => ['a', 't']];
        $array = $this->getFieldsArray($fields);
        $title = array_shift($array);
        return str_replace('In: ', '', $title);
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

     /**
     * Get Status/Holdings Information from the internally stored MARC Record
     * (support method used by the NoILS driver).
     *
     * @param array $field The MARC Field to retrieve
     * @param array $data  A keyed array of data to retrieve from subfields
     *
     * @return array
     */
    public function getFormattedMarcDetails($field, $data)
    {
        $parent = parent::getFormattedMarcDetails($field, $data);
        $return = [];
        foreach ($parent as $k => $item) {
            $ill_status = '';
            switch ($item['availability']) {
                case 'a': $ill_status = 'ill_status_a';
                     break;
                case 'b': $ill_status = 'ill_status_b';
                     break;
                case 'c': $ill_status = 'ill_status_c';
                     break;
                case 'd': $ill_status = 'ill_status_d';
                     break;
                case 'e': $ill_status = 'ill_status_e';
                     break;
                default: $ill_status = 'ill_status_d';
            }
            $item['availability'] = $ill_status;
            $return[] = $item;
            
        }
        return $return;
    }
}
