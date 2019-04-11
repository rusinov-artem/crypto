<?php

namespace Crypto\HitBTC;

use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Exceptions\ActionIsForbidden;
use Crypto\Exchange\Exceptions\AuthorisationFail;
use Crypto\Exchange\Exceptions\CurrencyNotFound;
use Crypto\Exchange\Exceptions\ExchangeError;
use Crypto\Exchange\Exceptions\OrderNotFound;
use Crypto\Exchange\Exceptions\OrderRejected;
use Crypto\Exchange\Exceptions\PairNotFound;
use Crypto\Exchange\Exceptions\TooManyRequests;
use Crypto\Exchange\Exceptions\UnknownError;
use Crypto\Exchange\Exceptions\ValidationError;
use Crypto\Exchange\Order;
use Crypto\Exchange\OrderBook;
use Crypto\Exchange\OrderBookItem;
use Crypto\Exchange\Pair;
use Crypto\Exchange\PairLimit;
use Crypto\Exchange\Trade;
use Crypto\Traits\Loggable;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;

Class Client implements ClientInterface
{
   use Loggable;

    public $apiKey;
    public $secretKey;


    //Cache
    public $trades = [];
    public $pairs = [];
    public $activeOrders = null;
    public $historicalOrders = null;
    public $orderBook = [];

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function loadTrades($pair, $mCount = 999)
    {


        if(array_key_exists( $pair, $this->trades )) return;

        $client = $this;
        $i = 0;
        $this->chunkAccountTrades($pair, function($trade, $index, $count) use (&$client, &$i, &$mCount, $pair)
        {
            /**
             * @var $item Trade
             */

            $i++;

            if($i >= $mCount) {
                return false;
            }

            $client->trades[$pair][] = $trade;


            if($count < 1000 && $index == $count)
            {
                return false;
            }

            return true;

        }, "DESC", 1000);

        if(!array_key_exists($pair, $this->trades))
        {
            $this->trades[$pair] = [];
        }
    }
    public function clearPreloadedTrades($pair=null)
    {
        if($pair === null)
        {
            $this->trades = [];
            return;
        }

        if(array_key_exists($pair, $this->trades))
            unset($this->trades[$pair]);

    }

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
        if(!empty($this->pairs))
        {
            return $this->pairs;
        }

        $response = $this->request("GET", 'public/symbol', []);

        if($response->getStatusCode() == 200)
        {
            $data = json_decode((string)$response->getBody(), true);
            $result = [];
            foreach ($data as $item)
            {
                $limit = new PairLimit();
                $limit->lotSize = (float) $item['quantityIncrement'];
                $limit->qtyTick = (float) $item['quantityIncrement'];
                $limit->priceTick = (float) $item['tickSize'];
                $limit->feeCurrency =  $item['feeCurrency'];
                $limit->takeLiquidityRate = (float) $item['takeLiquidityRate'];
                $limit->provideLiquidityRate = (float) $item['provideLiquidityRate'];
                $limit->pairID = $item['id'];

                $pair = new Pair();
                $pair->id = $item['id'];
                $pair->baseCurrency = $item['baseCurrency'];
                $pair->quoteCurrency = $item['quoteCurrency'];
                $pair->limit = $limit;

                $result[$pair->id] = $pair;
            }

            $this->pairs = $result;
            return $result;
        }

        $ex = $this->handleErrorResponse($response);

        if($ex instanceof \Exception)
        {
            throw new $ex;
        }

    }

    public function handleErrorResponse(Response $response)
    {

        $error = json_decode((string) $response->getBody(), true);
        $eMessage = "{$error['error']['message']} ({$error['error']['description']})";

        if(in_array($response->getStatusCode(), [500, 502, 504]))
        {
            return new ExchangeError($eMessage);
        }

        if($error['error']['code'] == 403 || $error['error']['code'] == 1003)
        {
            return new ActionIsForbidden($eMessage);
        }

        if($error['error']['code'] == 429)
        {
            return new TooManyRequests($eMessage);
        }

        if($error['error']['code'] == 1001  || $error['error']['code'] == 1001)
        {
            return new AuthorisationFail($eMessage);
        }

        if($error['error']['code'] == 2001)
        {
            return new PairNotFound($eMessage);
        }

        if($error['error']['code'] == 2002)
        {
            return new CurrencyNotFound($eMessage);
        }

        if($error['error']['code'] == 2010)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 2011)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 2012)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 2020)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 2021)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 2022)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 20001)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 20003)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['error']['code'] == 20002)
        {
            return new OrderNotFound('');
        }

        if($error['error']['code'] == 10001)
        {
            return new ValidationError($error['error']['message'].'. '.$error['error']['description']);
        }

        if($error['error']['code'] == 2001)
        {
            return new PairNotFound($eMessage);
        }

        return new UnknownError($eMessage);

    }

    /**
     * @return CurrencyBalance[]
     */
    public function getBalance()
    {
       $response = $this->request("GET", 'trading/balance', []);
        if($response->getStatusCode() == 200)
        {
            $data = json_decode((string)$response->getBody(), true);
            $result = [];

            foreach ($data as $item)
            {
                $balance = new CurrencyBalance();
                $balance->currency = $item['currency'];
                $balance->available  = (float) $item['available'];
                $balance->reserved = (float) $item['reserved'];

                $result[$item['currency']] = $balance;
            }

            return $result;
        }

        $ex = $this->handleErrorResponse($response);

        if($ex instanceof \Exception)
        {
            throw new $ex;
        }

    }

    /**
     * @return CurrencyBalance[]
     */
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
     * @throws \Exception
     */
    public function createOrder(Order &$order)
    {

        $response =  $this->request("POST", 'order', [
            'symbol' => $order->pairID,
            'side' => $order->side,
            'type' => $order->type,
            'timeInForce' => $order->timeInForce,
            'quantity' => $order->value,
            'price' => $order->price,
        ]);

        if($response->getStatusCode() == 200) {

            $data = json_decode((string)$response->getBody(), true);

            $order = new Order();
            $order->pairID = $data['symbol'];
            $order->eClientOrderID = $data['clientOrderId'];
            $order->eOrderID = $data['id'];
            $order->side = $data['side'];
            $order->value = (float)$data['quantity'];
            $order->price = (float)$data['price'] ?? null;
            $order->date = new \DateTime($data['createdAt']);
            $order->status = $data['status'];
            $order->traded = (float)$data['cumQuantity'];


            return $order;
        }

        $this->log("Unable to create order. ".(string)$response->getBody(),[], Logger::WARNING);
        $ex = $this->handleErrorResponse($response);

        throw $ex;

    }

    public function updateOrder(Order &$order)
    {

        $response =  $this->request("PATCH ", "order/{$order->eClientOrderID}", [
            'symbol' => $order->pairID,
            'side' => $order->side,
            'type' => $order->type,
            'timeInForce' => $order->timeInForce,
            'quantity' => $order->value,
            'price' => $order->price,
            //'requestClientId'=> $order->eClientOrderID,
        ]);

        if($response->getStatusCode() == 200) {

            $data = json_decode((string)$response->getBody(), true);

            $order = new Order();
            $order->pairID = $data['symbol'];
            $order->eClientOrderID = $data['clientOrderId'];
            $order->eOrderID = $data['id'];
            $order->side = $data['side'];
            $order->value = (float)$data['quantity'];
            $order->price = (float)$data['price'] ?? null;
            $order->date = new \DateTime($data['createdAt']);
            $order->status = $data['status'];
            $order->traded = (float)$data['cumQuantity'];


            return $order;
        }

        $ex = $this->handleErrorResponse($response);

        throw $ex;
    }

    /**
     * @param Order $order
     * @return Order
     * @throws \Exception
     */
    public function closeOrder(Order &$order)
    {
        $response = $this->request("DELETE", "order/{$order->eClientOrderID}", []);

        if($response->getStatusCode() == 200)
        {
            $item = json_decode((string) $response->getBody() ,true);

            $order = new Order();
            $order->pairID = $item['symbol'];
            $order->eClientOrderID = $item['clientOrderId'];
            $order->eOrderID = $item['id'];
            $order->side = $item['side'];
            $order->value = (float)$item['quantity'];
            $order->price = (float)$item['price'];
            $order->date =  new \DateTime($item['createdAt']);
            $order->status = 'canceled';
            $order->traded = (float)$item['cumQuantity'];

            return $order;
        }


        $ex =  $this->handleErrorResponse($response);

        if($ex instanceof OrderNotFound)
        {
            $ex->order = $order;
        }

        throw  $ex;

    }

    /**
     * @return Order[]
     * @throws \Exception
     */
    public function getActiveOrders($forse = false)
    {

        if($this->activeOrders !== null && $forse === false)
        {
            return $this->activeOrders;
        }

        $response = $this->request("GET", 'order', []);

        if($response->getStatusCode() == 200)
        {
            $data = json_decode((string)$response->getBody(), true);

            $result = [];
            foreach ($data as $item)
            {
                $order = new Order();
                $order->pairID = $item['symbol'];
                $order->eClientOrderID = $item['clientOrderId'];
                $order->eOrderID = $item['id'];
                $order->side = $item['side'];
                $order->value = (float) $item['quantity'];
                $order->price = (float) $item['price'];
                $order->date =  new \DateTime($item['createdAt']);
                $order->status = $item['status'];
                $order->traded = (float) $item['cumQuantity'];

                $result[$order->eClientOrderID] = $order;
            }

            $this->activeOrders = $result;

            return $result;
        }

        throw $this->handleErrorResponse($response);

    }

    /**
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function checkOrderIsActive(Order &$order, $forse = false)
    {
        $orders = $this->getActiveOrders($forse);

        if($result = (array_key_exists($order->eClientOrderID, $orders)))
        {
            $order = $orders[$order->eClientOrderID];
        }

        return $result;

    }

    public function getOrderStatus(Order &$order, $forse = false)
    {
        if($isActive = $this->checkOrderIsActive($order, $forse))
        {
            return $order->status;
        }

        if($this->isOrderCanceled($order))
        {
            $order->status = 'canceled';
            return $order->status;
        }

        $this->loadTrades($order->pairID);
        //Ордер либо полностью исполнен либо canceled;

        $this->getOrderTrades($order);

        if(abs((float)$order->traded - (float)$order->value) < pow(10, -9))
        {
            $order->status = 'filled';
        }
        else
        {
            $order->status = 'unknown';
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

            if($item->eOrderID == $order->eOrderID)
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

            $response = $this->request($method, $action, $params);

            if($response->getStatusCode() == 200)
            {
                $data = json_decode( (string)$response->getBody(), true);
                $dataCount = count($data);
                if($dataCount < 1) {
                    break;
                }

                foreach ($data as $item)
                {
                    $counter++;
                    $item = $itemConverter($item);
                    $r = $func($item, $counter, $dataCount);
                    if($r === false) return $counter;
                }

                $params['offset']  += $chunkSize;
            }
            else
            {
                throw $this->handleErrorResponse($response);
            }

        }

        return $counter;
    }

    public function chunkAccountTrades($pairID, callable $func, $sort="DESC", $chunkSize=100)
    {

        if(array_key_exists($pairID ,$this->trades) && is_array($this->trades[$pairID]))
        {
            foreach ($this->trades[$pairID] as $trade)
            {
                $r = $func($trade);
                if($r===false) return ;
            }

            return ;
        }

        $p =
            [
              'sort'=>$sort,
            ];

        if($pairID !== null)
        {
            $p['symbol'] = $pairID;
        }

        $pairs = $this->getPairs();


       return $this->chunker($func, 'GET', 'history/trades', $p, $chunkSize, function($item) use(&$pairs) {

           $trade = new Trade();
           $trade->date = new \DateTime($item['timestamp']);
           $trade->eTradeID = (string)$item['id'];
           $trade->eClientOrderID = (string)$item['clientOrderId'];
           $trade->eOrderID = (string)$item['orderId'];
           $trade->pairID = $item['symbol'];
           $trade->side = $item['side'];
           $trade->value = (float) $item['quantity'];
           $trade->fee = (float) $item['fee'];
           $trade->price = (float) $item['price'];
           $trade->feeCurrency = $pairs[$trade->pairID]->limit->feeCurrency;

           return $trade;

       });
    }

    /**
     * @param $pairID
     * @param int $limit
     * @param bool $forse
     * @return OrderBook
     * @throws ActionIsForbidden
     * @throws AuthorisationFail
     * @throws CurrencyNotFound
     * @throws ExchangeError
     * @throws OrderNotFound
     * @throws OrderRejected
     * @throws PairNotFound
     * @throws TooManyRequests
     * @throws UnknownError
     * @throws ValidationError
     */
    public function getOrderBook($pairID, $limit = 100, $forse = false)
    {
        if(array_key_exists($pairID, $this->orderBook) && !$forse)
        {
            return $this->orderBook[$pairID];
        }

        $response = $this->request('GET', "public/orderbook/$pairID", ['limit'=>$limit]);

        if(200 == $response->getStatusCode())
        {
            $data = json_decode( (string)$response->getBody(), true);

            $orderBook = new OrderBook();

            foreach ($data['ask'] as $item)
            {
                $i = new OrderBookItem();
                $i->price = (double)$item['price'];
                $i->size = (double)$item['size'];
                $orderBook->ask[] = $i;
            }
            unset($item);

            foreach ($data['bid'] as $item)
            {
                $i = new OrderBookItem();
                $i->price = (double)$item['price'];
                $i->size = (double)$item['size'];
                $orderBook->bid[] = $i;
            }

            return $this->orderBook[$pairID] = $orderBook;
        }
        else
        {
            $error = json_decode((string) $response->getBody(), true);
            if(array_key_exists('error', $error))
            {
                $eMessage = "{$error['error']['message']} ({$error['error']['description']})";

                if($error['error']['code'] == 2001)
                {
                    throw new PairNotFound("Pair $pairID not found" );
                }
            }
        }

        throw $this->handleErrorResponse($response);

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

        $jParams = json_encode($params);

        try{
            $response = $this->client->request($method, "https://api.hitbtc.com/api/2/" . $action, $dataToSend);
        }
        catch (ClientException $e)
        {
            $eResponse = $e->getResponse();
            $logMessage = "REQUEST {$method} {$action} with params $jParams";
            $logMessage .="\n\t RESPONSE status=".$eResponse->getStatusCode()." body=".(string)$eResponse->getBody();
            $this->log($logMessage, [], Logger::ERROR);

            return $eResponse;
        }
        catch (ServerException $e)
        {
            $eResponse = $e->getResponse();
            $logMessage = "REQUEST {$method} {$action} with params $jParams";
            $logMessage .="\n\t RESPONSE status=".$eResponse->getStatusCode()." body=".(string)$eResponse->getBody();
            $this->log($logMessage, [], Logger::ERROR);

            return $eResponse;
        }

        return $response;
    }

    public function getBuyPrice($pairID, $volume)
    {
        $result = 0;

        $ob = $this->getOrderBook($pairID);

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

    public function getSellPrice($pairID, $volume)
    {
        $result = 0;

        $ob = $this->getOrderBook($pairID);

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

    /**
     * @return Order[]
     * @throws \Exception
     */
    public function getOrdersHistory($forse = false, $params = [])
    {

        if(!$forse && is_array($this->historicalOrders))
        {
            return $this->historicalOrders;
        }

        $response = $this->request("GET", "history/order", $params);

        $result = [];
        if($response->getStatusCode() == 200)
        {
            $data = json_decode((string) $response->getBody() ,true);


            foreach($data as $item)
            {
                $order = new Order();
                $order->pairID = $item['symbol'];
                $order->eClientOrderID = $item['clientOrderId'];
                $order->eOrderID = $item['id'];
                $order->side = $item['side'];
                $order->value = (float)$item['quantity'];
                $order->price = (float)$item['price'];
                $order->date =  new \DateTime($item['createdAt']);
                $order->status = $item['status'];
                $order->traded = (float)$item['cumQuantity'];

                $result[$order->eOrderID] = $order;

            }

            return $this->historicalOrders = $result;
        }


        $ex =  $this->handleErrorResponse($response);
        throw  $ex;

    }

    public function isOrderCanceled( Order $order, $force = false )
    {
        $orders = $this->getOrdersHistory($force);

        if(!array_key_exists($order->eOrderID, $orders))
        {
            return false;
        }

        $order = $orders[$order->eOrderID];

        return ($order->status === 'canceled');
    }

    public function clearCache()
    {
        $this->trades = [];
        $this->historicalOrders = null;
        $this->activeOrders = null;
        $this->orderBook = [];
    }

}