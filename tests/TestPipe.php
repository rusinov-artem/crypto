<?php


namespace Crypto\Tests;


use Crypto\Exchange\Pipe;
use Crypto\HitBTC\Client;
use PHPUnit\Framework\TestCase;

class TestPair extends TestCase
{
    public function testPairWorking()
    {

        $config = include __DIR__."/../config.php";
        $hit = new Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

        $pipe = new Pipe();
        $pipe->currencyList = [
          "USD", "BTC", "ETH"
        ];
        $pipe->client = $hit;
        $pipe->volume = 0.01;
        $result = $pipe->calculate();

    }
}