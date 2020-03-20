<?php
require __DIR__."/../vendor/autoload.php";

$bin = new \Crypto\Binance\Client();
$config = include __DIR__ . "/../config.php";

$bin->apiKey = $config['binance.api.key'];
$bin->secretKey = $config['binance.api.secret'];


m1:
if(!($p??false))
{
    $bin->removeListenKey("5Y6t2sljjfLQvRDD6SPx32eGzrCxPJ5xSYCp9E1kJ2eYmnvWV8X39Of2cs29");
}
else
{
    $bin->removeListenKey($p);
}



    $p = $bin->getListenKey();var_dump($p);
    $r = $bin->pingListenKey($p);
    if(!empty($r))
    {
        var_dump($r);die();
    }
    file_put_contents("binance.log", "$p\n\n", FILE_APPEND);

    var_dump((new \DateTime())->format("Y-m-d H:i:s"));

    $client = new \Crypto\WSFrameClient('stream.binance.com', 9443, "/ws/$p");
    //$r = $client->send('');


stream_set_timeout($client->socket, 1);
stream_set_blocking($client->socket, false);
$i=0;
while(1) {
        $i++;

        try{
            usleep(500000);
            $frame = $client->getFrame();
        }
        catch (\Exception $e)
        {
            goto m1;
        }

        if($frame)
        {
            var_dump($msg = $frame->getData());
            if(!empty($msg) && $frame->opcode !==9)
            {
                $dt = (new \DateTime())->format("Y-m-d H:i:s");
                file_put_contents("binance.log", "$dt\n$msg\n\n", FILE_APPEND);
            }
        }



        if($i % 30 == 0)
        {
            //$client->ping();
        }

}

