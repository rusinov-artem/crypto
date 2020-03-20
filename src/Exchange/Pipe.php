<?php


namespace Crypto\Exchange;


class Pipe
{
    public $currencyList;
    public $volume;

    /**
     * @var ClientInterface
     */
    public $client;

    public $start;
    public $finish;

    public function calculate()
    {
        $currencyList = $this->currencyList;
        $first = array_shift($currencyList);
        $pairs = $this->client->getPairs();
        $current = array_shift($currencyList);

        $result = $this->spendFirst($this->volume, $first, $current, $pairs );

        $this->start = $result['spend'];

        $third = array_shift($currencyList);
        $result2 = $this->spendSecond($this->volume,  $current, $third, $pairs);

        $result = $this->spendThird($result2['v2'], $first, $third, $pairs);

        return
        [
            'start'=>$this->start,
            'end'=>$result,
            'profit'=> $result-$this->start - (($result * 0.001) * 2),
            'fee'=>(($result * 0.001) * 2),
        ];

    }

    //Buy First Coin
    public function spendFirst($volume /* in altCurrency */, $mainCurrency, $altCurrency, $pairs)
    {
        $currentPair = $this->findPairWith($mainCurrency, $altCurrency, $pairs);
        if(!$currentPair) return false;

        $ob1 = $this->client->getOrderBook($currentPair->id);

        if($mainCurrency === $currentPair->quoteCurrency)
        {
            //Покупаю коины за базовую
            $px1 = $this->getBuyPrice($currentPair->id, $volume, $ob1);

            return
            [
              'buyPrice' => $px1 / $volume, //mainCoin
              'volume' => $volume, // altCoin
              'spend' => $px1,  // mainCoin
            ];
        }
        else
        {
            return false;
            //Продаю "базовую" за коины
            $px1 = $this->getSellPrice($currentPair->id, $volume / $ob1->getBestBid()->price, $ob1);

            return
            [
               'buyPrice' => $px1/( $volume / $ob1->getBestBid()->price ), // altCoin / mainCoin
               'volume' => $px1, // altCoin
               'spend' =>  $volume / $ob1->getBestBid()->price // mainCoin
            ];
        }
    }

    //Buy First Coin
    public function spendSecond($volume /* in mainCurrency */, $mainCurrency, $altCurrency, $pairs)
    {
        $currentPair = $this->findPairWith($mainCurrency, $altCurrency, $pairs);
        if(!$currentPair) return false;

        $ob1 = $this->client->getOrderBook($currentPair->id);

        $v2 = $volume / $ob1->getBestAsk()->price;

        if($mainCurrency === $currentPair->quoteCurrency)
        {
            //Покупаю коины за базовую
            $px2 = $this->getBuyPrice($currentPair->id, $v2, $ob1);

            return
                [
                    'v2' => $v2, //altCoin
                    'volume' => $volume, // mainCoin
                    'spend' => $px2,  // mainCoin
                    'p2' => $px2 / $v2,
                ];
        }
        else
        {
            return false;
            //Продаю "базовую" за коины
            $px1 = $this->getSellPrice($currentPair->id, $volume, $ob1);

            return
                [
                    'buyPrice' => $px1/( $volume / $ob1->getBestBid()->price ), // altCoin / mainCoin
                    'volume' => $volume, // mainCoin
                    'spend' =>  $volume // $ob1->getBestBid()->price // mainCoin
                ];
        }
    }

    //Buy First Coin
    public function spendThird($volume /* in AltCoin */, $mainCurrency, $altCurrency, $pairs)
    {
        $currentPair = $this->findPairWith($mainCurrency, $altCurrency, $pairs);
        if(!$currentPair) return false;

        $ob1 = $this->client->getOrderBook($currentPair->id);


        if($mainCurrency === $currentPair->quoteCurrency)
        {
            //Покупаю коины за базовую
            $px2 = $this->getSellPrice($currentPair->id, $volume, $ob1);

            return $px2;
        }
        else
        {
            return false;
            //Продаю "базовую" за коины
            $px1 = $this->getSellPrice($currentPair->id, $volume, $ob1);

            return
                [
                    'buyPrice' => $px1/( $volume / $ob1->getBestBid()->price ), // altCoin / mainCoin
                    'volume' => $volume, // mainCoin
                    'spend' =>  $volume // $ob1->getBestBid()->price // mainCoin
                ];
        }
    }

    /**
     * @param $first
     * @param $second
     * @param Pair[] $pairs
     * @return bool|Pair|mixed
     */
    public function findPairWith($first, $second, array $pairs)
    {
        /**
         * @var $pair Pair
         */
        foreach ($pairs as $pair)
        {
            if($first === $pair->quoteCurrency && $second === $pair->baseCurrency)
            {
                return $pair;
            }
        }

        return false;
    }

    public function getBuyPrice($pairID, $volume, OrderBook $ob)
    {
        $result = 0;

        foreach ($ob->ask as $bookItem)
        {
            if($bookItem->size > $volume)
            {
                $result += $volume * $bookItem->price;
                return $result;
            }
            else
            {
                $result += $bookItem->size * $bookItem->price;
                $volume -= $bookItem->size;
            }
        }

        return null;
    }

    public function getSellPrice($pairID, $volume, OrderBook $ob)
    {
        $result = 0;

        foreach ($ob->bid as $bookItem)
        {
            if($bookItem->size > $volume)
            {
                $result += $volume * $bookItem->price;
                return $result;
            }
            else
            {
                $result += $bookItem->size * $bookItem->price;
                $volume -= $bookItem->size;
            }
        }

        return null;
    }
}