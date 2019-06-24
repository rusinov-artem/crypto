<?php

require __DIR__."/../vendor/autoload.php";

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

$finder = new \Symfony\Component\Finder\Finder();

$finder->files()->in("/root/h/storage/bots/")
    ->name("*BCHSV*.*")
    ->date(">= 2019-05-16", '<= 2019-05-17');

/**
 * @var $file Symfony\Component\Finder\SplFileInfo
 */
foreach ($finder as $file)
{
    var_dump($file->getRealPath());
    $dt = (new DateTime())->setTimestamp($file->getCTime());
    var_dump($dt->format("Y-m-d"));
    unlink($file->getRealPath());

}