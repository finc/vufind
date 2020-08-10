<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\RecordDriver;

/**
 * Trait offers sub-srecord functions
 *
 * @author amzar
 */
trait SubrecordTrait
{
    /**
     * Dedup Functions
     *
     * @return boolean
     */
    public function isSubRecord()
    {
        return isset($this->fields['_isSubRecord']) ?
            $this->fields['_isSubRecord'] : false;
    }

    /**
     *
     * @return array|null
     */
    public function getSubRecords()
    {
        return isset($this->fields['_subRecords']) ?
            $this->fields['_subRecords'] : null;
    }

    /**
     *
     * @return boolean
     */
    public function hasSubRecords()
    {
        if (null !== ($collection = $this->getSubRecords())) {
            return 0 < $collection->count();
        }
        return false;
    }
}
