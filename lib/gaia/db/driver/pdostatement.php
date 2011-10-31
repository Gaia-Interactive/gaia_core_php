<?php
namespace Gaia\DB\Driver;
use Gaia\DB\Transaction;
class PDOStatement extends \PDOStatement {

    protected function __construct( PDO $connection){
        $this->connection = $connection;
    }
    
    public function execute( $parameters = NULL ){
        if( $this->connection->locked() ) return FALSE;
        $res = parent::execute( $parameters );
        if( $res ) return $res;
        if( $this->connection->txn() ) {
            Transaction::block();
        }
        return $res;
    }

}
