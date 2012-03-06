<?php
namespace Gaia\Stream;

/**
 * Allows us to run stream select calls in a non-blocking fashion.
 */
class Pool {
    
   /**
    * @type array   list of active streams
    */
    protected $streams = array();
    
    protected $debug;
    
    /**
    * add a new request to the pool.
    */
    public function add( Resource $r ){
        $stream = $r->stream;
        $id = (int) $stream;
        if( isset( $this->streams[ $id ] ) ) return $this->streams[ $id ];        
        stream_set_blocking($stream, 0);
		stream_set_write_buffer($stream, 0);
        return $this->streams[$id] = $r;
    }
    
    public function remove( Resource $r ){
        unset( $this->streams[(int) $r->stream] );
    }
    
    /**
    * get a list of all the streams in the pool.
    */
    public function streams(){
        return $this->streams;
    }
    
    public function attachDebugger( \Closure $debug = NULL ){
        $this->debug = $debug;
    }

   /**
    * wait for the specified timeout for data to come back on the socket.
    */
	public function select($timeout = 1.0){
	    if( ! $this->streams ) return FALSE;
	    $read = $write = array();
	    $except = NULL;
	    foreach( $this->streams as $r ){
	        $read[] = $r->stream;
	        if( $r->out ) $write[] = $r->stream;
	    }
	    list( $tv_sec, $tv_usec ) = explode('.', number_format($timeout, 6, '.', '') );
	    if( $tv_usec < 1 ) $tv_usec = 0;
	    
	    $status = stream_select($read, $write, $except, $tv_sec, $tv_usec);
	    if( $status === FALSE ) return FALSE;
	    if( $status === 0 ) return TRUE;
        if( $debug = $this->debug ) $debug("found $status active sockets: " . print_r( array( $read, $write), TRUE), 2 );
		
        foreach( $read as $id ){
	        $id = (int) $id;
	        if( ! isset( $this->streams[ $id ] ) ) continue;
	        $this->streams[ $id ]->read();
	    }
	    
        foreach( $write as $id ){
	        $id = (int) $id;
	        if( ! isset( $this->streams[ $id ] ) ) continue;
	        $this->streams[ $id ]->write();
	    }
	    
       
	    
		return TRUE;
	}
	
	/**
	* process all of the requests in the pool.
	*/
	public function finish($timeout = 1){
		while ($this->select($timeout) === TRUE) { 
		
		}
		return TRUE;
	}
}
// EOC
