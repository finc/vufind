<?php

namespace finc\ILS\Driver;

/**
 * Class FincTheca
 * @deprecated Remove when Bibliotheca support ends
 * @package finc\ILS\Driver
 */
class FincTheca extends FincILS {

    private $identifier_type = 'ppn';
    private $daiaIdPrefixBase;

    public function init()
    {
        parent::init();
        if (isset($this->config['DAIA']['daiaIdPrefixBase'])) {
            $this->daiaIdPrefixBase = $this->config['DAIA']['daiaIdPrefixBase'];
        }
    }

    public function getStatus($id)
    {
        $result = [];
        try {
            $result = parent::getStatus($id);
        } catch (\Exception $e) {

        }
        if (empty($result)) {
            $result = $this->getStatusViaMediennummer($id);
        }
        return $result;
    }

    /**
     * @param $record_id String Finc-ID of record, used to retrieve Mediennummer
     * @return array|mixed
     */
    protected function getStatusViaMediennummer($record_id) {
        $result = [];
        if ($ilsRecordId = $this->_getRecord($record_id)->tryMethod('getMediennummer')) {
            $this->identifier_type = 'mediennr';
            $result = parent::doGetStatus($ilsRecordId);
            foreach($result as &$item) {
                //fix-up IDs
                $item['id'] = $record_id;
            }
        }
        return $result;
    }

    public function getStatuses($ids)
    {
        $results = parent::getStatuses($ids);
        if (count($results) < count($ids)) {
            //some records had no availability info
            $missing = array_flip($ids);
            foreach ($results as $items) {
                $item = current($items);
                if (isset($missing[$item['id']])) unset($missing[$item['id']]);
            }
            foreach (array_keys($missing) as $missing_id) {
                $results[] = $this->getStatusViaMediennummer($missing_id);
            }
        }
        return $results;
    }

    protected function generateURI($id)
    {
        if (isset($this->daiaIdPrefixBase)) {
            return
                $this->daiaIdPrefixBase
                .':'.$this->identifier_type
                .':'.$id;
        }
        return parent::generateMultiURIs($id);
    }

    protected function getItemBarcode($item)
    {
        $matches = [];
        if (preg_match('/^'.$this->daiaIdPrefixBase.':'.$this->identifier_type.':\w+:(\w+)$/',$item['id'],$matches)) {
            return $matches[1];
        }
        return null;
    }

}