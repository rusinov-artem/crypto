<?php


namespace Crypto;


class WSFrame
{
    public $fin;
    public $rsv1;
    public $rsv2;
    public $rsv3;
    public $opcode;
    public $mask;
    public $maskKey;
    public $dataLength;
    public $rawData;
    public $offset;
    public $isReady;

    public function setRawData($data)
    {
        $this->rawData = $data;
    }

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

    public function initHeaderInfo($str)
    {
        // x3,x2,x1,0
        //x0 * 2^0 + x1 * 2^1 + x2 *2^2 + x3 * 2^3
        // 1) разделить на 2, остаток это x0, результат от деления
        // 2)

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
        $r = decbin(ord($str[1]));
        $r1 = decbin(127);
        $payloadLength = ord($str[1]) & 127;

        $r3 = decbin(ord($str[1]) & 127);
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

}