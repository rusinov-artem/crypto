<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/12/2018
 * Time: 4:40 PM
 */

namespace Crypto\Bot;


use Crypto\HitBTC\Client;
use Crypto\HitBTC\Order;

class EDOBot
{

    public $depositUSD = 20;

    public $initBuyPrice = 0.3;

    /**
     * @var Order
     */
    public $buyOrder = null;

    /**
     * @var Order
     */
    public $sellOrder = null;

    public $routes = [];
    public $id = "FirstEDOUSDBot";

    /**
     * @var Client
     */
    public $client;

    public function __construct( $client )
    {
        $this->client = $client;
        $this->initRoutes();
    }


    public function tick()
    {
        $action = $this->getAction();
        if(is_callable($action['action']))
        {
            call_user_func_array($action['action'], $action['params']);
        }
    }

    public function createBuyOrder()
    {
        $this->log('In createByOrderMethod');

        $this->log('Getting best bid');

        $orderBook = $this->client->getOrderBook("EDOUSD");
        $bid = current($orderBook['bid'])['price'];

        $this->log('best bid = '.$bid);

        $this->buyPrice = $bid - 0.03;

        $this->log('buy price = '.$this->initBuyPrice);

        if( $this->initBuyPrice >= current($orderBook['ask'])['price'] )
        {
            $this->log('init price higher then ask price!');
            $this->initBuyPrice = current($orderBook['bid'])['price'];
        }


        $this->log('creating order');

        $data = $this->client->createOrder('EDOUSD', 'buy', 0.1, $this->initBuyPrice);

        if(isset($data['clientOrderId']))
        {
            $order = new Order();
            $order->init($data);

            $this->buyOrder = $order;
            var_dump($order);

            $this->log('order created clientOrderId='.$data['clientOrderId']);
        }
    }

    public function createSellOrder()
    {

    }

    public function checkBuyOrder()
    {
        $this->log('We are gonna check buyOrder '.$this->buyOrder->id);

        //Проверяем что ордер среди активных
        $isActive = $this->client->checkOrderIsActive($this->buyOrder->id);

        $this->log('buyOrder is '. ($isActive?'active':'not active'));

        //Если его там  нет, то возможно он исполнен
        if(!$isActive)
        {
            //Проверяем исполнен ли ордер
            if(1 !== $this->checkOrder($this->buyOrder))
            {
                $this->log('we calculate that order was canceled');
                //В данном случае ордер не исполнен, и не активен
                //Значит он был просто удален...
                $this->buyOrder = null;

                return;
            }
            else
            {
                //В этом случае ордер был исполнен полностью
                $this->log('we calculate thar order was executed');
                $sellOrder = new Order();

                $this->log('creating sell order');
                $orderBook = $this->client->getOrderBook("EDOUSD");
                $price = $this->buyOrder->price + 0.01;

                if($price < current($orderBook['bid'])['price'])
                {
                    $price = current($orderBook['bid'])['price'] + 0.01;
                }

                $data = $this->client->createOrder("EDOUSD", 'sell', $this->buyOrder->value, $price);
                $sellOrder->init($data);
                $this->sellOrder = $sellOrder;

                $this->log('sell order was successfully created and has id = '.$sellOrder->id);
                $this->buyOrder = null;
            }
        }
    }

    public function checkSellOrder()
    {
        $this->log('We are gonna check sellOrder '.$this->sellOrder->id);

        //Проверяем что ордер среди активных
        $isActive = $this->client->checkOrderIsActive($this->sellOrder->id);

        $this->log('sellOrder is '. ($isActive?'active':'not active'));

        //Если его там  нет, то возможно он исполнен
        if(!$isActive)
        {
            //Проверяем исполнен ли ордер
            if(1 !== $this->checkOrder($this->sellOrder))
            {
                $this->log('we calculate that order was canceled');
                //В данном случае ордер не исполнен, и не активен
                //Значит он был просто удален...
                $this->sellOrder = null;

                return;
            }
            else
            {
                //В этом случае ордер был исполнен полностью
                $this->log('we calculate thar order was executed');
                $this->log('Congratulation Deal Successfully finished');

                $this->buyOrder = null;
            }
        }
    }

    public function checkOrder(Order $order)
    {
        $this->log('we are gonna check trades of order '.$order->id);

        $trades = 0;

        $this->client->getAccountTrades("EDOUSD", function ($item) use ($order, &$trades)
        {
            $td = new \DateTime($item['timestamp']);
            if($td < $order->date) return false;

            $trades += $item['quantity'];

            return true;

        });

        $this->log('order '.$order->id.' has '.$trades. ' trades of '.$order->value);

        if($trades >= $order->value) return 1;

        return 0;
    }

    public function initRoutes()
    {
        //check if this is fresh bot
        $this->routes[] = function ()
        {
            if($this->buyOrder !== null || $this->sellOrder !== null)
                return false;

            return [ 'action' => [$this, 'createBuyOrder'], 'params' => []];

        };

        $this->routes[] = function ()
        {
            if($this->buyOrder == null && $this->sellOrder !== null)
                return false;

            return [ 'action' => [$this, 'checkBuyOrder'], 'params' => [] ];

        };

        $this->routes[] = function ()
        {
            if($this->sellOrder == null || $this->buyOrder !== null)
            {
                return false;
            }

            return [ 'action' => [$this, 'checkSellOrder'], 'params' => [] ];
        };
    }

    public function getAction()
    {
        foreach ($this->routes as $route)
        {
            $r = call_user_func($route);
            if($r !== false) return $r;
        }

        return false;
    }

    public function log($message)
    {
        $logLine = (new \DateTime())->format("Y-m-d H:i:s")." {{$this->id}} $message";
        var_dump($logLine);
        file_put_contents(__DIR__."/../../storage/log/{$this->id}.log", $logLine."\n", FILE_APPEND);
    }

    public function __sleep()
    {
        $this->routes = [];
        $this->client = null;
        return array_keys(get_object_vars($this));
    }

    public function __wakeup()
    {
        $this->initRoutes();
    }
}