<?php
namespace Demo;
use Gaia\ShortCircuit\Request;

class ShortCircuit {
	function indexAction( Request $r ){
	    return array(
	        'title'=>'hello world', 
	        'message'=>'welcome to shortcircuit'
	        );
	}
	
	function echoAction( Request $r ){
	    return array(
	        'title'=>$r->get('title', 'safe', 'hello world'),
	        'message'=>$r->get('message', 'safe', 'welcome to shortcircuit'),
	    );
	}
}
