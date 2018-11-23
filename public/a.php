<?php


class IntHelper
{
    public static function formatFiguresRound($float, $numbers)
    {
        $string = '%.' . $numbers . 'f';
        return sprintf($string, $float);
    }

    public static function format8figuresRound($float)
    {
        return sprintf('%.8f', $float);
    }

    public static function cut8figures($float)
    {
        $fig8 = sprintf('%.9f', $float);
        return bcdiv($fig8, "1", 8);
    }

    public static function cut4figures($float)
    {
        $pos = strpos($float, '.') + 5;
        return (float) substr($float, 0, $pos);
    }
}

var_dump(IntHelper::cut8figures(100.71710577 / 0.01));
var_dump(floor(100.71710577 / 0.01) * 1);
var_dump(IntHelper::cut8figures(floor(101) * 1));