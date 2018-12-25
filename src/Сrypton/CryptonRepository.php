<?php


namespace Crypto\Сrypton;


use Crypto\Exchange\Order;
use Crypto\Exchange\Trade;

class CryptonRepository
{

    public $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    //Вернуть последний обработанный trade
    public function getLastTrade($exchangeID, $pairID)
    {

    }

    //Обработать новый трейд
    public function handleTrade($exchangeID, Trade $trade)
    {

    }

    public function getOrder($exchangeID, $pairID, array $orderIDs)
    {

    }

    public function storeOrder(Order $order)
    {

    }

}