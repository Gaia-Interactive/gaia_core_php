<?php
namespace Gaia\DB;
use \PDO;

class StaticResult {
    /***
    * The rows of data
    * @type array
    ***/    
    protected $rows = array();
    
    public function __construct( array $rows = NULL){
        if( $rows ) $this->rows = $rows;
    }
    
    //
    public function data_seek( $offset ){
        if( $offset > count( $this->rows ) ) return FALSE;
        reset( $this->rows );
        for( $i = 0; $i < $offset; $i++){
            if( ! next( $this->rows ) ) return FALSE;
        }
        return TRUE;
    }
    
    // mysqli
    function fetch_all($resulttype = NULL ){
        $rows = array();
        foreach( $this->rows as $row ){
            switch( $resulttype ){
                case MYSQLI_ASSOC : 
                    break;
                    
                case MYSQLI_NUM :
                    $row = array_values( $row );
                    break;
                
                default :
                    $row = array_values( $row ) + $row;;
            }
            $rows[] = $row;
        }
        return $rows;
    }
    
    // pdo
    public function fetchAll($fetch_style = PDO::FETCH_BOTH){
        switch( $fetch_style ){
            case PDO::FETCH_ASSOC: return $this->fetch_all(MYSQLI_ASSOC);
            case PDO::FETCH_NUM: return $this->fetch_all(MYSQLI_NUM);
            case PDO::FETCH_BOTH: 
            default:    return $this->fetch_all();
        }
    }
    
    
    // mysqli
    public function fetch_array($resulttype = MYSQLI_BOTH){
        $row = current( $this->rows );
        if( ! is_array( $row ) ) return FALSE;
        next( $this->rows );
        if( $resulttype == MYSQLI_ASSOC ) return $row;
        if( $resulttype == MYSQLI_NUM ) return array_values( $row );
        return array_values( $row ) + $row;
    }
    
    // pdo
    public function fetch ( $fetch_style = PDO::FETCH_BOTH, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0 ){
        while( $cursor_offset-- ) next( $this->rows );
        if( $fetch_style == PDO::FETCH_ASSOC ) return $this->fetch_assoc();
        if( $fetch_style == PDO::FETCH_NUM) return $this->fetch_row();
        if( $fetch_style == PDO::FETCH_OBJ) return $this->fetch_object();
        return $this->fetch_array();
    }
    
    // mysqli
    public function fetch_assoc() {
        return $this->fetch_array( MYSQLI_ASSOC );
    }
    
    // mysqli
    public function fetch_object($class_name = 'stdclass', $params = NULL ){
        $row = $this->fetch_assoc();
        if( ! $row ) return FALSE;
        if( $params ){
            $obj = new $class_name( $params );
        } else {
            $obj = new $class_name();
        }
        foreach( $row as $k => $v) @$obj->$k = $v;
        return $obj;
    }
    
    // pdo
    public function fetchObject($class_name = 'stdclass', $params = NULL){
        return $this->fetch_object( $class_name, $params );
    }
    
    // mysqli
    public function fetch_row(){
        return $this->fetch_array(MYSQLI_NUM);
    }
    
    // mysqli
    public function free(){
        $this->rows = array();
        return TRUE;
    }
    
    // pdo
    public function closeCursor(){
        return $this->free();
    }
    
    public function __call( $method, array $args ){
        return FALSE;
    }
    
    public function __get( $key ){
        if( $key == 'num_rows' ) return count( $this->rows );
        if( $key == 'rowCount' ) return count( $this->rows );
        return NULL;
    }

}