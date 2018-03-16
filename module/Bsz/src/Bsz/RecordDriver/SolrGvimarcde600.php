<?php

namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for ZDB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarcde600 extends SolrGvimarc
{
    public function getNetwork() {return 'ZDB';}
}
