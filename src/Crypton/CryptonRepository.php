<?php


namespace Crypto\Crypton;


use Crypto\Exchange\Order;
use Crypto\Exchange\Trade;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class CryptonRepository
{

    /**
     * @var Connection
     */
    public $conn;

    public function __construct( Connection $conn)
    {
        $this->conn = $conn;
    }

    //Вернуть последний обработанный trade
    public function getLastTrade($accessID, $pairID)
    {
        $q = "select * from `trades` where access_id={$accessID} and pair_id='{$pairID}'  order by dt desc limit 1";
        $stm = $this->conn->query($q);
        $stm->execute();
        $row = $stm->fetch(\PDO::FETCH_ASSOC);

        if($row)
        {
            $trade = $this->initTrade($row);
            return $trade;
        }

        return false;
    }

    private function initTrade(array $row)
    {
        $trade = new Trade();

        $trade->pairID = $row['pair_id'];
        $trade->date = new \DateTime($row['dt']);
        $trade->eTradeID = $row['e_trade_id'];
        $trade->id = $row['id'];
        $trade->price = $row['price'];
        $trade->value = $row['value'];
        $trade->fee = $row['fee'];
        $trade->side = $row['side'];
        $trade->feeCurrency = $row['feeCurrency'];
        $trade->orderID = $row['order_id'];
        $trade->accessID = $row['access_id'];

        return $trade;

    }

    private function tradeToRow(Trade $trade)
    {
        $row = [];

        $row['pair_id']      = $trade->pairID;
        $row['dt']           = $trade->date->format('Y-m-d H:i:s');
        $row['e_trade_id']   = $trade->eTradeID;
        $row['id']           = $trade->id;
        $row['price']        = $trade->price;
        $row['value']        = $trade->value;
        $row['fee']          = $trade->fee;
        $row['side']         = $trade->side ;
        $row['feeCurrency']  = $trade->feeCurrency;
        $row['order_id']     = $trade->orderID;
        $row['access_id']    = $trade->accessID;

        return $row;
    }

    //Обработать новый трейд
    public function handleTrade($accessID, Trade $trade)
    {
        $row = $this->tradeToRow($trade);

        $row['access_id'] = $accessID;

        $values = array_reduce(array_keys($row), function(&$c, $i){
            $c[$i] = ":$i";
            return $c;
        }, []);

        $params = array_reduce($values, function(&$c, $i) use($row){
            $c[$i] = $row[substr($i, 1)];
            return $c;
        }, []);

        $qb = new QueryBuilder($this->conn);
        $q = $qb->insert("trades")->values($values)->getSQL();
        $stm  = $this->conn->prepare($q);
        $r = $stm->execute($params);
        return $r;

    }

    public function getOrder($exchangeID, $pairID, array $orderIDs)
    {

    }

    public function storeOrder(Order $order)
    {

    }

}