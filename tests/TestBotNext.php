<?php


namespace Crypto\Tests;


use Crypto\Bot\BotNext;
use Crypto\Exchange\Order;
use Crypto\Tests\Exchange\ExchangeFabric;
use Crypto\Tests\Exchange\ExchangeStubClient;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

    public $dispatcher;

    public function setUp()
    {

        $this->dispatcher = new EventDispatcher();

        $this->dispatcher->addSubscriber(new class implements EventSubscriberInterface {

            /**
             * Returns an array of event names this subscriber wants to listen to.
             *
             * The array keys are event names and the value can be:
             *
             *  * The method name to call (priority defaults to 0)
             *  * An array composed of the method name to call and the priority
             *  * An array of arrays composed of the method names to call and respective
             *    priorities, or 0 if unset
             *
             * For instance:
             *
             *  * array('eventName' => 'methodName')
             *  * array('eventName' => array('methodName', $priority))
             *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
             *
             * @return array The event names to listen to
             */
            public static function getSubscribedEvents()
            {
                return
                [
                  "BotNext.InOrderCreated" => ['handler'],
                  "BotNext.InOrderExecuted" => ['handler'],
                  "BotNext.OutOrderCreated" => ['handler'],
                  "BotNext.OutOrderExecuted" => ['handler'],
                ];
            }

            public function handler($event)
            {
                var_dump(get_class($event));
            }
        });

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
        $bot->dispatcher = $this->dispatcher;
        $bot->logger = new Logger("TEST");

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