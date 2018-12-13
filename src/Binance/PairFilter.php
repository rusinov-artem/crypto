<?php


namespace Crypto\Binance;


class PairFilter
{
    /**
     * @var array
     */
    private $data;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getFilter($name)
    {
        foreach ($this->data as $item)
        {
            if($item['filterType'] === $name)
            {
                return $item;
            }
        }

        return false;
    }
}