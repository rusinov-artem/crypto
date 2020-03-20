<?php

namespace Crypto\Tests;


use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Order;
use Crypto\Exchange\OrderBook;
use Crypto\Exchange\OrderBookItem;
use Crypto\Exchange\Pair;
use Crypto\Exchange\PairLimit;
use Crypto\Exchange\Trade;
use Crypto\HitBTC\Client;
use PHPUnit\Framework\TestCase;

class TestHitBTCClient extends TestCase
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
        $this->client = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

        $order = new Order();
        $order->side='buy';
        $order->price = 0.01;
        $order->value = 1;
        $order->pairID = "BTCUSD";
        $this->miniOrder = $order;
    }

    public function testGetPairs()
    {
        $data = $this->client->getPairs();

        $this->assertGreaterThan(0, count($data));
        $pair = current($data);
        $this->assertTrue($pair instanceof Pair);
        $this->assertTrue($pair->limit instanceof PairLimit);
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

    public function testCreateOrder()
    {
        $order = $this->client->createOrder($this->miniOrder);
        $this->assertTrue('new' === $order->status);
        $this->assertNotNull($order->eClientOrderID);
        $this->assertNotNull($order->eOrderID);
        $this->assertTrue($order instanceof Order);
        $this->client->closeOrder($order);
    }

    public function testCloseOrder()
    {
        $order = $this->client->createOrder($this->miniOrder);
        $o = $this->client->closeOrder($order);
        $this->assertTrue('canceled' === $order->status);
        $this->assertNotNull($order->eClientOrderID);
        $this->assertNotNull($order->eOrderID);
        $this->assertTrue($order instanceof Order);

        $this->assertTrue('canceled' === $o->status);
        $this->assertNotNull($o->eClientOrderID);
        $this->assertNotNull($o->eOrderID);
        $this->assertTrue($o instanceof Order);
    }

    public function testActiveOrders()
    {
        $order = $this->client->createOrder($this->miniOrder);
        $orders = $this->client->getActiveOrders();
        $this->assertGreaterThan(0, $this->count($orders));
        $this->assertTrue(current($orders) instanceof Order);
        $this->assertArrayHasKey($order->eClientOrderID, $orders);
        $this->client->closeOrder($order);
    }

    public function testCheckOrderIsActive()
    {
        $order = $this->client->createOrder($this->miniOrder);
        $this->assertTrue($this->client->checkOrderIsActive($order, true));
        $this->client->closeOrder($order);
        $this->assertTrue(!$this->client->checkOrderIsActive($order, true));
    }

    public function testOrderGetStatus()
    {
        $order = $this->client->createOrder($this->miniOrder);
        $this->assertEquals('new',$this->client->getOrderStatus($order, true));
        $this->client->closeOrder($order);
        $this->assertTrue(in_array($this->client->getOrderStatus($order, true), ['canceled', 'unknown']));
    }

    public function testGetAccountTrades()
    {
        $t = $this;
        $counter = 0;
        $this->client->chunkAccountTrades(null, function($trade)use($t, &$counter){
           $t->assertTrue($trade instanceof Trade);
           $counter++;
           return false;
        });
        $this->assertTrue($counter >=0 );
    }

    public function testGetOrderBook()
    {
        $ob = $this->client->getOrderBook("BTCUSD");

        $this->assertTrue($ob instanceof OrderBook);
        $this->assertTrue(current($ob->ask) instanceof OrderBookItem);
        $this->assertTrue(current($ob->bid) instanceof OrderBookItem);
        $this->assertTrue((double)current($ob->ask)->price > (double)current($ob->bid)->price );

    }

    public function testGetPairTrades()
    {
        $count = 0;
        $this->client->getPairTrades("BTCUSD", function($trade)use(&$count){
            $count++;
           $this->assertTrue($trade instanceof Trade);
            return false;
        });
        $this->assertTrue($count>=0);
    }

    public function test_preloaded_trades()
    {
        $orders = $this->client->getActiveOrders();

        $tClient = new class($this->client) extends Client{

            private $client;
            public $counter = 0;

            public function __construct(Client $client)
            {
                $this->client = $client;
            }

            public function request($method, $action, array $params)
            {
                if($action === 'history/trades')
                {
                    $this->counter++;
                }

                return $this->client->request($method, $action, $params);
            }

            public function __call($name, $arguments)
            {
               return call_user_func_array([$this->client, $name], $arguments);
            }

            public function __get($name)
            {
               return $this->client->$name;
            }
        };


        $pairs = [];
        foreach ($orders as $order)
        {
            $pairs[$order->pairID] = 1;
            $tClient->loadTrades($order->pairID);
            $tClient->getOrderTrades($order);
        }

        var_dump(count($pairs));
        var_dump($tClient->counter);

        $this->assertEquals(count($pairs), $tClient->counter);
    }


}
