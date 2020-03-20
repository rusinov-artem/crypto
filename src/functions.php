<?php
function exceptionToString(\Throwable $t)
{
    return "EXCEPTION ".get_class($t).": ".$t->getMessage()." in ".$t->getFile().":".$t->getLine();
}