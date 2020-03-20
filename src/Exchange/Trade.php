<?php


namespace Crypto\Exchange;


class Trade
{
    public $id;
    public $accessID;
    public $eTradeID;
    public $pairID;
    public $eClientOrderID;
    public $eOrderID;
    public $side;
    public $price;
    public $value;

    /**
     * @var \DateTime
     */
    public $date;
    public $fee;
    public $feeCurrency;
}