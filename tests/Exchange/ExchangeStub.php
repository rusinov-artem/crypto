<?php


namespace Crypto\Tests\Exchange;

// Биржа для тестирования поведения ботов
use Crypto\Exchange\Trade;

class ExchangeStub
{
    public $orders;

    /**
     * @var Trade[]
     */
    public $trades;

    public $balances;

    public $pairs;

    public $orderBooks;

    public function getOrder($id)
    {}

    public function fillOrder($id, $value)
    {}



}