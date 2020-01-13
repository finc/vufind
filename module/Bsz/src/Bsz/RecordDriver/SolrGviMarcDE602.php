<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for KOBV records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE602 extends SolrGviMarc
{
    public function getNetwork() {return 'KOBV';}
    
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
}
