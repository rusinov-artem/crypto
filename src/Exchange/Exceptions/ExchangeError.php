<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 12/6/2018
 * Time: 1:47 AM
 */

namespace Crypto\Exchange\Exceptions;


use Throwable;

class ExchangeError extends \Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}