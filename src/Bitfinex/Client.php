<?php


namespace Crypto\Bitfinex;

use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Exceptions\OrderNotFound;
use Crypto\Exchange\Exceptions\OrderRejected;
use Crypto\Exchange\Exceptions\UnknownError;
use Crypto\Exchange\Exceptions\ValidationError;
use Crypto\Exchange\Order;
use Crypto\Exchange\OrderBook;
use Crypto\Exchange\Pair;

class Client implements ClientInterface
{

    /**
     * @return Pair[]
     */
    public function getPairs()
    {
        // TODO: Implement getPairs() method.
    }

    /**
     * @return CurrencyBalance[]
     */
    public function getBalance()
    {
        // TODO: Implement getBalance() method.
    }

    public function getNonZeroBalance()
    {
        // TODO: Implement getNonZeroBalance() method.
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function createOrder(Order &$order)
    {
        // TODO: Implement createOrder() method.
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function closeOrder(Order &$order)
    {
        // TODO: Implement closeOrder() method.
    }

    /**
     * @return Order[]
     */
    public function getActiveOrders()
    {
        // TODO: Implement getActiveOrders() method.
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function checkOrderIsActive(Order &$order)
    {
        // TODO: Implement checkOrderIsActive() method.
    }

    public function getOrderStatus(Order &$order)
    {
        // TODO: Implement getOrderStatus() method.
    }

    public function getOrderTrades(Order &$order)
    {
        // TODO: Implement getOrderTrades() method.
    }

    public function chunkAccountTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100)
    {
        // TODO: Implement chunkAccountTrades() method.
    }

    /**
     * @param $pairID
     * @param int $limit
     * @return OrderBook
     * @throws OrderNotFound
     * @throws OrderRejected
     * @throws UnknownError
     * @throws ValidationError
     */
    public function getOrderBook($pairID, $limit = 100)
    {
        // TODO: Implement getOrderBook() method.
    }

    public function getPairTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100)
    {
        // TODO: Implement getPairTrades() method.
    }
}