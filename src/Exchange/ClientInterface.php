<?php

namespace Crypto\Exchange;

interface ClientInterface
{
    /**
     * @return Pair[]
     */
    public function getPairs();

    /**
     * @return CurrencyBalance[]
     */
    public function getBalance();

    public function getNonZeroBalance();

    /**
     * @param Order $order
     * @return Order
     */
    public function createOrder(Order &$order);

    /**
     * @param Order $order
     * @return Order
     */
    public function closeOrder(Order &$order);

    /**
     * @return Order[]
     */
    public function getActiveOrders();

    /**
     * @param Order $order
     * @return bool
     */
    public function checkOrderIsActive(Order &$order);

    public function getOrderStatus(Order &$order);

    public function getOrderTrades(Order &$order);

    public function chunkAccountTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100);

    public function getOrderBook($pairID, $limit = 100);

    public function getPairTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100);
}