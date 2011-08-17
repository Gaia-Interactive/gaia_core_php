<?php
namespace Gaia\Cache;

class StackDisabled extends Stack {
	public function add( $value, $expires = NULL ){return 0;}
	public function shift( $depth = NULL ){return false;}
	public function get($k) {return FALSE;}
	public function getRecent($limit = 10) {return FALSE;}
}