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

        $firstByteBinary = sprintf('%08b', ord($str[0]));
        $secondByteBinary = sprintf('%08b', ord($str[1]));
        $this->opcode = bindec(substr($firstByteBinary, 4, 4));
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
        } elseif ($payloadLength === 127) {
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
        } else {
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