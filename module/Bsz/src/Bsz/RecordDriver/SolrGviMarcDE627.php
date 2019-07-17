<?php

namespace Bsz\RecordDriver;


/**
 * SolrMarc implementation for K10plus records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE627 extends SolrGviMarc
{
    /*
     * return string "DE-576" or "DE-601"
     * prefer DE-576 if possible
     */
    public function getNetwork() {
        
        $consortium = parent::getConsortium();
        
        if (strpos($consortium, "SWB") !== false) {
            return "SWB";
        } else {
            return "GBV";
        }        
    }
}

