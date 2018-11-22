<?php


namespace Crypto\Tests\Exchange;

// Биржа для тестирования поведения ботов
use Crypto\Exchange\CurrencyBalance;
use Crypto\Exchange\Exceptions\OrderNotFound;
use Crypto\Exchange\Exceptions\PairNotFound;
use Crypto\Exchange\Order;
use Crypto\Exchange\Pair;
use Crypto\Exchange\Trade;

class ExchangeStub
{

    /**
     * @var Order[]
     */
    public $orders;

    /**
     * @var Trade[]
     */
    public $trades = [];

    /**
     * @var CurrencyBalance[]
     */
    public $balances;

    public $pairs;

    public $orderBooks;

    public function &getOrder($id)
    {
        if(array_key_exists($id, $this->orders))
            return $this->orders[$id];

        throw new OrderNotFound($id);
    }

    /**
     * @param $id
     * @return Pair
     * @throws PairNotFound
     */
    public function &getPair($id)
    {
        if(array_key_exists($id, $this->pairs))
            return $this->pairs[$id];

        throw new PairNotFound($id);
    }

    public function fillOrder($id, $value)
    {


        $order = $this->getOrder($id);

        $orderDif = $order->value - $order->traded;

        if($value >= $orderDif)
        {
            $value = $orderDif;
            $order->status = "filled";
        }
        else
        {
            $order->status = "partiallyFilled";
        }

        $order->traded += $value;

        $trade = new Trade();
        $trade->pairID = $order->pairID;
        $trade->value = $value;
        $trade->orderID = $order->id;
        $trade->id = random_bytes(10);
        $trade->date = new \DateTime();
        $trade->side = $order->side;
        $trade->price = $order->price;

        $pair = $this->getPair($order->pairID);
        if($order->side === 'sell')
        {
            $trade->fee = $pair->limit->provideLiquidityRate * $value;
        }
        elseif($order->side === 'buy')
        {
            $trade->fee = $pair->limit->takeLiquidityRate * $value;
        }

        if($pair->baseCurrency === $pair->limit->feeCurrency)
        {
            $trade->fee *= $trade->price;
        }

        $trade->feeCurrency = $pair->limit->feeCurrency;

        $this->trades[$trade->id] = $trade;

        if($order->side === 'sell')
        {
            $this->balances[$pair->quoteCurrency]->reserved -= $value;
            $this->balances[$pair->baseCurrency]->available += $value * $trade->price;
        }
        elseif ($order->side === 'buy')
        {
            $this->balances[$pair->baseCurrency]->reserved -= $value * $trade->price;
            $this->balances[$pair->quoteCurrency]->available += $value;
        }

        $this->balances[$trade->feeCurrency]->reserved -= $trade->fee;

    }


}