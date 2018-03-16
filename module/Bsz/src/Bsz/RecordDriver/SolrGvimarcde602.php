<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for KOBV records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde602 extends SolrGvimarc
{
    public function getNetwork() {return 'KOBV';}
}
