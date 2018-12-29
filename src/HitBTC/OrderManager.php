<?php


namespace Crypto\HitBTC;


use Crypto\Exchange\Events\NewTrade;
use Crypto\Exchange\Events\OrderUpdated;
use Crypto\Exchange\Order;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

class OrderManager
{

    public $conn;
    public $dispatcher;

    public function __construct(Connection $conn, EventDispatcher $dispatcher)
    {
        $this->conn = $conn;
        $this->dispatcher = $dispatcher;
    }

    public function subscribeTrades( TradeManager $tm)
    {
        $tm->dispatcher->addListener('TradeManager.NewTrade', [$this, 'handleTrade']);
    }

    public function handleTrade( NewTrade $newTrade )
    {


        $qb = new QueryBuilder($this->conn);
        $qb
            ->select('*')
            ->from('orders')
            ->where("access_id = '{$newTrade->trade->accessID}'")
            ->andWhere("e_client_order_id = '{$newTrade->trade->eClientOrderID}'")
            ->andWhere("side = '{$newTrade->trade->side}'")
            ->setMaxResults(1)
         ;

        $stm = $this->conn->query($qb->getSQL());
        $stm->execute();
        $row = $stm->fetch(\PDO::FETCH_ASSOC);

        if(!$row) return;

        $traded = $row['traded'] + $newTrade->trade->value;

        if( abs($traded - $row['value']) < pow(10, -8))
        {
            $status = 'filled';
        }
        else
        {
            $status = 'partiallyFilled';
        }


        $qb
            ->update('orders')
            ->set('traded', ":traded")
            ->set('status', ":status")
        ;

        $stm = $this->conn->prepare($qb->getSQL());
        $r = $stm->execute([
            ':traded'=>$traded,
            ':status'=>$status,
        ]);

        if($r)
        {
            $row['stats'] = $status;
            $row['traded'] = $traded;
            $order = $this->initOrder($row);
            $this->dispatcher->dispatch('OrderManager.OrderUpdated', new OrderUpdated($order));
        }



    }

    public function initOrder(array $row)
    {
        $order = new Order();

        $order->id = $row['id'];
        $order->traded = $row['traded'];
        $order->status = $row['status'];
        $order->eClientOrderID = $row['e_client_order_id'];
        $order->eOrderID = $row['e_order_id'];
        $order->side = $row['side'];
        $order->value = $row['value'];
        $order->price = $row['price'];
        $order->pairID = $row['pair_id'];
        $order->date = new \DateTime($row['created_at']);
        $order->type = $row['type'];

        return $order;
    }
}