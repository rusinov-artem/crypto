<?php


namespace Crypto\Exchange;


class OrderBook
{
    /**
     * @var OrderBookItem[]
     */
    public $bid;

    /**
     * @var OrderBookItem[]
     */
    public $ask;

    public function getBestBid()
    {
        return current($this->bid);
    }

    public function getBestAsk()
    {
        return current($this->ask);
    }
}