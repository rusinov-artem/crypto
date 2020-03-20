<?php


namespace Crypto\Exchange\Events;


use Crypto\Exchange\Trade;
use Symfony\Component\EventDispatcher\Event;

class NewTrade extends Event
{
    public $trade;
    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
    }
}