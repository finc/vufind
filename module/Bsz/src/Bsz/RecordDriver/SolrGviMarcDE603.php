<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for HEBIS records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE603 extends SolrGviMarc
{
    public function getNetwork() {return 'HEBIS';}
    
    /**
     * For rticles: get container title
     * 
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [772 => ['t']];
        $array = $this->getFieldsArray($fields);
        $title = array_shift($array);
        return str_replace('In: ', '', $title);
    }
}
