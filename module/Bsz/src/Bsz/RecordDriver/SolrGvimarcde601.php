<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for GBV records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde601 extends SolrGvimarc
{
    public function getNetwork() {return 'GBV';}
}
