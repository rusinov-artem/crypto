<?php
$k = json_decode('{"error":{"code":-32000,"message":"Bridge is too busy, RequestsCounter = 2"},"id":1,"jsonrpc":"2.0"}', true);
var_dump($k);