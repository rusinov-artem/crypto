<?php


namespace Crypto\Tests;


use Crypto\Binance\Pair;
use PHPUnit\Framework\TestCase;

class TestPair extends TestCase
{
    public function testPairWorking()
    {

        $config = include __DIR__."/../config.php";
        $bin = new \Crypto\Binance\Client();
        $bin->apiKey = $config['binance.api.key'];
        $bin->secretKey = $config['binance.api.secret'];

        $pair = current($bin->getPairs());
        $pair->setClient($bin);

        $pair->limit;

    }
}