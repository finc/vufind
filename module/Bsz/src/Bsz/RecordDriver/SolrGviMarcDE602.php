<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for KOBV records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE602 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'KOBV';
    }
}
