<?php


namespace Crypto\Exchange;

/**
 * Class Pair
 * @property PairLimit $limit
 * @package Crypto\Exchange
 */
class Pair
{
    public $id;
    public $baseCurrency;
    public $quoteCurrency;

    /**
     * @var PairLimit
     */
    protected $limit;

    protected function getLimit()
    {
        return $this->limit;
    }

    protected function setLimit(PairLimit $limit)
    {
        $this->limit = $limit;
    }

    public function __get($name)
    {
        if($name === 'limit')
            return $this->getLimit();

        return null;
    }

    public function __isset($name)
    {
        if($name === 'limit')
            return true;

        return false;
    }

    public function __set($name, $value)
    {
        if($name === 'limit')
        {
            $this->limit =$value;
        }
    }
}