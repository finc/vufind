<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for DNB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde101 extends SolrGvimarc
{
    public function getNetwork() {
        return 'DNB';
    }
}
