#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(4);

  
// this is a mock message queue
class MessageQueue {

    public $queue = array();
    public $messagesAccepted = 0;
    public $messagesDelivered = 0;

    /** doesn't block the caller */
    public function send( Execution $e ) {
      $this->queue[] = $e;
      $this->messagesAccepted++;
    }

    /** consumes all available messages and then returns.
     * normally this would be done in a different thread.  but for 
     * illustration purposes in this unit test it's easer to 
     * invoke this method in the test-method's thread */
    public function processMessages() {
      while ( ( $execution = array_shift($this->queue) ) ) { 
        $execution->event('continue');
        $this->messagesDelivered++;
      }
    }
 }
  
class AsynchronousNode extends Node {

    protected $mq;
    
    public function __construct( MessageQueue $mq, $cb = NULL ){
        $this->mq = $mq;
        parent::__construct( $cb );
    }   

    public function execute(Execution $execution) {
      $this->mq->send($execution);
    }
}



$mq = new MessageQueue();

// one --> two --> three --> four
$one = new Node();
$two = new AsynchronousNode( $mq );
$three = new AsynchronousNode( $mq );
$four = new Node();
$one->add("continue",$two);
$two->add("continue",$three);
$three->add("continue",$four);

$execution = new Execution($one);
$execution->event("continue");
Tap::ok($two === $execution->node, 'execution node equals two');

// processMessages will propagate the execution 
// to the fourth node in 2 separate transactions
$mq->processMessages();
Tap::ok($four === $execution->node, 'execution node is four');
Tap::ok(2 === $mq->messagesAccepted, 'two messages accepted');
Tap::ok(2 === $mq->messagesDelivered, 'two messages delivered');
