<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for GBV records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE601 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'GBV';
    }
}
