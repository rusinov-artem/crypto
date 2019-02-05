<?php


namespace Crypto\Bot;


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

    private function renew()
    {

        $this->inOrder->status = null;
        $this->outOrder->status = null;
    }
}