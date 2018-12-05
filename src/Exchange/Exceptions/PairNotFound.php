<?php


namespace Crypto\Exchange\Exceptions;


use Throwable;

class PairNotFound extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}