<?php
namespace Gaia\DB\Driver;

class PDOStatement extends \PDOStatement {

    protected function __construct( PDO $connection){
        $this->connection = $connection;
    }
    
    public function execute( $parameters = NULL ){
        if( $this->connection->locked() ) return FALSE;
        $res = parent::execute( $parameters );
        if( $res ) return $res;
        if( $this->connection->txn() ) {
            if( is_callable( $this->connection->txn() ) ) call_user_func( $this->connection->txn(), $this );
            $this->lock = TRUE;
        }
    }

}
