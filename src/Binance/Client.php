<?php


namespace Crypto\Binance;


use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Exceptions\OrderNotFound;
use Crypto\Exchange\Exceptions\OrderRejected;
use Crypto\Exchange\Exceptions\PairNotFound;
use Crypto\Exchange\Exceptions\UnknownError;
use Crypto\Exchange\Order;
use Crypto\Exchange\PairLimit;
use Crypto\Traits\Loggable;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Monolog\Logger;
use mysql_xdevapi\Exception;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Simple\FilesystemCache;

class Client implements ClientInterface
{

    use Loggable;

    public static $proxyList;
    public $apiKey;
    public $secretKey;

    /**
     * @var \GuzzleHttp\Client
     */
    public $client;

    /**
     * @var CacheInterface
     */
    public $cache;

    public function __construct()
    {

        $container = [];
        $history = Middleware::history($container);

        $handlerStack = HandlerStack::create();
        //$handlerStack->setHandler(new StreamHandler());
        // or $handlerStack = HandlerStack::create($mock); if using the Mock handler.

        // Add the history middleware to the handler stack.
        $handlerStack->push($history);

        $this->client = new \GuzzleHttp\Client(['handler' => $handlerStack]);
        $this->cache = new FilesystemAdapter("binance", 0, __DIR__."/../../storage/cache");
    }

    public function getAveragePrice($pairID)
    {

        if($this->cache)
        {
            $key = "binance.$pairID";
            if($this->cache->has($key))
            {
                return $this->cache->get($key);
            }
        }

        $response = $this->publicRequest("v3", "GET", "avgPrice", ['symbol'=>$pairID]);

        if($response->getStatusCode()==200)
        {
            $data = json_decode((string)$response->getBody(), true);
            $price = (float)$data['price'];
            if($this->cache)
            {
                $this->cache->set($key, $price, 10);
            }

            return $price;
        }
    }

    /**
     * @return Pair[]
     */
    public function getPairs()
    {
       $response = $this->publicRequest('v1', "GET",  "exchangeInfo", []);

       if($response->getStatusCode() == 200)
       {
         $data = json_decode((string)$response->getBody(), true);

         $result = [];

         foreach ($data['symbols'] as $symbol)
         {
            $pair = new \Crypto\Binance\Pair();
            $pair->setClient($this);
            $pair->id = $symbol['symbol'];
            $pair->baseCurrency = $symbol['baseAsset'];
            $pair->quoteCurrency = $symbol['quoteAsset'];

            $limit = new PairLimit();
            $limit->pairID = $pair->id;

            $limit->data = $symbol['filters'];

            $filter = new PairFilter($symbol['filters']);

            if($filterItem = $filter->getFilter("PRICE_FILTER"))
            {
                $limit->priceTick = (float)$filterItem['tickSize'];
            }

            if($filterItem = $filter->getFilter("LOT_SIZE"))
            {
                $limit->lotSize = (float) $filterItem['minQty'];
                $limit->qtyTick = (float)$filterItem['stepSize'];
            }

            if($filterItem = $filter->getFilter("MIN_NOTIONAL"))
            {
                $limit->minNotional = (float)$filterItem['minNotional'];
            }


            $pair->limit = $limit;
            $result[$pair->id] = $pair;
         }

            return $result;
       }


    }

    /**
     * @return CurrencyBalance[]
     */
    public function getBalance()
    {

        $response = $this->request("v3", 'GET', "account", []);

        if($response->getStatusCode() === 200)
        {
            $data = json_decode((string)$response->getBody(), true);

            $result = [];

            foreach ($data['balances'] as $bData)
            {
                $balance = new CurrencyBalance();
                $balance->currency = $bData['asset'];
                $balance->available = $bData['free'];
                $balance->reserved = $bData['locked'];
                $result[$balance->currency] = $balance;
            }

            return $result;

        }

        var_dump($a = (string)$response->getBody());
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
     * @throws \Exception
     */
    public function createOrder(Order &$order)
    {
        $params =
            [
              'symbol' =>$order->pairID,
              'side' => strtoupper($order->side),
              'type' => strtoupper($order->type),
              'timeInForce' => "GTC",
              'quantity' => $order->value,
              'price' => $order->price,
            ];

        if($params['type'] == 'MARKET')
        {
            unset($params['timeInForce']);
        }

        if($order->id)
        {
            $params['newClientOrderId'] = $order->id;
        }

        $response = $this->request("v3", "POST", "order", $params );

        if($response->getStatusCode() === 200)
        {
            $data = json_decode((string)$response->getBody(), true);

            $order->pairID = $data['symbol'];
            $order->id = $data['clientOrderId'];
            $order->date = (new \DateTime())->setTimestamp($data['transactTime'] / pow(10, 3));
            $order->price = (float)$data['price'];
            $order->value = (float)$data['origQty'];
            $order->traded = (float)$data['executedQty'];
            $order->status = $this->convertOrderStatus($data['status']);
            $order->type = strtolower($data['type']);
            $order->side = strtolower($data['side']);

            return $order;

        }

        $data = json_decode((string)$response->getBody(), true);

        if($response->getStatusCode() == 400)
        {
            if($data['code'] == -1013)
            {
                throw new OrderRejected($order, "Invalid price. {$data['msg']}");
            }

            if($data['code'] == -1121 )
            {
                throw new PairNotFound("$order->pairID. {$data['msg']}");
            }

            if($data['code'] == -2010)
            {
                $pair = $this->getPairs()[$order->pairID];
                $currency = $pair->quoteCurrency;
                throw new OrderRejected($order, "Not enough $currency. {$data['msg']}");
            }

        }

        throw new OrderRejected($order, "{$data['msg']}");
    }

    public function convertOrderStatus($status)
    {
        // new, suspended, partiallyFilled, filled, canceled, expired
        $map =
            [
               "NEW"=>'new',
               "PARTIALLY_FILLED" => 'partiallyFilled',
               "FILLED"=>'filled',
               "CANCELED" => 'canceled',
               "EXPIRED"=>"expired"
            ];

        if(!array_key_exists($status, $map))
        {
            return null;
        }

        return $map[$status];

    }

    public function getMinimalValue($pairID, $price)
    {
        $pairs = $this->getPairs();

        if(!array_key_exists($pairID, $pairs))
        {
            throw new PairNotFound("$pairID not found");
        }

        $pair = $pairs[$pairID];

        var_dump($pair->limit);

        return $pair->limit->minNotional / $price;
    }
    /**
     * @param Order $order
     * @return Order
     */
    public function closeOrder(Order &$order)
    {
        $params =
            [
                'symbol' =>$order->pairID,
                'origClientOrderId' => $order->id,
                'newClientOrderId' => $order->id,
            ];


        $response = $this->request("v3", "DELETE", "order", $params );

        if($response->getStatusCode() === 200)
        {
            $data = json_decode((string)$response->getBody(), true);

            $order->pairID = $data['symbol'];
            $order->id = $data['clientOrderId'];
            $order->price = (float)$data['price'];
            $order->value = (float)$data['origQty'];
            $order->traded = (float)$data['executedQty'];
            $order->status = $this->convertOrderStatus($data['status']);
            $order->type = strtolower($data['type']);
            $order->side = strtolower($data['side']);

            return $order;

        }

        throw new OrderNotFound($order->id);

    }

    /**
     * @return Order[]
     */
    public function getActiveOrders()
    {
        //GET /api/v3/openOrders

        $response = $this->request("v3", "GET", "openOrders",[]);

        $data = json_decode((string)$response->getBody(), true);

        if($response->getStatusCode()==200)
        {
            $result = [];

            foreach ($data as $orderData)
            {
                $order = new Order();
                $order->pairID = $orderData['symbol'];
                $order->id = $orderData['clientOrderId'];
                $order->price = (float)$orderData['price'];
                $order->value = (float)$orderData['origQty'];
                $order->traded = (float)$orderData['executedQty'];
                $order->status = $this->convertOrderStatus($orderData['status']);
                $order->type = strtolower($orderData['type']);
                $order->side = strtolower($orderData['side']);
                $order->date = (new \DateTime())->setTimestamp($orderData['time'] / pow(10, 3));

                $result[$order->id] = $order;
            }

            return $result;
        }

        throw new UnknownError($data['msg'], $data['code']);


    }


    public function getAllOrders($pair, $orderId=null, int $startTime=null, int $endTime=null, $limit=1000)
    {
        //GET /api/v3/allOrders

        $response = $this->request("v3", "GET", "allOrders",[
            'symbol'=>$pair,
            'orderId'=>$orderId,
            'startTime'=>$startTime,
            'endTime'=>$endTime,
            'limit'=>$limit
        ]);

        $data = json_decode((string)$response->getBody(), true);

        if($response->getStatusCode()==200)
        {
            $result = [];

            foreach ($data as $orderData)
            {
                $order = new Order();
                $order->pairID = $orderData['symbol'];
                $order->eOrderID = $orderData['orderId'];
                $order->eClientOrderID = $orderData['clientOrderId'];
                $order->price = (float)$orderData['price'];
                $order->value = (float)$orderData['origQty'];
                $order->traded = (float)$orderData['executedQty'];
                $order->status = $this->convertOrderStatus($orderData['status']);
                $order->type = strtolower($orderData['type']);
                $order->side = strtolower($orderData['side']);
                $order->date = (new \DateTime())->setTimestamp($orderData['time'] / pow(10, 3));
                $order->updatedAt = (new \DateTime())->setTimestamp($orderData['updateTime'] / pow(10, 3));

                $result[$order->eOrderID] = $order;
            }

            return $result;
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function checkOrderIsActive(Order &$order)
    {
        // TODO: Implement checkOrderIsActive() method.
    }

    public function getOrderStatus(Order &$order)
    {
        // TODO: Implement getOrderStatus() method.
    }

    public function getOrderTrades(Order &$order)
    {
        // TODO: Implement getOrderTrades() method.
    }

    public function chunkAccountTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100)
    {
        // TODO: Implement chunkAccountTrades() method.
    }

    public function getOrderBook($pairID, $limit = 100)
    {
        // TODO: Implement getOrderBook() method.
    }

    public function getPairTrades($pairID, callable $func, $sort = "DESC", $chunkSize = 100)
    {
        // TODO: Implement getPairTrades() method.
    }


    public function getListenKey()
    {
        $response = $this->request("v1", "POST", "userDataStream",[]);

        $data = json_decode((string)$response->getBody(), true);

        if($response->getStatusCode()==200)
        {

            var_dump($data);
            return $data['listenKey'];
        }

    }

    public function removeListenKey($key)
    {
        $response = $this->request("v1", "DELETE", "userDataStream",['listenKey'=>$key]);

        $data = json_decode((string)$response->getBody(), true);

        if($response->getStatusCode()==200)
        {

            var_dump($data);
        }

    }

    public function pingListenKey($key)
    {
        $response = $this->request("v1", "PUT", "userDataStream",['listenKey'=>$key]);
        $data = json_decode((string)$response->getBody(), true);
        return $data;

    }

    private function getProxy()
    {
        if(!static::$proxyList){
            $file = __DIR__ . "/../../public/proxy.data";
            static::$proxyList = $data = unserialize(file_get_contents($file));
        }

        $proxy = array_shift(static::$proxyList);
        array_push(static::$proxyList, $proxy);
        $proxy = $proxy;
        $p = parse_url($proxy);
        return $proxy;
    }

    public function request( $version, $method,  $action, array $params)
    {

        $dataToSend = [];
        $bparams = [];

        foreach ($params as $k=>$v)
        {
            $bparams[$k]=$v;
        }

        $bparams['timestamp'] = number_format(microtime(true) * 1000 - 4000 , 0, '.', '');

        $query = http_build_query($bparams, '', '&');
        $signature = hash_hmac('sha256', $query, $this->secretKey);
        $bparams['signature'] = $signature;

        $dataToSend['proxy'] = $this->getProxy();
        $dataToSend['verify'] = false;


        $dataToSend['headers'] =
            [
              "X-MBX-APIKEY" => $this->apiKey,
            ];

        if(strtolower($method) !== "get")
        {
            if(!empty($params))
              $dataToSend['form_params'] = $bparams;

            if($version === 'v1')
            {
                $dataToSend['form_params'] = $params;
            }
        }
        else
        {
            $dataToSend['query'] = $bparams;
        }


        try {
            $jParams = json_encode($params);
            $response = $this->client->request($method, "https://api.binance.com/api/$version/" . $action, $dataToSend);
        } catch (ClientException $e) {
            $eResponse = $e->getResponse();
            $logMessage = "REQUEST {$method} {$action} with params $jParams";
            $logMessage .= "\n\t RESPONSE status=" . $eResponse->getStatusCode() . " body=" . (string)$eResponse->getBody();
            $this->log($logMessage, [], Logger::ERROR);

            return $eResponse;

        } catch (ServerException $e) {
            $eResponse = $e->getResponse();
            $logMessage = "REQUEST {$method} {$action} with params $jParams";
            $logMessage .= "\n\t RESPONSE status=" . $eResponse->getStatusCode() . " body=" . (string)$eResponse->getBody();
            $this->log($logMessage, [], Logger::ERROR);

            return $eResponse;
        }

        return $response;
    }

    public function publicRequest( $version, $method,  $action, array $params)
    {
        $dataToSend = [];

        $dataToSend['headers'] =
            [
                "X-MBX-APIKEY" => $this->apiKey,
            ];

        if(strtolower($method) !== "get")
        {
            $dataToSend['form_params'] = $params;
        }
        else
        {
            $dataToSend['query'] = $params;
        }


        try {
            $jParams = json_encode($params);
            $response = $this->client->request($method, "https://api.binance.com/api/$version/" . $action, $dataToSend);
        } catch (ClientException $e) {
            $eResponse = $e->getResponse();
            $logMessage = "REQUEST {$method} {$action} with params $jParams";
            $logMessage .= "\n\t RESPONSE status=" . $eResponse->getStatusCode() . " body=" . (string)$eResponse->getBody();
            $this->log($logMessage, [], Logger::ERROR);

            return $eResponse;

        } catch (ServerException $e) {
            $eResponse = $e->getResponse();
            $logMessage = "REQUEST {$method} {$action} with params $jParams";
            $logMessage .= "\n\t RESPONSE status=" . $eResponse->getStatusCode() . " body=" . (string)$eResponse->getBody();
            $this->log($logMessage, [], Logger::ERROR);

            return $eResponse;
        }

        return $response;
    }
}