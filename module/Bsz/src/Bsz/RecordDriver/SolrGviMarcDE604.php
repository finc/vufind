<?php
namespace Bsz\RecordDriver;

/**
 * SolrMarc implementation for BVB records
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarcDE604 extends SolrGviMarc
{
    public function getNetwork()
    {
        return 'BVB';
    }
}
