<?php


namespace Crypto\Bot;

use Crypto\Bot\Events\InOrderCreated;
use Crypto\Bot\Events\InOrderExecuted;
use Crypto\Bot\Events\OutOrderCreated;
use Crypto\Bot\Events\OutOrderExecuted;
use Crypto\Exchange\Order;
use Crypto\HitBTC\Client;
use Crypto\Traits\Loggable;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class BotNext
{

    use Loggable;

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

    /**
     * @var EventDispatcher
     */
    public $dispatcher;

    public function isFinished()
    {
        if($this->inOrder->status === 'filled' && $this->outOrder->status === 'filled')
        {
            return true;
        }

        if($this->inOrder->status === 'canceled' || $this->outOrder->status === 'canceled')
        {
            //return true;
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
            if(($this->inOrder->isActive() || $this->inOrder->status === 'canceled' )&& $this->outOrder->status == null)
            {
                return [ 'action' => [$this, 'checkInOrder'], 'params' => [] ];
            }

            return false;

        };

        $routes[] = function ()
        {
            if($this->inOrder->status === 'filled' || ( $this->outOrder->isActive() || $this->outOrder->status === 'canceled') )
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

    public function createInOrder()
    {

        $ob = $this->client->getOrderBook($this->inOrder->pairID);

        if($this->inOrder->side === 'buy')
        {
            if($ob->getBestAsk()->price <= $this->inOrder->price )
            {
                $this->log("WARNING! Order will not be placed cose actual price lower then buy order price");
                return false;
            }
        }
        elseif($this->inOrder->side === 'sell')
        {
            if($ob->getBestBid()->price >= $this->inOrder->price )
            {
                $this->log("WARNING! Order will not be placed cose actual price higher then sell order price");
                return false;
            }
        }
        else
        {
            throw new \Exception("Unexpected order side");
        }


        $this->client->createOrder($this->inOrder);
        $this->fire('BotNext.InOrderCreated', new InOrderCreated($this));

    }

    public function checkInOrder()
    {
        $this->log('Checking in order');
        $status = $this->client->getOrderStatus($this->inOrder);

        if('filled' === $status)
        {
            $this->fire('BotNext.InOrderExecuted', new InOrderExecuted($this));
            $this->client->createOrder($this->outOrder);
            $this->fire('BotNext.OutOrderCreated', new OutOrderCreated($this));
        }
    }

    public function checkOutOrder()
    {
        $this->log('Checking out order');
        $status = $this->client->getOrderStatus($this->outOrder);

        if('filled' === $status)
        {
            $this->fire('BotNext.OutOrderExecuted', new OutOrderExecuted($this));
            //bot finished;
        }

    }

    public function __sleep()
    {
        $this->client = null;
        return array_keys(get_object_vars($this));
    }

    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function fire($eventName, Event $event)
    {
        if($this->dispatcher)
        {
            $this->dispatcher->dispatch($eventName, $event);
        }
    }

}