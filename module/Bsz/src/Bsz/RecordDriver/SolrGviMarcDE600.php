<?php
namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for ZDB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE600 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'ZDB';
    }
}
