<?php

namespace BerkTest;

use Berk\Berk;

class BerkTest extends \PHPUnit_Framework_TestCase
{

    public function testEmptyConfig()
    {
        $path = realpath(__DIR__ . '/../config/empty.json');
        $berk = new Berk($path);

        $this->assertEquals('berk', $berk->getConfiguration()['name']);
        $this->assertEmpty($berk->getConfiguration()['servers']);
        $this->assertEquals($path, $berk->getConfiguration()['ignore'][0]);
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidConfig()
    {
        $berk = new Berk(__DIR__ . '/../config/invalid.json');
    }

}