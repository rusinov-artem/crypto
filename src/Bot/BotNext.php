<?php


namespace Crypto\Bot;

use Crypto\Exchange\Order;
use Crypto\HitBTC\Client;

class BotNext
{
    public $id;

    /**
     * @var Order
     */
    public $inOrder;

    /**
     * @var Order
     */
    public $outOrder;

    /**
     * @var Client
     */
    public $client;

    public function isFinished()
    {
        if($this->inOrder->status === 'filled' && $this->outOrder->status === 'filled')
        {
            return true;
        }

        return false;
    }

    public function getRoutes()
    {
        $routes = [];
        //check if this is fresh bot
        $routes[] = function ()
        {
            if($this->inOrder->status !== null || $this->outOrder->status !== null)
                return false;

            return [ 'action' => [$this, 'createInOrder'], 'params' => []];

        };

        $routes[] = function ()
        {
            if($this->inOrder->isActive() && $this->outOrder->status == null)
            {
                return [ 'action' => [$this, 'checkInOrder'], 'params' => [] ];
            }

            return false;

        };

        $routes[] = function ()
        {
            if($this->inOrder->status === 'filled' || $this->outOrder->isActive())
            {
                return [ 'action' => [$this, 'checkOutOrder'], 'params' => [] ];
            }

            return false;

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
        if($this->isFinished()) return ;

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

    public function createInOrder()
    {
        $this->client->createOrder($this->inOrder);
    }

    public function checkInOrder()
    {
        $status = $this->client->getOrderStatus($this->inOrder);

        if('filled' === $status)
        {
            $this->client->createOrder($this->outOrder);
        }
    }

    public function checkOutOrder()
    {
        $status = $this->client->getOrderStatus($this->outOrder);

        if('filled' === $status)
        {
            //bot finished;
        }

    }

    public function __sleep()
    {
        $this->client = null;
        return array_keys(get_object_vars($this));
    }

}