<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for HEBIS records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE603 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'HEBIS';
    }
}
