<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for HEBIS records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde603 extends SolrGvimarc
{
    public function getNetwork() {return 'HEBIS';}
}
