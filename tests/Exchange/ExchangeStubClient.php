<?php


namespace Crypto\Tests\Exchange;


use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Exceptions\OrderNotFound;
use Crypto\Exchange\Exceptions\PairNotFound;
use Crypto\Exchange\Order;
use Crypto\Exchange\Pair;


class ExchangeStubClient implements ClientInterface
{

    /**
     * @var ExchangeStub
     */
    public $exchange;

    public function __construct(ExchangeStub $exchangeStub)
    {
        $this->exchange = $exchangeStub;
    }


    /**
     * @return Pair[]
     */
    public function getPairs()
    {
        return $this->exchange->pairs;
    }

    /**
     * @return CurrencyBalance[]
     */
    public function getBalance()
    {
       return  $this->exchange->balances;
    }

    public function getNonZeroBalance()
    {
        $result = [];

        foreach ($this->getBalance() as $balance)
        {
            if($balance->available > 0 || $balance->reserved > 0)
            {
                $result[$balance->currency] = $balance;
            }
        }

        return $result;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function createOrder(Order &$order)
    {
        $id = $this->generateRandomString();
        $order->id = $id;
        $order->date = new \DateTime();
        $order->status = 'new';
        $this->exchange->orders[$id] = clone $order;
        return $order;
    }

    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws OrderNotFound
     */
    public function closeOrder(Order &$order)
    {
        if(array_key_exists($order->id, $this->exchange->orders))
        {
            $orderE =  $this->exchange->orders[$order->id];
            $orderE->status = 'canceled';
            $order = clone $orderE;
        }
        else
        {
            throw new OrderNotFound($order->id);
        }

        return $order;
    }

    /**
     * @return Order[]
     */
    public function getActiveOrders()
    {
        $result = [];
        foreach ($this->exchange->orders as $order)
        {
            if(in_array($order->status, ['new', 'partiallyFilled']))
            {
                $result[$order->id] = clone $order;
            }
        }
        return $result;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function checkOrderIsActive(Order &$order)
    {
        $activeOrders = $this->getActiveOrders();
        $result =  array_key_exists($order->id, $activeOrders);
        if($result)
        {
            $order = clone $activeOrders[$order->id];
        }

        return $result;
    }

    public function getOrderStatus(Order &$order)
    {
        if(!array_key_exists($order->id, $this->exchange->orders))
        {
            throw new OrderNotFound($order->id);
        }

        $order =  clone $this->exchange->orders[$order->id];
        return $order->status;
    }

    public function getOrderTrades(Order &$order)
    {
        $result = [];

        foreach ($this->exchange->trades as $trade)
        {
            if($trade->orderID === $order->id)
                $result[$trade->id] = clone $trade;
        }

        return $result;
    }

    public function chunkAccountTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100)
    {
        foreach ($this->exchange->trades as $trade)
        {
            if($trade->orderID === $pairID)
            {
                $r = $func(clone $trade);
                if($r === false) return;
            }
        }
    }

    public function getOrderBook($pairID, $limit = 100)
    {
        if(!array_key_exists($pairID, $this->exchange->pairs))
        {
            throw new PairNotFound($pairID);
        }

        return $this->exchange->orderBooks[$pairID];
    }

    public function getPairTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100)
    {
        return [];
    }
}