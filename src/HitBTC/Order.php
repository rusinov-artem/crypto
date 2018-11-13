<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/12/2018
 * Time: 7:26 PM
 */

namespace Crypto\HitBTC;


class Order
{
    public $id;
    public $date;
    public $price;
    public $value;
    public $pairID;
    public $side;
    public $traded = 0;
    public $status; // new, suspended, partiallyFilled, filled, canceled, expired

    public function init(array $data)
    {
        $this->id = $data['clientOrderId'];
        $this->pairID = $data['symbol'];
        $this->side = $data['side'];
        $this->value = $data['quantity'];
        $this->price = $data['price'];
        $this->status = $data['status'];
        $this->date =  new \DateTime($data['createdAt']);
        $this->traded = $data['cumQuantity'];
        return $this;
    }
}