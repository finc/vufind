<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for HBZ records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE605 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'HBZ';
    }
}
