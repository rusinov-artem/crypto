<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/9/2018
 * Time: 7:55 PM
 */

namespace Crypto\HitBTC;

use Crypto\Exchange\Pair;
use Crypto\Exchange\PairLimit;

Class Client{

    public $apiKey;
    public $secretKey;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function __construct($apiKey, $secretKey)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;

        $this->client = new \GuzzleHttp\Client();
    }

    public function getPairs()
    {
        $data = $this->request("GET", 'public/symbol', []);

        $result = [];
        foreach ($data as $item)
        {
            $limit = new PairLimit();
            $limit->lotSize = $item['quantityIncrement'];
            $limit->priceTick = $item['tickSize'];
            $limit->feeCurrency = $item['feeCurrency'];
            $limit->takeLiquidityRate = $item['takeLiquidityRate'];
            $limit->provideLiquidityRate = $item['provideLiquidityRate'];
            $limit->pairID = $item['id'];

            $pair = new Pair();
            $pair->id = $item['id'];
            $pair->baseCurrency = $item['baseCurrency'];
            $pair->quoteCurrency = $item['quoteCurrency'];
            $pair->limit = $limit;

            $result[$pair->id] = $pair;

        }

        return $result;
    }


    public function getBalance()
    {
       return $this->request("GET", 'trading/balance', []);
    }

    public function getNonZeroBalance()
    {
        $data = $this->getBalance();

        $result = [];

        foreach($data as $item)
        {
            if($item['available'] > 0 || $item['reserved'] > 0)
            {
                $result[] = $item;
            }
        }

        return $result;


    }

    public function createOrder($pairID, $direction, $amount, $price)
    {
        return $this->request("POST", 'order', [
            'symbol' => $pairID,
            'side' => $direction,
            'type' => 'limit',
            'timeInForce' => 'GTC',
            'quantity' => $amount,
            'price' => $price,

        ]);
    }

    public function closeOrder($orderClientID)
    {
        return $this->request("DELETE", "order/$orderClientID", []);
    }

    public function getActiveOrders()
    {
        return $this->request("GET", 'order', []);
    }

    public function checkOrderIsActive($orderID)
    {
        $orders = $this->getActiveOrders();

        foreach ($orders as $orderData)
        {
            if($orderData['clientOrderId'] === $orderID) {
                $order = new Order();
                return $order->init($orderData);
            }
        }

        return false;
    }

    public function getOrderStatus(Order &$order)
    {
        if($co = $this->checkOrderIsActive($order->id))
        {
            unset($order);
            $order = $co;
            return $order->status;
        }

        //Ордер либо полностью исполнен либо canceled;

        $this->getOrderTrades($order);

        if($order->traded >= $order->value)
        {
            $order->status = 'filled';
        }
        else
        {
            $order->status = 'canceled';
        }

        return $order->status;

    }

    public function getOrderTrades(Order &$order)
    {
        $trades = 0;

        $this->getAccountTrades($order->pairID, function ($item) use ($order, &$trades)
        {
            $td = new \DateTime($item['timestamp']);
            if($td < $order->date) return false;

            if($item['clientOrderId'] === $order->id)
            {
                $trades += $item['quantity'];
            }

            return true;

        });

        return $order->traded = $trades;

    }

    public function chunker(callable $func, $method, $action, array $params,  $chunkSize = 100)
    {
        $go = true;
        $counter = 0;
        if($chunkSize)
        {
            $params['limit'] = $chunkSize;
            $params['offset'] = 0;
        }

        while($go)
        {

            $data = $this->request($method, $action, $params);

            if(count($data) < 1) break;

            foreach ($data as $item)
            {
                $counter++;
                $r = $func($item);
                if($r === false) return $counter;
            }

            $params['offset']  += $chunkSize;

        }

        return $counter;
    }

    public function getAccountTrades($pairID, callable $func, $sort="DESC", $chunkSize=100)
    {
        $p =
            [
              'sort'=>$sort,
            ];

        if($pairID !== null)
        {
            $p['symbol'] = $pairID;
        }

       return $this->chunker($func, 'GET', 'history/trades', $p, $chunkSize );
    }

    public function getOrderBook($pairID, $limit = 100)
    {
        return $this->request('GET', "public/orderbook/$pairID", ['limit'=>$limit]);
    }

    public function getBestBidAsk($pairID)
    {
        $data = $this->getOrderBook($pairID);

        $result = [
          'bid' => current($data['bid']),
          'ask' => current($data['ask']),
        ];

        return $result;
    }

    public function getPairTrades($pairID, callable $func, $sort="DESC", $chunkSize=100)
    {
        $p =
            [
                'sort'=>$sort,
            ];


        return $this->chunker($func, 'GET', "public/trades/$pairID", $p, $chunkSize );
    }

    public function request($method, $action, array $params)
    {

        $dataToSend = [];

        $dataToSend['auth'] = [
            $this->apiKey,
            $this->secretKey
        ];

        if(strtolower($method) !== "get")
        {
            $dataToSend['form_params'] = $params;
        }
        else
        {
            $dataToSend['query'] = $params;
        }


        $response = $this->client->request($method, "https://api.hitbtc.com/api/2/" . $action, $dataToSend);

        return json_decode((string)$response->getBody(), true);
    }

}