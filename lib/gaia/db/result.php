<?php
namespace Gaia\DB;

class Result { //implements Traversable {

    protected $callbacks = array();
    
    public function __construct( array $callbacks ){
        $this->callbacks = $callbacks;
    }
    
    public function affected(){
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f();
    }
    
    public function free(){
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f();
    }
    
    public function fetch(){
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f();
    }
    
    public function all(){
        $rows = array();
        while( $row = $this->fetch() ) $rows[] = $row;
        return $rows;
    }
    
    public function __toString(){
        return '(Gaia\DB\CallbackResult object)';
    }
}