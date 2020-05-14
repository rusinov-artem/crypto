<?php
if(!isset($argv[1])){
    fwrite(STDERR,  "socket file name is required\n");
    fwrite(STDERR, "simple example:\n php sock.php xdebug.sock \n");
    return 1;
}

$file = $argv[1];
if(file_exists($file)){
    fwrite(STDERR, "file $file already exists\n");
    return 1;
}


$s = socket_create(AF_UNIX, SOCK_STREAM, 0);
umask(0);
$r = socket_bind($s, $argv[1]);

if(!$r){
    fwrite(STDERR, "unable ot bind unix socket to file $file \n");
    $err = error_get_last();
    if(!empty($err)){
        fwrite(STDERR, "{$err['message']} {$err['file']}:{$err['line']}");
    }
    return 1;
}
socket_shutdown($s, 2);
socket_close($s);

return 0;