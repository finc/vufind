<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for BVB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde604 extends SolrGvimarc
{
    public function getNetwork() {return 'BVB';}
    
        /**
     * For rticles: get container title
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [490 => ['a', 'v']];
        $array = $this->getFieldsArray($fields);
        $title = array_shift($array);
        return str_replace('In: ', '', $title);
    }
}
