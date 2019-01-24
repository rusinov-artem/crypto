<?php


namespace Crypto\Bot;


use Crypto\Exchange\Order;
use Crypto\HitBTC\Client;

class BotFactory
{
    public static function simple( string $pairID, float $volume, float $buyPrice, float $deltaPrice, int $retry=3, string $botID = null)
    {

        $bot = new CircleBot();
        $bot->circles = $retry;

        $inOrder = new Order();
        $inOrder->side = $volume > 0 ? 'buy' : 'sell';
        $inOrder->pairID = $pairID;
        $inOrder->price = $buyPrice;
        $inOrder->value = abs($volume);

        $bot->inOrder = $inOrder;

        $outOrder = clone $inOrder;
        $outOrder->side = $volume > 0 ? 'sell' : 'buy';
        $outOrder->price += ( $volume <=> 0 ) * abs($deltaPrice);
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
     * @throws \Exception
     */
    public static function spreadAttack(string $pairID, float $lVolume, float $buyPrice, float $priceStep, float $deltaPrice, int $count)
    {
        $result = [];
        for($i=0; $i<$count; $i++)
        {
            $price = $buyPrice - ( $lVolume <=> 0 ) * ($priceStep * $i);
            $bot = self::simple($pairID, $lVolume, $price, $deltaPrice, $i+1);
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

    public static function spreadMartinSV(float $buyPrice, Client $client)
    {
        $pairID = "BCHSVUSD";

        $balance = $client->getNonZeroBalance()['USD']->available;
        $balance = 600;
        $lVolume=0.1;
        $priceStep=0.1;
        $tp = 1;

        $result = [];

        $i=0;
        $price = $buyPrice - ($priceStep * $i);

        while($balance > $price * $lVolume){
            $i++;

            $bot = self::simple($pairID, $lVolume, $price, $tp, $i );

            var_dump(" ({$bot->inOrder->value}) {$bot->inOrder->price} => {$bot->outOrder->price}");

            $bot->id = "spreadStatic_".$bot->id;
            $lVolume += 0.1;
            $balance -= $price * $lVolume;
            $priceStep += 0.1;
            $tp += 0.05;
            $price = $buyPrice - ($priceStep * $i);
            $result[] = $bot;

        };

        var_dump($i);

        return $result;
    }
}

