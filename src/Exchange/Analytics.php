<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 12/8/2018
 * Time: 1:26 AM
 */

namespace Crypto\Exchange;




class Analytics
{
    /**
     * @var ClientInterface
     */
    public $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getTradeIndex($pair, \DateInterval $interval)
    {
        $sellAmount = 0;
        $buyAmount = 0;
        $baseSellAmount = 0;
        $baseBuyAmount = 0;
        $dt = (new \DateTime())->setTimezone( new \DateTimeZone("UTC"));

        $this->client->getPairTrades($pair, function(Trade $item)use($dt, $interval, &$sellAmount, &$buyAmount, &$baseSellAmount, &$baseBuyAmount){

            if($item->side == 'sell')
            {
                $sellAmount += $item->value;
                $baseSellAmount += $item->value * $item->price;
            }
            else
            {
                $buyAmount +=$item->value;
                $baseBuyAmount += $item->value * $item->price;
            }


            if((clone $dt)->sub($interval)->getTimestamp() > $item->date->getTimestamp())
            {
                return false;
            }

            return true;

        });

        if($sellAmount > 0)
        {
            return $buyAmount / $sellAmount;
        }
        else
        {
            return 999999999;
        }

    }
}