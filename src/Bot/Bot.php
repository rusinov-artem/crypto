<?php


namespace Crypto\Bot;


use Crypto\HitBTC\Client;
use Crypto\HitBTC\Order;

class Bot
{
    public $id;
    public $pairID;

    public $initBuyPrice;
    public $sellPrice;

    public $buyPercentage = 0.005;
    public $sellPercentage = 0.0025;

    /**
     * @var Order
     */
    public $buyOrder;

    /**
     * @var Order
     */
    public $sellOrder;

    /**
     * @var Client
     */
    public $client;

    public function getBuyPrice()
    {
        $bestBidAsk = $this->client->getBestBidAsk($this->pairID);

        return $bestBidAsk['bid']['price']*(1-$this->buyPercentage);
    }

    public function getSellPrice()
    {
        return $this->buyOrder->price*(1 + $this->sellPercentage);
    }

    public function getValue()
    {
        return 0.01;
    }

    public function getRoutes()
    {
        $routes = [];
        //check if this is fresh bot
        $routes[] = function ()
        {
            if($this->buyOrder !== null || $this->sellOrder !== null)
                return false;

            return [ 'action' => [$this, 'createBuyOrder'], 'params' => []];

        };

        $routes[] = function ()
        {
            if($this->buyOrder == null && $this->sellOrder !== null)
                return false;

            return [ 'action' => [$this, 'checkBuyOrder'], 'params' => [] ];

        };

        $routes[] = function ()
        {
            if($this->sellOrder == null || $this->buyOrder !== null)
            {
                return false;
            }

            return [ 'action' => [$this, 'checkSellOrder'], 'params' => [] ];
        };

        return $routes;
    }

    public function getAction()
    {
        foreach ($this->getRoutes() as $route)
        {
            $r = call_user_func($route);
            if($r !== false) return $r;
        }

        return false;
    }

    public function tick()
    {
        $action = $this->getAction();
        if(is_callable($action['action']))
        {
            call_user_func_array($action['action'], $action['params']);
        }
    }

    public function log($message)
    {
        $logLine = (new \DateTime())->format("Y-m-d H:i:s")." {{$this->id}} $message";
        var_dump($logLine);
        file_put_contents(__DIR__."/../../storage/log/{$this->id}.log", $logLine."\n", FILE_APPEND);
    }

    public function createBuyOrder()
    {
        $this->log('In createByOrderMethod');

        $this->log('Getting best bid');

        $this->initBuyPrice = $this->getBuyPrice();
        $this->log('buy price = '.$this->getBuyPrice());

        $bestBidAsk = $this->client->getBestBidAsk($this->pairID);

        if( $this->initBuyPrice >= $bestBidAsk['ask']['price'] )
        {
            $this->log('init price higher then ask price!');
            $this->initBuyPrice = $bestBidAsk['bid']['price'];
        }

        $this->log('creating order');

        $data = $this->client->createOrder( $this->pairID, 'buy', $this->getValue(), $this->initBuyPrice);

        if(isset($data['clientOrderId']))
        {
            $order = new Order();
            $this->buyOrder = $order->init($data);

            var_dump($order);

            $this->log('order created clientOrderId='.$data['clientOrderId']);
        }
    }

    public function checkBuyOrder()
    {
        //$this->log('We are gonna check buyOrder '.$this->buyOrder->id);

        $status = $this->client->getOrderStatus($this->buyOrder);

        //$this->log('Buy order '.$this->buyOrder->id.' status = '.$status);

            //Проверяем исполнен ли ордер
            if('canceled' === $status)
            {
                $this->log('Buy order '.$this->buyOrder->id.' status = '.$status);

                $this->log('we calculate that order was canceled');
                //В данном случае ордер не исполнен, и не активен
                //Значит он был просто удален...
                $this->buyOrder = null;

                return;
            }
            elseif('filled' === $status)
            {
                $this->log('Buy order '.$this->buyOrder->id.' status = '.$status);

                //В этом случае ордер был исполнен полностью
                $this->log('we calculate that order was executed');
                $sellOrder = new Order();

                $this->log('creating sell order');
                $bestBidAsk = $this->client->getBestBidAsk($this->pairID);

                $price = $this->getSellPrice();

                if($price <= $bestBidAsk['bid']['price'])
                {
                    $price = $bestBidAsk['ask']['price'] + 0.5;
                }

                $data = $this->client->createOrder($this->pairID, 'sell', $this->buyOrder->value, $price);
                $this->sellOrder = $sellOrder->init($data);

                if( $this->sellOrder->price <= $this->buyOrder->price )
                {
                    $this->log('EMERGENCY RESET SELL ORDER');
                    $this->client->closeOrder($this->sellOrder);
                    $data = $this->client->createOrder($this->pairID, 'sell', $this->buyOrder->value, $price + 1);
                    $this->sellOrder = $sellOrder->init($data);
                }

                $this->log('sell order was successfully created and has id = '.$sellOrder->id);
                $this->buyOrder = null;
            }

    }

    public function checkSellOrder()
    {
        $status = $this->client->getOrderStatus($this->sellOrder);

            if('canceled' === $status)
            {
                $this->log('sellOrder is '. $status);

                $this->log('we calculate that order was canceled');
                //В данном случае ордер не исполнен, и не активен
                //Значит он был просто удален...
                $this->sellOrder = null;

                return;
            }
            elseif('filled' === $status)
            {
                $this->log('sellOrder is '. $status);

                //В этом случае ордер был исполнен полностью
                $this->log('we calculate thar order was executed');
                $this->log('Congratulation Deal Successfully finished');

                $this->sellOrder = null;
                $this->buyOrder = null;
            }

    }

    public function __sleep()
    {
        $this->client = null;
        return array_keys(get_object_vars($this));
    }
}