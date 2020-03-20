<?php


namespace Crypto\HitBTC;

use Crypto\Exchange\Events\NewTrade;
use Crypto\Exchange\Trade;
use Crypto\Crypton\CryptonRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TradeManager
{

    private $client;
    private $repo;

    /**
     * @var EventDispatcher
     */
    public $dispatcher;

    public function __construct( Client $client, CryptonRepository $repo, EventDispatcher $dispatcher )
    {
        $this->client = $client;
        $this->repo = $repo;
        $this->dispatcher = $dispatcher;
    }

    public function loadTrades($accessID,  $pairID )
    {
        $lastTrade = $this->repo->getLastTrade($accessID, $pairID);

        if(!$lastTrade)
        {
            $lastTrade = new Trade();
            $lastTrade->date = (new \DateTime())->sub(new \DateInterval("P1D"));
        }

        $trades = [];
        $this->client->chunkAccountTrades($pairID, function (Trade $trade) use ($lastTrade, &$trades, $accessID) {

            if($trade->date->getTimestamp() <= $lastTrade->date->getTimestamp())
                return false;

            $trade->accessID = $accessID;
            $trades[] = $trade;

        });

        foreach ($trades as $trade)
        {
            if($this->repo->handleTrade($accessID, $trade))
            {
                $this->dispatcher->dispatch('TradeManager.NewTrade', new NewTrade($trade));
            }
        }
    }


}