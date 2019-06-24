<?php

require_once __DIR__."/../vendor/autoload.php";

$m = new Mustache_Engine();
$str = $m->render("{{var}}", ['var'=>'first var']);
var_dump($str);
$b58 = new Tuupola\Base58\PhpEncoder();
$d = $b58->encode("803133293B7827ED422EA95FF7E6B92145FAA6A22DE1896043F457306AF4CF5B4258DAE61C");
var_dump($d);
