<?php


set_error_handler(function($errno, $srrstr, $errfile, $errline, $errcontext){
   throw  new \Exception('LOL');
});

set_exception_handler(function(){
 var_dump('Exception handler worked out');
});

try{
    unlink("randomfile.txt");
}
catch (\Throwable $t)
{
    var_dump("handled");
}
