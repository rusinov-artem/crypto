<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 12/7/2018
 * Time: 12:42 AM
 */

namespace Crypto\Deal;


use Crypto\Bot\BotFactory;
use Crypto\Bot\BotStorage;

class LongDeal
{
    public $id;

    public $bots;
    public $mainBot;


    /**
     * @var BotStorage
     */
    public $botStorage;

    /**
     * @var
     */
    protected $client;

    public $pairID;

    public function init()
    {

    }

    public function setMainBot( float $volume, float $buyPrice, float $deltaPrice, int $retry=3 )
    {
        BotFactory::simple($this->pairID,  $volume,  $buyPrice,  $deltaPrice );
    }

    public function setPair($pairID)
    {
        $this->pairID = $pairID;
    }

    public function setClient()
    {

    }
}