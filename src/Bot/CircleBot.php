<?php


namespace Crypto\Bot;


class CircleBot extends BotNext
{
    public $circles = 3;
    public $currentCircle = 0;


    public function checkOutOrder()
    {
        $this->log('Checking out order');
        $status = $this->client->getOrderStatus($this->outOrder);

        if('filled' === $status)
        {
            $this->currentCircle++;
            $this->log("Circle #{$this->currentCircle} passed");

            if($this->currentCircle <= $this->circles)
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