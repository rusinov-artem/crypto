<?php


namespace Crypto\Bot\Events;


use Crypto\Bot\BotNext;
use Symfony\Component\EventDispatcher\Event;

class BotNextEvent extends Event
{
    public $bot;

    public function __construct(BotNext $bot)
    {
        $this->bot = $bot;
    }
}