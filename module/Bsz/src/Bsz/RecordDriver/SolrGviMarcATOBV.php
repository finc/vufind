<?php
namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for K10plus records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcATOBV extends SolrGviMarc
{
    /*
     * return string "DE-576" or "DE-601"
     * prefer DE-576 if possible
     */
    public function getNetwork()
    {
        return 'AT-OBV';
    }
}
