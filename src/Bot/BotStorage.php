<?php
/**
 * Created by PhpStorm.
 * User: RusinovArtem
 * Date: 11/12/2018
 * Time: 6:40 PM
 */

namespace Crypto\Bot;

use Symfony\Component\Finder\Finder;

class BotStorage
{
    public $directory = __DIR__."/../../storage/bots";



    public function __construct()
    {


    }

    public function getAll()
    {
        $result = [];
        $finder = new Finder();
        $finder->files()->in($this->directory);
        foreach ($finder as $file)
        {
            $result[] = substr($file->getFilename(), 0, strlen($file->getFilename())-4);
        }
        return $result;
    }

    public function getBot($id)
    {
        $botStr=file_get_contents($this->directory."/".$id.".bot");
        $bot = unserialize($botStr);
        return $bot;
    }

    public function saveBot($bot)
    {
        $botStr = serialize($bot);
        return file_put_contents($this->directory."/".$bot->id.".bot", $botStr);
    }

    public function deleteBot( $bot)
    {
        if($bot instanceof BotNext)
        {
            $id = $bot->id;
        }
        else
        {
            $id = $bot;
        }

        $r = unlink($this->directory."/".$id.".bot");
    }


}