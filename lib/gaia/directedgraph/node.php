<?php
namespace Gaia\DirectedGraph;
use Gaia\Exception;

/** a node in the process graph */
class Node {
    
    protected $cb;
    
    /** maps events to transitions */
    protected $transitions = array();
    
    /** maps events to actions */
    protected $actions = array();
    
    public function __construct($cb = NULL) {
        $this->callback = $cb;
    }
    
      
    /** create a new transition to the destination node and 
    * associate it with the given event
    *      ... or ....
    * create a new transition to the destination node and 
    * associate it with the given event */     
    public function add( $event, $result ){
        if( $result instanceof self) return $this->transitions[$event] = $result;
        if( is_scalar( $result ) && ( $pos = strpos($result, '::') ) !== FALSE ){
            $result = array( substr( $result, 0, $pos ), substr( $result, $pos + 2 ) );
        }
        if( ! is_callable( $result ) ) throw new Exception('invalid action');
        if ( ! isset( $this->actions[$event])) $this->actions[ $event ] = array();
        $this->actions[$event][] = $result;
        return $result;
    }
    
    /** to be overriden by Node implementations. The default doesn't 
    * propagate the execution so it behaves as a wait state. */
    public function execute( Execution $execution) {
        if( is_callable( $this->callback) ) call_user_func( $this->callback, $execution );
    }
    
    public function __get( $k ){
        if( $k == 'name' ) return $this->name;
        if( $k == 'actions' ) return $this->actions;
        if( $k == 'transitions' ) return $this->transitions;
        throw new Exception('invalid property');
    }
}