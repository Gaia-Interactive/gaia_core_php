<?php
namespace Gaia;
use Gaia\DB\Transaction;
use Gaia\DB\Iface;

/**
* This DB class is a simple wrapper around an existing database connection like mysqli or pdo.
* Here is the reason ... most likely you have already started work on a project when you decide to 
* use a component from gaia.  We're not gonna try to force you to rewrite your entire application
* just to be able to use something in gaia that communicates with a database. Instead, we'd like 
* you to be able to pass whatever database object you are using into our framework, and use it out 
* of the box. We do that by attaching your db object to a bunch of closure callbacks that act as an 
* adapter. We can do this easily since the Gaia\DB object really only needs to provide just a handful 
* of methods. We are a minimalist framework.
* 
* The db object needs to be able to start, rollback, and commit transactions, run queries, and escape 
* strings properly. That's pretty much it:
*
*   * $db->start();
*   * $db->commit();
*   * $db->rollback();
*   * $db->execute('SELECT * FROM table1 WHERE id = ?', $id);
*
* We escape strings DBI or pdo style, but without bothering with the prepared statement objects. 
* just pass in the parameters to the execute function like you might pass to sprintf:
*
*
*   * $db->execute('SELECT * FROM table1 WHERE id = %i', $id);
* 
* We support the following mappings:
*
*   %s -> strings
*   %i -> digits 
*   %f -> floats 
*   ?  -> strings 
*
* Our database library just types they variables based on the placeholder type and properly escapes them
* using the db object you passed in. All the work is done on the client side, so we can even generate 
* the SQL statement before hand and pass it in later:
*
*   $sql = $db->prep('INSERT INTO table1(id,data) VALUES (%i, %s)', $id, $data);
*   $db->execute($sql);
*
* Since all the work is done beforehand, and doesn't have to be validated and compiled on the server,
* I can use the prep method to build more dynamic SQL statements easily:
*
*   $values = array();
*   foreach( $list as $id=>$data){
*       $values[] = $db->prep('(%i, %s)', $id, $data);
*   }
*
*   $db->execute('INSERT INTO table1 (id,data) VALUES ' . implode(', ', $values) );
*
* This allows me to build a multi-row batch insert statement easily in a way that is difficult
* to do using PDO's prepared statements.
* 
* For more on this philosophy of database escaping, see: 

*   http://schlueters.de/blog/archives/155-Escaping-from-the-statement-mess.html
*
* We take a similar stance on the result object returned. You only need a method to see how many
* rows were affected by the query, grab the next row from the result set, free it when we are done,
* and maybe grab an insert id.
*   $sql = 'INSERT INTO table1 (id, data) VALUES (%i, %s)';
*   $result = $db->execute($sql, $id, $data);
*   if( ! $result ) throw new Exception('database error: ' . $db->error() );
*   $insert_id = $result->insertId();
*   $sql = 'SELECT * FROM table1';
*   $result = $db->execute($sql);
*   if( ! $result ) throw new Exception('database error: ' . $db->error() );
*   $affected_rows = $result->affected();
*   $rows = array(); 
*   while( $row = $result->fetch() ) $rows[] = $row;
*   $result->free();
*
* Normally the db->execute call returns false on query errors, but we recommend wrapping the DB 
* object in DB\Except so that it throws exception on query errors:
*   $db = new Gaia\DB\Except( $db );
*   try {
*       $db->execute($sql);
*   } catch( Exception $e ){
*       print $e;
*   }
*/
class DB implements Iface {
    
    protected $core;
    protected $_ = array();
    protected $_lock = FALSE;
    protected $_txn = FALSE;
    
    /**
    * pass in an object - mysqli, pdo, or codeigniter database object.
    * If you need another adapter, email John Loehrer <jloehrer@gaiaonline.com> or submit your own.
    * Thinking about writing an adapter for DBAL -- doctrine's data access layer.
    */
    function __construct( $core ){
    
        while( $core instanceof \Gaia\DB && $core->core() instanceof Iface ) $core = $core->core();

        $this->core = $core;
        if( $core instanceof \PDO ){            
           $this->_ = DB\Closure\PDO::closures( $core );
        } elseif( $core instanceof \MySQLi ) {     
           $this->_ = DB\Closure\MySQLi::closures( $core );
        } elseif( $core instanceof \CI_DB_driver) {
           $this->_ = DB\Closure\CI::closures( $core );  
        } else {
            trigger_error('invalid db object', E_USER_ERROR);
            exit(1);
        }
    }
    
    /**
    * grab the object passed into the constructor.
    */
    public function core(){
        return $this->core;
    }
    
    /**
    * start a new transaction.
    * connected to the Transaction singleton to support multi-database transactions.
    */
    public function start(){
        $args = func_get_args();
        $auth = isset( $args[0] ) ? $args[0] : NULL;
        if( $this->core instanceof Iface ) return $this->core->start( $auth );
        if( $auth == Transaction::SIGNATURE){
            if( $this->lock ) return FALSE;
            $this->txn = TRUE;
            $f = $this->_[ __FUNCTION__];
            return (bool) $f($auth);
        }
        Transaction::start();
        if( ! Transaction::add($this) ) return FALSE;
        return TRUE;
    }
    
    /**
    * rollback a transaction.
    * connected to the Transaction singleton to support multi-database transactions.
    */
    public function rollback(){
        $args = func_get_args();
        $auth = isset( $args[0] ) ? $args[0] : NULL;
        if( $this->core instanceof Iface ) return $this->core->rollback( $auth );
        if( $auth != Transaction::SIGNATURE) return Transaction::rollback();
        if( ! $this->txn ) return FALSE;
        if( $this->lock ) return TRUE;
        $f = $this->_[ __FUNCTION__];
        $res = (bool) $f($auth);
        $this->lock = TRUE;
        return $res;
    }
    
    /**
    * commit a transaction.
    * connected to the Transaction singleton to support multi-database transactions.
    */
    public function commit(){
        $args = func_get_args();
        $auth = isset( $args[0] ) ? $args[0] : NULL;
        if( $this->core instanceof Iface ) return $this->core->commit( $auth );
        if( $auth != Transaction::SIGNATURE) return Transaction::commit();
        if( ! $this->txn ) return FALSE;
        if( $this->lock ) return FALSE;
        $f = $this->_[ __FUNCTION__];
        $res = (bool) $f($auth);
        if( ! $res ) return $res;
        $this->txn = FALSE;
        return $res;
    }
    
    /**
    * run a query and return a Gaia\DB\Result object on success.
    * returns FALSE on failure.
    * pass in extra args to do automagic SQL preparation.
    */
    public function execute($query){
        if( $this->lock ) return FALSE;
        $args = func_get_args();
        array_shift( $args );
        $sql = $this->prep_args( $query, $args );
        $f = $this->_[ __FUNCTION__ ];
        $res = $f( $sql );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
   /**
    * get the last error message generated by the db.
    */
    public function error(){
        $f = $this->_[ __FUNCTION__ ];
        return $f();
    }
    
    /**
    * get the last error code generated by the db.
    */
    public function errorcode(){
        $f = $this->_[ __FUNCTION__ ];
        return $f();
    }
    
    /**
    * prepare an SQL statement, injecting the extra args into the SQL.
    * returns an SQL string, ready to be passed to the database.
    * can pass in partial SQL to allow more dynamic construction of SQL statements.
    */
    public function prep( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->prep_args( $query, $args );
    }
    
    /*
    * same as the prep method, but accepts a list of arguments to inject.
    * Can use this approach to inject named parameters:
    * 
    * $sql = $db->prep_args( SELECT * FROM t1 WHERE id = :test', array('test'=>1 ) );
    *
    */
    public function prep_args($query, array $args) {
        $f = $this->_[ __FUNCTION__ ];
        return $f( $query, $args );
    }
    
    /**
    * Like instanceof comparison, but also compares against the core object, and the name of
    * the platform. for example:
    *
    * $db = new Gaia\DB( $mysqli );
    * $db->isa('mysql'); // returns true.
    * 
    * $db = new Gaia\DB( $pdo_mysql );
    * $db->isa('mysql'); // returns true.
    */
    public function isa( $name ){
        if( $this instanceof $name ) return TRUE;
        if( $this->core instanceof $name ) return TRUE;
        $f = $this->_[ 'isa' ];
        return $f( $name );
    }
    
    /**
    * Unfortunately the db object is very messy when you do a print_r( $db ) on it. There are a slew
    * of closures attached to it that make it difficult to inspect.
    * but if you print it, this magic method converts it into a readable representation with all
    * the debug you might need.
    */
    public function __tostring(){
        $f = $this->_[ __FUNCTION__ ];
        return $f();
    }
    
    /**
    * you probably don't need to use this ... internal use only.
    */
    public function __get( $k ){
        if( $this->core instanceof Iface) return $this->core->__get( $k );
        if( $k == 'lock' ) return $this->_lock;
        if( $k == 'txn' ) return $this->_txn;
    }
    
    /**
    * you probably don't need to use this ... internal use only.
    */
    public function __set( $k, $v ){
        if( $this->core instanceof Iface) return $this->core->__set( $k, $v );
        if( $k == 'lock' ) return $this->_lock = (bool) $v;
        if( $k == 'txn' ) return $this->_txn = (bool) $v;
    }

}

// EOC
