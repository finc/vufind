<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for HBZ records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde605 extends SolrGvimarc
{
    public function getNetwork() {return 'HBZ';}
    
        /**
     * For rticles: get container title
     * 
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [830 => ['a', 't']];
        $array = $this->getFieldsArray($fields);
        $title = array_shift($array);
        return str_replace('In: ', '', $title);
    }
}
