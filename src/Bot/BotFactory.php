<?php


namespace Crypto\Bot;


use Crypto\Exchange\Order;

class BotFactory
{
    public static function simple( string $pairID, float $volume, float $buyPrice, float $deltaPrice, int $retry=3, string $botID = null)
    {

        $bot = new CircleBot();
        $bot->circles = $retry;

        $inOrder = new Order();
        $inOrder->side = 'buy';
        $inOrder->pairID = $pairID;
        $inOrder->price = $buyPrice;
        $inOrder->value = $volume;

        $bot->inOrder = $inOrder;

        $outOrder = clone $inOrder;
        $outOrder -> side = 'sell';
        $outOrder->price += $deltaPrice;

        $bot->outOrder = $outOrder;

        if($botID !== null)
        {
            $bot->id = $botID;
        }
        else
        {
            $bot->id = "{$bot->inOrder->pairID}_{$bot->inOrder->price}-{$bot->outOrder->price}_".uniqid();
        }

        return $bot;
    }
}