<?php


namespace Crypto\Exchange;


class Order
{
    public $id = null; //Идентификатор ордера на бирже
    public $date; //Дата размещения ордера на бирже
    public $price;
    public $value;
    public $pairID; //И
    public $side; //"buy" or "sell"
    public $traded = 0; //Количество проданых\купленных монет
    public $status; // new, suspended, partiallyFilled, filled, canceled, expired

    public function isActive()
    {
        return in_array($this->status, ['new', 'partiallyFilled']);
    }
}