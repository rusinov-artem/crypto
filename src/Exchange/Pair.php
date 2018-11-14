<?php


namespace Crypto\Exchange;


class Pair
{
    public $id;
    public $baseCurrency;
    public $quoteCurrency;

    /**
     * @var PairLimit
     */
    public $limit;

}