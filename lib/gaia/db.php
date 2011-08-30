<?php
namespace Gaia;
/*
the config file you load should look something like:
<?php
return array(
    'test'=>'mysqli://admin:pass@localhost:3306/test_db',
);

*/

use Gaia\Exception;
 
class DB {

    protected static $map = array();
    protected static $instances = array();
    
    public static function load( $file ){
        $res = require $file;
        if( ! is_array( $res ) ) throw new Exception('invalid db config file', $file );
        foreach( $res as $name => $config )self::$map[ $name ] = $config;
    }
    
    public static function instance( $name ){
        if( isset( self::$instances[ $name ] ) ) return self::$instances[ $name ];
        $params = self::config( $name );
        if( ! $params ) throw new Exception('invalid config', $name );
        switch( $params['scheme'] ) {
            case 'mysqli':
                if( ! isset( $params['host'] ) ) $params['host'] = ini_get("mysqli.default_host");
                if( ! isset( $params['port'] ) ) $params['port'] = ini_get("mysqli.default_port");
                if( ! isset( $params['user'] ) ) $params['user'] = ini_get("mysqli.default_user");
                if( ! isset( $params['pass'] ) ) $params['pass'] = ini_get("mysqli.default_pw");
                if( ! isset( $params['path'] ) ) $params['path'] = '';
                $params['path'] = trim($params['path'], '/');
                return self::$instances[ $name ] = new DB\MySQLi( $params['host'], $params['user'], $params['pass'], $params['path'], $params['port']);
        }
        throw new Exception('invalid db layer', $params );

        
    }
    
    public static function config( $name ){
        if( ! isset( self::$map[ $name ] ) ) return FALSE;
        $params = parse_url( self::$map[ $name] );
        if( ! is_array( $params ) ) return FALSE;
        if( ! isset( $params['scheme'] ) ) return FALSE;
        return $params;

    }
}
