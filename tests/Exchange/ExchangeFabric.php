<?php


namespace Crypto\Tests\Exchange;


use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\OrderBook;
use Crypto\Exchange\OrderBookItem;
use Crypto\Exchange\Pair;
use Crypto\Exchange\PairLimit;

class ExchangeFabric
{
    public static function make()
    {
        $ex = new ExchangeStub();

        $pair = new Pair();
        $pair->id = "BTCUSD";
        $pair->quoteCurrency = "BTC";
        $pair->baseCurrency = "USD";

        $limit = new PairLimit();
        $limit->pairID = "BTCUSD";
        $limit->provideLiquidityRate = 0.001;
        $limit->takeLiquidityRate = 0.001;
        $limit->priceTick = 0.001;
        $limit->lotSize = 0.01;
        $limit->feeCurrency = "USD";

        $pair->limit = $limit;


        $ex->pairs[$pair->id] = $pair;
        //var_dump($ex->pairs);

        $ob = new OrderBook();

        $obItem = new OrderBookItem();
        $obItem->price = 6000;
        $obItem->size = 1000;
        $ob->bid[] = $obItem;

        $obItem = new OrderBookItem();
        $obItem->price = 8000;
        $obItem->size = 1000;
        $ob->ask[] = $obItem;

        $balance = new CurrencyBalance();
        $balance->currency = "BTC";
        $balance->available = 1000;
        $balance->reserved = 0;

        $ex->balances[$balance->currency] = $balance;

        $balance = new CurrencyBalance();
        $balance->currency = "USD";
        $balance->available = 1000;
        $balance->reserved = 0;

        $ex->balances[$balance->currency] = $balance;

        $ex->orderBooks["BTCUSD"] = $ob;

        return $ex;


    }
}