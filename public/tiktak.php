<?php

include __DIR__."/../vendor/autoload.php";
ini_set('display_startup_errors',1);
ini_set('display_errors', 1);


$eventBase = new EventBase();
$te = Event::timer($eventBase, function ($arg)use(&$te){
    var_dump((new \DateTime())->format("Y-m-d H:i:s"));
    $te->add(1);
}, null);
$te->addTimer(1);

$eventBase->dispatch();