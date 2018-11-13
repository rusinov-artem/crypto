<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/9/2018
 * Time: 7:55 PM
 */

namespace Crypto\HitBTC;

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

    public function getPairs($pairID=null)
    {
        if($pairID === null)
            return $this->request("GET", 'public/symbol', []);

        $data = $this->request("GET", 'public/symbol', []);

        foreach ($data as $item)
        {
            if($item['id'] == $pairID)
                return $item;
        }

        return false;
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

        foreach ($orders as $order)
        {
            if($order['clientOrderId'] === $orderID) return true;
        }

        return false;
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