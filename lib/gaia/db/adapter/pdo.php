<?php
namespace Gaia\DB;

$cb = array();
$db = $this->core();


 $cb['__tostring'] = function () use ( $db ){
    list( $errstate, $errcode, $errmsg ) = $db->errorInfo();
    @ $res ='(Gaia\DB\PDO object - ' . "\n" .
        '  [driver] => ' . $db->getAttribute(\PDO::ATTR_DRIVER_NAME) . "\n" .
        '  [connection] => ' . $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS) . "\n" .
        '  [version] => ' . $db->getAttribute(\PDO::ATTR_SERVER_VERSION) . "\n" .
        '  [info] => ' . $db->getAttribute(\PDO::ATTR_SERVER_INFO) . "\n" .
        '  [error] => ' . $errmsg . "\n" .
        '  [insert_id] => ' . $db->lastInsertId() . "\n" .
        ')';
    return $res;
};

$cb['format_query_args'] = $format_args = function($query, array $args ) use ( $db ){
    if( ! $args || count( $args ) < 1 ) return $query;
    return \Gaia\DB\Query::format( 
        $query, 
        $args, 
        function($v) use( $db ){ return $db->quote( $v ); }
       );
};

 $cb['execute'] = function( $query ) use ( $db ){
    try {
        $res = $db->query($query);
        
        if( ! $res ) return FALSE;
        $cb = array();
    
        $cb['fetch'] = function() use( $res ){
            return $res->fetch(\PDO::FETCH_ASSOC);
        };
        $cb['free'] = function() use( $res ){
            $res->closeCursor();
        };
        
        $affected = $res->rowCount();
        
        $cb['affected'] = function() use( $affected ){
            return $affected;
        };
    
        return new Result( $cb );

    } catch( \Exception $e ){
        return FALSE;
    }
};
            
$cb['start'] = function ($auth = NULL) use ($db){ 
    if( $db instanceof Iface ) return $db->start($auth);
    return $db->beginTransaction();
};

$cb['commit'] = function ($auth = NULL) use ($db){ 
    if( $db instanceof Iface ) return $db->commit($auth);
    return $db->commit();
};

$cb['rollback'] = function ($auth = NULL) use ($db){
    if( $db instanceof Iface ) return $db->rollback($auth);
    return $db->rollback();
};

$cb['lastinsertid'] = function() use ( $db ){
    return $db->lastInsertId();
};

$cb['error'] = function() use ( $db ){
    $info = $db->errorInfo();
    return $info[2];
};

$cb['errorcode'] = function() use ( $db ){
    $info = $db->errorInfo();
    return $info[1];
};

$cb['isa'] = function($name) use ( $db ){
    if ( $db instanceof $name) return TRUE;
    if( $name == $db->getAttribute(\PDO::ATTR_DRIVER_NAME) ) return TRUE;
    return FALSE;
};

return $cb;
