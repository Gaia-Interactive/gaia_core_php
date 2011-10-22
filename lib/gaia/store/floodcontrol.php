<?php
namespace Gaia\Store;
use Gaia\Exception;
use Gaia\Time;

/**
 * A smarter flood control - allows "burst" activity, 
 * but still enforce the overall flood requirements
 * based on a simple linear decay algorithm
 * @author Dan Quinlivan
 * @copyright 2003-present GAIA Interactive, Inc.
 */
class FloodControl {

    const DEFAULT_DECAY = 60;
    public static $time_offset = 0;
	protected $storage = array ( 1.0, 0);
	protected $options;
	protected $core;
	
	/* constructor
	 * instantiate and configure flood control
	 * @param - Iface - a cache object
	 * @param - array of options:
	 *  @param (string) $scope - a unique scope for this flood control, such as "avatarsave/{$user_id}"
	 *  @param (int) $max - number of times the user can burst before running into flood control (5 is a good number)
	 *  @param (int) $decay - decay rate in seconds (typically the same value as old style flood control, such as 60 seconds)
	 *  @param (int) $short - minimum time between events - you probably want 1 second here and it can't be 00001
	 */
    public function __construct( Iface $core, $options = NULL ){
		$options = new Container( $options );
		if( ! $options->scope ) throw new Exception('invalid flood-control scope');
		if( ! $options->max ) $options->max = 1;
	    if( ! $options->short ) $options->short = 1;
		if( ! $options->decay ) $options->decay = self::DEFAULT_DECAY;	
		$this->options = $options;
		$this->core = $core;
		$storage = $this->core->get($this->options->scope);
		if (!$storage || !is_array($storage)) $this->storage = array ( (float) 1.0, (int) 0);
		else $this->storage = $storage;	
	}
	
	
	/* event
	 * try to run an event against flood control
	 * @return (bool) $success - this tells you if you pass flood control
	 */
	public function go(){		
		$value = $this->calculate();
		if ($value > $this->options->max) return FALSE;
		$res = $this->core->add($this->options->scope . '_SHORT', 1, $this->options->short);
		if (! $res ) return FALSE;
		$this->storage = array(++$value, Time::now());
        $this->core->set($this->options->scope, $this->storage);
		return TRUE;
	}	
	
	
	/* find out how long until you can run another event
	 * possibly useful for displaying a countdown timer for the user
	 * @return (int) $seconds - # of seconds before you can perform another event
	 */
	public function timeToNext(){		
		$remaining_short = $this->calculateShort();		
		$value = $this->calculate();
		$remaining_long = 0.0;
		if ($value > $this->options->max){
			$diff = (float) $value - (float) $this->options->max;
			$decay = (float) $this->decay;			
			$remaining_long = (float) $diff * (float) ($decay + 0.00001); //  <-- fuck you php
		}
		
		if ($remaining_long > $remaining_short) return  intval( $remaining_long);
		return (int) $remaining_short;
	}
		
	// make calculations for event qualification
	protected function calculate(){
			
		list ($value, $time) = $this->storage;	
		$elapsed_time = Time::now() - $time;
		if ($elapsed_time != 0){ 
			$value -= (float) $elapsed_time / $this->options->decay;		
		}
		if ($value < 1.0) $value = (float) 1.0;
		return (float) $value;

	}
	
	// get time left before short interval is met
	protected function calculateShort(){
		list ($value, $time) = $this->storage;		
		$elapsed_time = Time::now() - $time;
		if ($elapsed_time < $this->options->short) return $this->options->short - $elapsed_time;
		return 0;
	}
}
