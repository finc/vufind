<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for DNB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE101 extends SolrGviMarc
{
    public function getNetwork() {
        return 'DNB';
    }
}
