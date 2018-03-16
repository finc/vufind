<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for BVB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde604 extends SolrGvimarc
{
    public function getNetwork() {return 'BVB';}
}
