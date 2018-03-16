<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for HBZ records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde605 extends SolrGvimarc
{
    public function getNetwork() {return 'HBZ';}
}
