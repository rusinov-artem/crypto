<?php


namespace Crypto\Exchange;


class Pair
{
    public $id;
    public $baseCurrency;
    public $QuoteCurrency;

    /**
     * @var PairLimit
     */
    public $limit;

}