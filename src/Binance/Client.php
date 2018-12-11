<?php


namespace Crypto\Binance;


use Crypto\Exchange\ClientInterface;
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Order;
use Crypto\Exchange\Pair;

class Client implements ClientInterface
{

    public $apiKey;
    public $secretKey;

    /**
     * @var \GuzzleHttp\Client
     */
    public $client;

    /**
     * @return Pair[]
     */
    public function getPairs()
    {
       $response = $this->request("GET", "exchangeInfo", []);
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


        $response = $this->client->request($method, "https://api.binance.com/api/v1/" . $action, $dataToSend);

        return $response;
    }
}