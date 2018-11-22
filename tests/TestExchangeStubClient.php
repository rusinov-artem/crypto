<?php


namespace Crypto\Tests;

use Crypto\Exchange\Order;
use Crypto\Tests\Exchange\ExchangeFabric;
use Crypto\Tests\Exchange\ExchangeStubClient;

class TestExchangeStubClient extends TestHitBTCClient
{
    /**
     * @var ExchangeStubClient
     */
    public $client;

    /**
     * @var Order
     */
    public $miniOrder;

    public function setUp()
    {

       $this->client = new ExchangeStubClient(ExchangeFabric::make());

        $order = new Order();
        $order->side='buy';
        $order->price = 0.01;
        $order->value = 1;
        $order->pairID = "BTCUSD";
        $this->miniOrder = $order;
    }
}