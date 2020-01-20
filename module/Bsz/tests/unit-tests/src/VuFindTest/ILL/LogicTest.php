<?php


namespace VuFindTest\ILL;


use Bsz\ILL\Holding;
use PHPUnit\Framework\TestCase;
use Bsz\ILL\Logic;
use Bsz\Config\Client;
use VuFind\RecordDriver\SolrMarc;
use Zend\Config\Config;

class LogicTest extends TestCase
{
    public function testLogic()
    {
        $logic = $this->getLogic();
        $this->assertEquals($logic->isAvailable(), 'false');
    }

    protected function getLogic()
    {
        $config = new Client();
        $holding = new Holding();
        $logic = new Logic($config, $holding, ['DE-352']);

        $config = new Config([]);
        $record = new SolrMarc($config);
        $fixture = $this->loadRecordFixture('repetitorium.json');
        $record->setRawData($fixture['response']['docs'][0]);

        $logic->setDriver($record);
        return $logic;


    }

    /**
     * Load a fixture file.
     *
     * @param string $file File to load from fixture directory.
     *
     * @return array
     */
    protected function loadRecordFixture($file)
    {
        return json_decode(
            file_get_contents(
                realpath(
                    VUFIND_PHPUNIT_MODULE_PATH . '/fixtures/misc/' . $file
                )
            ),
            true
        );
    }
}
