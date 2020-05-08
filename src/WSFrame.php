<?php


namespace Crypto;


class WSFrame
{
    public int $fin;
    public int $rsv1;
    public int $rsv2;
    public int $rsv3;
    public int $opcode;
    public int $mask;
    public array $maskKey;
    public int $dataLength;
    public string $rawData;
    public int $offset;

    public function getData()
    {
        $result = '';
        if ($this->mask == 1) {
            for ($i = 0; $i < strlen($this->rawData); $i++) {
                $result  .= $this->rawData[$i] ^ $this->maskKey[$i % 4];
            }
            return $result;
        } else {
            return $this->rawData;
        }
    }

    public function init($str){
        $this->initHeaderInfo($str);
        $this->rawData = substr($str, $this->offset, $this->dataLength);
    }

    public function initHeaderInfo($str)
    {
        $firstByteBinary = sprintf('%08b', ord($str[0]));
        $secondByteBinary = sprintf('%08b', ord($str[1]));
        $flags = substr($firstByteBinary, 0,4);
        $fin = (bool)(int)$flags[0];
        $this->opcode = bindec(substr($firstByteBinary, 4, 4));
        if($this->opcode === 0x9)
        {
           // var_dump("REsived PING!!!!");
        }

        if($this->opcode === 0xA)
        {
            //var_dump("REsived PONG!!!!");
        }


        if($this->opcode === 0x8)
        {
            var_dump("Other side closing connection!");
            var_dump($this->rawData);
            throw new \Exception("Connection closed");
        }

        if($this->opcode == 0x1)
        {
            //var_dump("GOT TEXT FRAME");
            //var_dump($str);
        }

        if($this->opcode == 0x2)
        {
            //var_dump("GOT TEXT FRAME");
        }

        $this->mask = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($str[1]) & 127;

        if ($payloadLength === 126) {

            if($this->mask)
            {
                $this->maskKey = substr($str, 4, 4);
                $payloadOffset = 8;
            }
            else
            {
                $payloadOffset = 4;
            }

            $this->dataLength =  bindec(sprintf('%08b', ord($str[2])) . sprintf('%08b', ord($str[3])));
            $this->offset  = $payloadOffset;
        }
        elseif ($payloadLength === 127) {
            if($this->mask)
            {
                $this->maskKey = substr($str, 10, 4);
                $this->offset = 14;
            }
            else
            {
                $this->offset = 10;
            }

            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($str[$i + 2]));
            }
            $this->dataLength = bindec($tmp);
        }
        else {
            if($this->mask)
            {
                $this->maskKey = substr($str, 2, 4);
                $this->offset = 6;
            }
            else
            {
                $this->offset = 2;
            }

            $this->dataLength = $payloadLength;
        }
    }

    public function encode(){

        $headerBits  = "1000";
        $headerBits .= str_pad(bindec($this->opcode), 4, '0', STR_PAD_LEFT);
        $headerBits .= $this->mask;
        $len = strlen($this->rawData);
        if($len < 126){
            $headerBits .= str_pad(decbin($len), 7, '0', STR_PAD_LEFT);
        }
        elseif($len >= 126 && $len < pow(2, 16)){
            $headerBits .= str_pad(decbin(126), 7, '0', STR_PAD_LEFT);
            $headerBits .= str_pad(decbin($len), 16, '0', STR_PAD_LEFT);
        }
        elseif($len >= pow(2, 16)){
            $headerBits .= str_pad(decbin(127), 7, '0', STR_PAD_LEFT);
            $headerBits .= str_pad(decbin($len), 64, '0', STR_PAD_LEFT);
        }

        if($this->mask == 1){
            foreach ($this->maskKey as $byte){
                $headerBits .= str_pad(decbin($byte), 8, '0',STR_PAD_LEFT);
            }
        }

        $header = "";
        foreach (str_split($headerBits, 8) as $byte)
        {
            $header .= chr(bindec($byte));
        }

        $data = "";
        if( 1 == $this->mask){
            for ($i = 0; $i < strlen($this->rawData); $i++) {
                $data  .= chr(ord($this->rawData[$i]) ^ $this->maskKey[$i % 4]);
            }
        }else{
            $data = $this->rawData;
        }
        return $header.$data;
    }

    public function generateMask()
    {
        $this->maskKey= [];
        for($i = 0; $i < 4; $i++){
            $this->maskKey[$i] =  rand(0, (pow(2,8) -1));
        }
    }

}