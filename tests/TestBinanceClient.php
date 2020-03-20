<?php


namespace Crypto\Tests;


use Crypto\Binance\Client;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Order;

class TestBinanceClient extends TestHitBTCClient
{
    /**
     * @var Client
     */
    public $client;

    /**
     * @var Order
     */
    public $miniOrder;

    public function setUp()
    {
        $config = include __DIR__ . "/../config.php";
        $this->client = new Client();
        $this->client->apiKey = $config['binance.api.key'];
        $this->client->secretKey = $config['binance.api.secret'];

        $order = new Order();
        $order->side='buy';
        $order->price = 0.01;
        $order->value = 1;
        $order->pairID = "BTCUSD";
        $this->miniOrder = $order;
    }

    public function testGetBalance()
    {
        $data = $this->client->getBalance();

        $this->assertGreaterThan(0, count($data));
        $balance = current($data);
        $this->assertTrue($balance instanceof CurrencyBalance);
    }

    public function testGetNonZeroBalance()
    {
        $data = $this->client->getNonZeroBalance();

        $this->assertGreaterThan(0, count($data));
        /**
         * @var $balance CurrencyBalance
         */
        $balance = current($data);
        $this->assertTrue($balance instanceof CurrencyBalance);
        $this->assertTrue($balance->reserved > 0 || $balance->available > 0);
    }
}