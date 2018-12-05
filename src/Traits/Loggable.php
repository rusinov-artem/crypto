<?php


namespace Crypto\Traits;


use Psr\Log\LoggerInterface;

trait Loggable
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    public function log($message, array $context = [], $level=100)
    {
        if($this->logger)
        {
            $this->logger->log($level, $message, $context);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}