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

    /**
     * @param string $pairID
     * @param float $lVolume
     * @param float $buyPrice
     * @param float $priceStep
     * @param float $deltaPrice
     * @param int $count
     * @return CircleBot[]
     */
    public static function spreadAttack(string $pairID, float $lVolume, float $buyPrice, float $priceStep, float $deltaPrice, int $count)
    {
        $result = [];
        for($i=0; $i<$count; $i++)
        {
            $price = $buyPrice - ($priceStep * $i);
            $bot = self::simple($pairID, $lVolume, $price, $deltaPrice, $i+1 );
            $bot->id = "spread_".$bot->id;
            $result[] = $bot;
        }
        return $result;
    }

    public static function spreadAttackStatic(string $pairID, float $lVolume, float $buyPrice, float $priceStep, float $deltaPrice, int $count, int $r = 3)
    {
        $result = [];
        for($i=0; $i<$count; $i++)
        {
            $price = $buyPrice - ($priceStep * $i);
            $bot = self::simple($pairID, $lVolume, $price, $deltaPrice, $r );
            $bot->id = "spreadStatic_".$bot->id;
            $result[] = $bot;
        }
        return $result;
    }
}

