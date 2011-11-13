<?php
namespace Gaia\NewID;
use Gaia\Exception;

class DB implements Iface {
    protected $core;
    
    public function __construct( $db, $app = 'default' ){
        if( ! $db instanceof \Gaia\DB\Iface ){
            $db = new \Gaia\DB( $db );
        }
        if( ! $db->isa('\gaia\db' ) ) $db = new \Gaia\DB( $db );
        
        if( $db->isa('mysql') ){
            $this->core = new \Gaia\NewId\DB\MySQL( $db, $app );
        } elseif( $db->isa('pgsql') || $db->isa('postgre')){
            $this->core = new \Gaia\NewId\DB\PgSQL( $db, $app );
        } else {
            trigger_error('db platform not supported', E_USER_ERROR);
            exit(1);
        }
    }
    
    public function id(){
        return $this->core->id();
    }
    
    public function ids( $ct = 1 ){
         return $this->core->ids( $ct );
    }


    public function init(){
        $this->core->init();
    }
    
    public function testInit(){
        $this->core->testInit();
    }
}