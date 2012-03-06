<?php
namespace Gaia\APN;

class Response
{

	const SIZE = 6; /**< @type integer Error-response packet size. */
	const COMMAND = 8; /**< @type integer Error-response command code. */

	protected static $responses = array(
		0   => 'No errors encountered',
		1   => 'Processing error',
		2   => 'Missing device token',
		3   => 'Missing topic',
		4   => 'Missing payload',
		5   => 'Invalid token size',
		6   => 'Invalid topic size',
		7   => 'Invalid payload size',
		8   => 'Invalid token',
	); /**< @type array Error-response messages. */
	
	public $message = '';
	public $status = 0;
	public $identifier = 0;
    
    public function __construct( $response = NULL ){
        if ( $response === NULL || $response === false || strlen($response) != self::SIZE) {
			return;
		}
	    $error_response = unpack('Ccommand/Cstatus/Nidentifier', $response);
		if (!isset($error_response['command'], $error_response['status'], $error_response['identifier'])) {
			return;
		}
		if ($error_response['command'] != self::COMMAND) {
			return;
		}
		$this->status = $error_response['status'];
		$this->message = isset(self::$responses[$this->status]) ? self::$responses[$this->status] : 'unknown';
		$this->identifier = $error_response['identifier'];
    }
    
    public static function responseCodes(){
        return self::$responses;
    }
}