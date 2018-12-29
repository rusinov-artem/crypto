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
        $eMessage = "{$error['message']} ({$error['description']})";

        if(in_array($response->getStatusCode(), [500, 502, 504]))
        {
            return new ExchangeError($eMessage);
        }

        if($error['code'] == 403 || $error['code'] == 1003)
        {
            return new ActionIsForbidden($eMessage);
        }

        if($error['code'] == 429)
        {
            return new TooManyRequests($eMessage);
        }

        if($error['code'] == 1001  || $error['code'] == 1001)
        {
            return new AuthorisationFail($eMessage);
        }

        if($error['code'] == 2001)
        {
            return new PairNotFound($eMessage);
        }

        if($error['code'] == 2002)
        {
            return new CurrencyNotFound($eMessage);
        }

        if($error['code'] == 2010)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 2011)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 2012)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 2020)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 2021)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 2022)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 20001)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 20003)
        {
            return new OrderRejected(new Order(), $eMessage);
        }

        if($error['code'] == 20002)
        {
            return new OrderNotFound(new Order());
        }

        if($error['code'] == 10001)
        {
            return new ValidationError(new Order());
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
            'type' => 'limit',
            'timeInForce' => 'GTC',
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
            $order->price = (float)$data['price'];
            $order->date = new \DateTime($data['createdAt']);
            $order->status = $data['status'];
            $order->traded = (float)$data['cumQuantity'];


            return $order;
        }

        $ex = $this->handleErrorResponse($response);

        if($ex instanceof OrderRejected)
        {
            $ex->order = $order;
            throw new $ex;
        }

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
    public function getActiveOrders()
    {
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
            return $result;
        }

        throw $this->handleErrorResponse($response);

    }

    /**
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function checkOrderIsActive(Order &$order)
    {
        $orders = $this->getActiveOrders();

        if($result = (array_key_exists($order->eClientOrderID, $orders)))
        {
            $order = $orders[$order->eClientOrderID];
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

        if(abs((float)$order->traded - (float)$order->value) < pow(10, -9))
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

            if($item->eOrderID === $order->eOrderID)
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
            else
            {
                throw $this->handleErrorResponse($response);
            }

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

        $pairs = $this->getPairs();


       return $this->chunker($func, 'GET', 'history/trades', $p, $chunkSize, function($item) use(&$pairs) {

           $trade = new Trade();
           $trade->date = new \DateTime($item['timestamp']);
           $trade->eTradeID = $item['id'];
           $trade->eClientOrderID = $item['clientOrderId'];
           $trade->eOrderID = $item['orderId'];
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
     * @return OrderBook
     * @throws OrderNotFound
     * @throws OrderRejected
     * @throws UnknownError
     * @throws ValidationError
     */
    public function getOrderBook($pairID, $limit = 100)
    {
        $response = $this->request('GET', "public/orderbook/$pairID", ['limit'=>$limit]);


        if(200 == $response->getStatusCode())
        {
            $data = json_decode( (string)$response->getBody(), true);

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

}