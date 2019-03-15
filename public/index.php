<?php

use Crypto\Exchange\Trade;

require __DIR__ . "/../vendor/autoload.php";
$config = include __DIR__ . "/../config.php";
$hit = new \Crypto\HitBTC\Client($config['hitbtc.api.key'], $config['hitbtc.api.secret']);

$data = $hit->getOrdersHistory(true, ['symbol'=>"EDOUSD"]);

foreach ($data as $order)
{
    if($order->price == 0.773597 || $order->price == 0.775657)
    {
        var_dump([
            'order'=>$order->eOrderID,
            'price'=>$order->price,
            'side'=>$order->side,
            'created_at'=>$order->date->format("Y-m-d H:i:s.u"),
        ]);

        $hit->chunkAccountTrades($order->pairID, function ($item) use ($order, &$trades)
        {
            /**
             * @var $item Trade
             */
            if($item->date < $order->date) return false;

            if($item->eOrderID == $order->eOrderID)
            {
                var_dump([
                    'trade'=>$item->date->format("Y-m-d H:i:s.u"),
                    'price'=>$item->price,
                    'volume'=>$item->value,
                    'side'=>$item->side,
                ]);
            }

            return true;

        });
    }

}