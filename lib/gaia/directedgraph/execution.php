<?php 
namespace Gaia\DirectedGraph;
use Gaia\Exception;
use Gaia\Container;


class Execution {
    
    /** pointer to the current node */ 
    protected $node;
    
    protected $data;
    
    /** an execution always starts in a given node */
    public function __construct( Node $node, Container $data = NULL ) {
        $this->node = $node;
        if( $data instanceof Container ) $this->data = $data;
    }
    
    /** executes the current node's actions and takes the event's transition */
    public function event($event) {
        $this->fire($event);
        if( ! isset( $this->node->transitions[ $event ]) ) return;
        $this->fire("leave-node");
        // take a transition
        $this->node = $this->node->transitions[ $event ];
        // enter the next node
        $this->fire("enter-node");
        $this->node->execute( $this );
    }
    
    /** fires the actions of a node for a specific event */
    protected function fire($event) {
        if (! isset( $this->node->actions[ $event ] ) ) return;
        foreach( $this->node->actions[ $event ] as $action ) call_user_func($action, $this);
    }
    
    public function __get( $k ){
        if( $k == 'node' ) return $this->node;
        if( $k == 'data' ) return $this->data instanceof Container ? $this->data : $this->data = new Container;
        throw new Exception('invalid property: ' . $k);
    }
    
    public function __set( $k, $v ){
        if( $k == 'node' && $v instanceof Node ) return $this->node = $v;
        if( $k == 'data' && $v instanceof Container ) return $this->data = $v;
        throw new Exception('invalid property: ' . $k);
    }
}
