<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/9/2018
 * Time: 7:55 PM
 */

namespace Crypto\HitBTC;

use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Order;
use Crypto\Exchange\OrderBook;
use Crypto\Exchange\OrderBookItem;
use Crypto\Exchange\Pair;
use Crypto\Exchange\PairLimit;
use Crypto\Exchange\Trade;

Class Client implements ClientInterface
{

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

    /**
     * @return Pair[]
     */
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

    /**
     * @return CurrencyBalance[]
     */
    public function getBalance()
    {
       $data = $this->request("GET", 'trading/balance', []);

       $result = [];

       foreach ($data as $item)
       {
           $balance = new CurrencyBalance();
           $balance->currency = $item['currency'];
           $balance->available  = $item['available'];
           $balance->reserved = $item['reserved'];

           $result[$item['currency']] = $balance;
       }

       return $result;
    }

    public function getNonZeroBalance()
    {
        $data = $this->getBalance();

        $result = [];

        foreach($data as $item)
        {
            if($item->available > 0 || $item->reserved > 0)
            {
                $result[$item->currency] = $item;
            }
        }

        return $result;


    }

    /**
     * @param Order $order
     * @return Order
     */
    public function createOrder(Order &$order)
    {
        $data =  $this->request("POST", 'order', [
            'symbol' => $order->pairID,
            'side' => $order->side,
            'type' => 'limit',
            'timeInForce' => 'GTC',
            'quantity' => $order->value,
            'price' => $order->price,
        ]);

        $order = new Order();
        $order->pairID = $data['symbol'];
        $order->id = $data['clientOrderId'];
        $order->side = $data['side'];
        $order->value = $data['quantity'];
        $order->price = $data['price'];
        $order->date = new \DateTime($data['createdAt']);
        $order->status = $data['status'];
        $order->traded = $data['cumQuantity'];


        return $order;

    }

    /**
     * @param Order $order
     * @return Order
     */
    public function closeOrder(Order &$order)
    {
        $item = $this->request("DELETE", "order/{$order->id}", []);

        $order = new Order();
        $order->pairID = $item['symbol'];
        $order->id = $item['clientOrderId'];
        $order->side = $item['side'];
        $order->value = $item['quantity'];
        $order->price = $item['price'];
        $order->date =  new \DateTime($item['createdAt']);
        $order->status = 'canceled';
        $order->traded = $item['cumQuantity'];

        return $order;


    }

    /**
     * @return Order[]
     */
    public function getActiveOrders()
    {
        $data = $this->request("GET", 'order', []);

        $result = [];
        foreach ($data as $item)
        {
            $order = new Order();
            $order->pairID = $item['symbol'];
            $order->id = $item['clientOrderId'];
            $order->side = $item['side'];
            $order->value = $item['quantity'];
            $order->price = $item['price'];
            $order->date =  new \DateTime($item['createdAt']);
            $order->status = $item['status'];
            $order->traded = $item['cumQuantity'];

            $result[$order->id] = $order;
        }
        return $result;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function checkOrderIsActive(Order &$order)
    {
        $orders = $this->getActiveOrders();

        if($result = (array_key_exists($order->id, $orders)))
        {
            $order = $orders[$order->id];
        }

        return $result;

    }

    public function getOrderStatus(Order &$order)
    {
        if($isActive = $this->checkOrderIsActive($order))
        {
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

        $this->chunkAccountTrades($order->pairID, function ($item) use ($order, &$trades)
        {
            /**
             * @var $item Trade
             */
            if($item->date < $order->date) return false;

            if($item->orderID === $order->id)
            {
                $trades += $item->value;
            }

            return true;

        });

        return $order->traded = $trades;

    }

    public function chunker(callable $func, $method, $action, array $params,  $chunkSize, callable $itemConverter)
    {
        $go = true;
        $counter = 0;

        if($chunkSize < 1)
        {
            $chunkSize = 100;
        }

        $params['limit'] = $chunkSize;
        $params['offset'] = 0;

        while($go)
        {

            $data = $this->request($method, $action, $params);

            if(count($data) < 1) break;

            foreach ($data as $item)
            {
                $counter++;
                $item = $itemConverter($item);
                $r = $func($item);
                if($r === false) return $counter;
            }

            $params['offset']  += $chunkSize;

        }

        return $counter;
    }

    public function chunkAccountTrades($pairID, callable $func, $sort="DESC", $chunkSize=100)
    {
        $p =
            [
              'sort'=>$sort,
            ];

        if($pairID !== null)
        {
            $p['symbol'] = $pairID;
        }

       return $this->chunker($func, 'GET', 'history/trades', $p, $chunkSize, function($item){

           $trade = new Trade();
           $trade->date = new \DateTime($item['timestamp']);
           $trade->id = $item['id'];
           $trade->orderID = $item['clientOrderId'];
           $trade->pairID = $item['symbol'];
           $trade->side = $item['side'];
           $trade->value = $item['quantity'];
           $trade->fee = $item['fee'];
           $trade->price = $item['price'];

           return $trade;

       });
    }

    public function getOrderBook($pairID, $limit = 100)
    {
        $data = $this->request('GET', "public/orderbook/$pairID", ['limit'=>$limit]);

        $orderBook = new OrderBook();

        foreach ($data['ask'] as $item)
        {
            $i = new OrderBookItem();
            $i->price = $item['price'];
            $i->size = $item['size'];
            $orderBook->ask[] = $i;
        }
        unset($item);

        foreach ($data['bid'] as $item)
        {
            $i = new OrderBookItem();
            $i->price = $item['price'];
            $i->size = $item['size'];
            $orderBook->bid[] = $i;
        }

        return $orderBook;
    }

    public function getPairTrades($pairID, callable $func, $sort="DESC", $chunkSize=100)
    {
        $p =
            [
                'sort'=>$sort,
            ];


        return $this->chunker($func, 'GET', "public/trades/$pairID", $p, $chunkSize, function($item) use($pairID){
            $trade = new Trade();
            $trade->price = $item['price'];
            $trade->value = $item['quantity'];
            $trade->pairID = $pairID;
            $trade->side = $item['side'];
            $trade->date = new \DateTime($item['timestamp']);
            return $trade;
        } );
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