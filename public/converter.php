<?php
var_dump(chr(138));
var_dump(decbin(138));
var_dump(bindec("00001010"));

$frame = chr(bindec("00001010")) . chr(0);
var_dump($frame);
var_dump(str_pad(decbin(ord($frame[0])), 8, "0", STR_PAD_LEFT));
var_dump(str_pad(decbin(ord($frame[1])), 8, "0", STR_PAD_LEFT));

