<?php
class A{

    public $a;
    public $b;
    public $c;

    /**
     * A constructor.
     * @param $a
     * @param $b
     * @param $c
     */
    public function __construct( $a, $b, $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }

}

$rf = new ReflectionClass(A::class);
var_dump($rf);
$args = $rf->getConstructor()->getParameters();
$obj = $rf->newInstanceWithoutConstructor();
var_dump($obj);
