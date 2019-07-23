<?php


namespace Crypto;

/**
 * Получает события от биржи
 * Class WSWorker
 * @package Crypto
 */
class WSWorker
{
    public $apiKeys; //Список ключей, по которым будем слушать
    public $apiKeyRepo; //Репозиторий из которого забирать ключи

    public $exchangeListener; //Объект, который отвечает за получение событий от биржы
    public $commandListener; //Объект, который отвечает за прием комманд
    public $eventSender; //Объект который посылает пришедшие события

    public function __construct($port, $apiKeysRepo, $commandListener, $exchangeListener, $eventSender)
    {
        //1. начать слушать входящие сообщения
        //2.
    }

    public function listen()
    {
        $streams = array_merge($this->commandListener->getStream());
        $read = $streams;

        $null = [];

        while(true)
        {
            $r = stream_select($read, $null, $null, 5);

            $this->commandListener->handle($this);
            $this->exchangeListener->handle($this, $read);

        }

    }

    public function addAccounts($accounts) {
        $this->initAccount();
    }
    
    public function removeAccounts($accounts){}

    public function getAccounts(){}

    public function sendEvent($event){

        $this->eventSender->send($event);

    }

    public function createOrder($order, $account){}

    public function cancelOrder($order, $account){}

}

class BotManager
{
    public $communicator; //Штука, которая умеет посылать запросы воркерам
    public $botRepo; //Репозиторий ботов

    public function handle($event)
    {
        //Найти бота, обрабатывающего $event
        $bot = $this->botRepo->findBot($event);
        $bot->handle($event, $this->communicator);
        $this->checkOutOrders();//Снять\поставить дополнительные ордера
    }

    public function createOrder($order)
    {
        $this->communicator->createOrder($order);
    }

    public function cancelOrder($order)
    {
        $this->communicator->cancelOrder($order);
    }
}