<?php


namespace Crypto\Tests;


use Crypto\Bot\BotNext;
use Crypto\Exchange\Order;
use Crypto\Tests\Exchange\ExchangeFabric;
use Crypto\Tests\Exchange\ExchangeStubClient;
use PHPUnit\Framework\TestCase;

class TestBotNext extends TestCase
{
    /**
     * @var ExchangeStubClient
     */
    public $client;

    /**
     * @var Order
     */
    public $miniOrder;

    /**
     * @var BotNext
     */
    public $bot;

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

    public function testSimple()
    {
        $bot = new BotNext();

        $inOrder = new Order();
        $inOrder->value = 0.01;
        $inOrder->price = 4000;
        $inOrder->side = 'buy';
        $inOrder->pairID = "BTCUSD";

        $bot->inOrder = $inOrder;

        $outOrder = clone $inOrder;
        $outOrder->price = 9000;
        $outOrder->side = 'sell';

        $bot->outOrder = $outOrder;

        $bot->client = $this->client;

        $bot->tick();

        $this->assertTrue($bot->inOrder->status === 'new');
        $this->assertTrue($this->client->exchange->getOrder($bot->inOrder->id) instanceof Order);

        $this->client->exchange->fillOrder($bot->inOrder->id, 0.01);

        $bot->tick();

        $this->assertTrue($bot->inOrder->status === 'filled');
        $this->assertTrue($this->client->exchange->getOrder($bot->outOrder->id) instanceof Order);

        $this->client->exchange->fillOrder($bot->outOrder->id, 0.01);
        $bot->tick();

        $this->assertTrue($bot->isFinished());

    }
}