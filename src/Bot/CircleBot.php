<?php


namespace Crypto\Bot;


use Crypto\Exchange\Order;

class CircleBot extends BotNext
{
    public $circles = 3;
    public $currentCircle = 0;
    public $profit = 0;


    public function checkOutOrder()
    {
        $this->log('Checking out order');

        if($this->outOrder->eOrderID === null && $this->outOrder->eClientOrderID === null)
        {
            $this->client->createOrder($this->outOrder);
        }

        $status = $this->client->getOrderStatus($this->outOrder);

        if('filled' === $status)
        {
            $this->currentCircle++;
            $this->log("Circle #{$this->currentCircle} passed");

            $this->profit += $this->calculateProfit();
            $this->log("PROFIT {$this->profit}");

            if(($this->currentCircle < $this->circles) || $this->circles <=0 )
            {
                $this->renew();
                $this->log("Bot renewed");
            }
        }

    }

    public function renew()
    {
        $this->renewOrder($this->inOrder);
        $this->renewOrder($this->outOrder);
        $this->finished = false;
    }

    public function renewOrder(Order $order)
    {
        $order->status = null;
        $order->eClientOrderID = null;
        $order->eOrderID = null;
        return $this;
    }

}