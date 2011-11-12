<?php
namespace Gaia\DB\Driver;
use Gaia\DB\Transaction;
use Gaia\DB\Iface;

class PDOStatement extends \PDOStatement {

    protected function __construct( Iface $connection){
        $this->connection = $connection;
    }
    
    public function execute( $parameters = NULL ){
        if( $this->connection->lock ) return FALSE;
        $res = parent::execute( $parameters );
        if( $res ) return $res;
        if( $this->connection->txn ) {
            Transaction::block();
        }
        return $res;
    }

}
