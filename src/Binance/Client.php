<?php


namespace Crypto\Binance;


use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Order;
use Crypto\Exchange\Pair;
use Crypto\Exchange\PairLimit;
use Crypto\Traits\Loggable;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Monolog\Logger;

class Client implements ClientInterface
{

    use Loggable;

    public $apiKey;
    public $secretKey;

    /**
     * @var \GuzzleHttp\Client
     */
    public $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * @return Pair[]
     */
    public function getPairs()
    {
       $response = $this->request("GET", "exchangeInfo", []);

       if($response->getStatusCode() == 200)
       {
         $data = json_decode((string)$response->getBody(), true);

         $result = [];

         foreach ($data['symbols'] as $symbol)
         {
            $pair = new Pair();
            $pair->id = $symbol['symbol'];
            $pair->baseCurrency = $symbol['baseAsset'];
            $pair->quoteCurrency = $symbol['quoteAsset'];

            $limit = new PairLimit();
            $limit->pairID = $pair->id;

            foreach ($symbol['filters'] as $filter)
            {
                if($filter['filterType'] === 'PRICE_FILTER')
                {
                    $limit->priceTick = $filter['tickSize'];
                }

                if($filter['filterType'] === 'LOT_SIZE')
                {
                    $limit->lotSize = $filter['stepSize'];
                    if($filter['stepSize'] !== $filter['minQty'])
                    {
                        var_dump($symbol);
                    }
                }
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
        // TODO: Implement getBalance() method.
    }

    public function getNonZeroBalance()
    {
        // TODO: Implement getNonZeroBalance() method.
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function createOrder(Order &$order)
    {
        // TODO: Implement createOrder() method.
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function closeOrder(Order &$order)
    {
        // TODO: Implement closeOrder() method.
    }

    /**
     * @return Order[]
     */
    public function getActiveOrders()
    {
        // TODO: Implement getActiveOrders() method.
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

    public function request($method, $action, array $params)
    {

        $dataToSend = [];
        $params['timestamp'] = number_format(microtime(true) * 1000, 0, '.', '');
        $query = http_build_query($params, '', '&');
        $signature = hash_hmac('sha256', $query, $this->secretKey);
        $params['signature'] = $signature;

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
            $response = $this->client->request($method, "https://api.binance.com/api/v3/" . $action, $dataToSend);
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