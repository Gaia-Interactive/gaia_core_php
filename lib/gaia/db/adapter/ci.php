<?php
namespace Gaia\DB;

$cb = array();
$db = $this->core();


$cb['__tostring'] = function () use ( $db ){
    @ $res = print_r( $db, TRUE);
    return $res;
};

$cb['format_query_args'] = $format_args = function($query, array $args ) use ( $db ){
    if( ! $args || count( $args ) < 1 ) return $query;
    return \Gaia\DB\Query::format( 
        $query, 
        $args, 
        function($v) use( $db ){  return "'" . $db->escape_str( $v ) . "'"; }
       );
};

 $cb['execute'] = function( $query ) use ( $db ){
    try {
        $res = $db->query($query);
        
        if( ! $res ) return FALSE;
        $cb = array();
        
        if( is_object( $res ) ){
            $cb['fetch'] = function() use( $res ){
                return $res->_fetch_assoc();
            };
            $cb['free'] = function() use( $res ){
                $res->free_result();
            };
        }
        
        $affected = $db->affected_rows();
        
        $cb['affected'] = function() use( $affected ){
            return $affected;
        };
    
        return new Result( $cb );

    } catch( Exception $e ){
        return FALSE;
    }
};
            
$cb['start'] = function ($auth = NULL) use ($db){ 
    if( $db instanceof Iface ) return $db->start($auth);
    $db->trans_start();
    return TRUE;
};

$cb['commit'] = function ($auth = NULL) use ($db){ 
    if( $db instanceof Iface ) return $db->commit($auth);
    $db->trans_complete();
    return TRUE;
};

$cb['rollback'] = function ($auth = NULL) use ($db){ 
    if( $db instanceof Iface ) return $db->rollback($auth);
    $db->trans_rollback($auth);
    return TRUE;
};

$cb['lastinsertid'] = function() use ( $db ){
    return $db->lastInsertId();
};

$cb['error'] = function() use ( $db ){
    return $db->_error_message();
};

$cb['errorcode'] = function() use ( $db ){
    return $db->_error_number();
};

$cb['isa'] = function($name) use ( $db ){
    if( $db instanceof $name) return TRUE;
    if( $db->dbdriver == $name ) return TRUE;
    return FALSE;
};

return $cb;
