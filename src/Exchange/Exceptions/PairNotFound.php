<?php


namespace Crypto\Exchange\Exceptions;


use Throwable;

class PairNotFound extends \Exception
{
    public function __construct($pairID)
    {
        parent::__construct("Pair $pairID not found", 2);
    }
}