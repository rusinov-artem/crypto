<?php


namespace Crypto\Binance;


use Crypto\Exchange\PairLimit;

class Pair extends \Crypto\Exchange\Pair
{
    /**
     * @var Client
     */
    protected $client;

    public function getLimit()
    {
        $limit = &$this->limit;

        $this->initLotSize($limit);
        $this->initFee($limit);

        return $limit;
    }

    private function initLotSize(PairLimit &$limit)
    {
        if(!$this->client) return;

        if($limit->minNotional)
        {
            if($avgPrice = $this->client->getAveragePrice($this->id))
            {
                $limit->lotSize = (float)$limit->minNotional / $avgPrice;

                if($limit->qtyTick > 0)
                {
                    $limit->lotSize  =  (floor($limit->lotSize / $limit->qtyTick)  + 1) * $limit->qtyTick;
                }
            }
        }
    }
    private function initFee(PairLimit &$limit)
    {
        if(!$this->client) return;

        $response = $this->client->request("v3", 'GET', "account", []);

        if($response->getStatusCode() === 200)
        {

            $data = json_decode((string)$response->getBody(), true);

            $limit->feeCurrency = $this->quoteCurrency;
            $limit->takeLiquidityRate = $data['takerCommission'];
            $limit->provideLiquidityRate = $data['makerCommission'];
        }

    }

    public function setClient(Client &$client)
    {
        $this->client = &$client;
    }
}