<?php


namespace Crypto\Exchange;


class Order
{
    public $id = null; //Идентификатор ордера в БД
    public $eOrderID; //Идентификатор ордера на бирже
    public $eClientOrderID; //Клиентский идентификатор ордера на бирже
    public $date; //Дата размещения ордера на бирже
    public $price;
    public $value;
    public $pairID; //Идентификатор пары
    public $side; //"buy" or "sell"
    public $traded = 0; //Количество проданых\купленных монет
    public $status; // new, suspended, partiallyFilled, filled, canceled, expired
    public $type = "limit"; //limit, market
    public $timeInForce = 'GTC';

    public function isActive()
    {
        return in_array($this->status, ['new', 'partiallyFilled']);
    }
}