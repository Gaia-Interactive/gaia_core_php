<?php
namespace Gaia\Apn;
use Gaia\Exception;

// php5-openssl dependency

class Connection extends \Gaia\Stream\Resource
{    
    protected static $reader;
    protected static $writer;
            
    public function __construct( $stream ){		
        parent::__construct( $stream );
        stream_set_blocking($stream, 0);
		stream_set_write_buffer($stream, 0);
        $this->read = self::reader();
        $this->write = self::writer();
    }
    
    public function send( Notice $notice ){
        $this->in = $this->out = '';
        $this->add( $notice );
        $this->write();
        $this->read();
        return $this->in;
    }
    
	public function add( Notice $notice ){
        $this->out .= $notice->serialize();
    }
    
    public static function writer(){
        if( isset( self::$writer ) ) return self::$writer;
        return self::$writer = function( $conn ){
            $len = strlen( $conn->out );
            if( $len < 1 ) return FALSE;
            $wlen = fwrite( $conn->stream, $conn->out, $len );
            if( $wlen === FALSE ){
                throw new Exception('write error');
            }
            if( $wlen < $len ) {
                $conn->out = substr( $conn->out, $wlen);
            } else {
                $conn->out = '';
            }
            return TRUE;
        };
    }
    
    protected static function reader(){
        if( isset( self::$reader ) ) return self::$reader;
        return self::$reader = function( $conn ){
            $response = fread($conn->stream, Response::SIZE);
            if( $response == '' ) return TRUE;
            if( $response === FALSE ) return FALSE;
            $conn->in .= $response;
            return TRUE;
        };
    }
    
    public function readResponses(){
        $list = array();
        while( strlen( $this->in ) >= Response::SIZE ){
            $list[] = new Response( substr( $this->in, 0, Response::SIZE ) );
            $this->in = substr( $this->in, Response::SIZE + 1);
        }
        if( $this->in === FALSE ) $this->in = '';
        return $list;
    }
}