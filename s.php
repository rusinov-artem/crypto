<?php

$s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$r = socket_bind($s, "192.168.116.1", 9000);
$r = socket_listen($s);
//socket_set_timeout($s, 90);
//socket_set_blocking($s, 1);
$r = socket_accept($s);

while($data = socket_read($r, 10000))
{
    file_put_contents("9000.log", $data, FILE_APPEND);
}

var_dump($r);
socket_shutdown($s);
socket_close($s);
