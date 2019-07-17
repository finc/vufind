<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\RecordDriver;

use Bsz\FormatMapper,
    VuFindCode\ISBN;

/**
 * SolrMarc class for Findex records
 *
 * @author amzar
 */
class SolrFindexMarc extends SolrMarc {
    
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;
    use \VuFind\RecordDriver\MarcAdvancedTrait;
}
