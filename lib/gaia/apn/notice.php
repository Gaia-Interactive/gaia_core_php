<?php
namespace Gaia\APN;
use Gaia\Exception;

class Notice {
    
	const COMMAND_PUSH = 1; /**< @type integer Payload command. */
	const DEVICE_BINARY_SIZE = 32; /**< @type integer Device token length. */
    const HEADER_LEN = 45;
    protected $device_token;
    protected $message_id = 0;
    protected $message;
    protected $expires = 0;
    
    
    public function __construct( $binary = NULL ){
        if( $binary === NULL ) return;
        $this->unserialize( $binary );
    }   
    
    public function setDeviceToken( $device_token ){
        $device_token = str_replace(' ', '', $device_token );
        if (! is_string( $device_token ) || !preg_match('#^[a-f0-9]{64}$#i', $device_token)) {
			throw new Exception("Invalid device token '{$device_token}'");
		}
		$this->device_token = $device_token;
    }
    
    public function getDeviceToken(){
        return $this->device_token;
    }
    
    // either the json string, or the message object.
    public function setMessage( Message $message ){
        $this->message = $message;
    }
    
    public function getMessage(){
        return ( $this->message ) ? $this->message : $this->message = new Message();
    }
    
    public function setRawMessage( $string ){
        $this->message = new Message( $string );
    }
    
    public function getRawMessage(){
        return $this->getMessage()->serialize();
    }
    
     public function setMessageId( $id ){
        if( $id < 0 ) throw new Exception('invalid message id');
        $this->message_id = (int) $id;
    }
    
    public function getMessageId(){
        return $this->message_id;
    }
    
    public function setExpires( $v ){
        if( $v < 0 ) throw new Exception('invalid message expiry time');
        $this->expires = (int) $v;
    }
    
    public function getExpires(){
        return $this->expires;
    }
        
    public function __toString(){
        try {
            return $this->serialize();
        } catch( Exception $e ){
            return '';
        }
    }
    
    public function unserialize( $binary ){
        $blen = strlen( $binary );
        if( $blen < 45 ){
            throw new Exception('unable to unserialize. raw binary string too short to unpack', $binary );
        }
        
        $parts = unpack('Ccommand/Nmessage_id/Nexpires/ndevice_size/H64token/nmessage_len', $binary );
        //print_r( $parts );
        if( ! isset( $parts['command'] ) || $parts['command'] != self::COMMAND_PUSH ) {
            throw new Exception('unable to unserialize. invalid command in APNs request', $binary);
        }
        
        if( ! isset( $parts['message_id'] ) ){
            throw new Exception('unable to unserialize. invalid message_id in APNs request', $binary);
        }
        
        if( ! isset( $parts['token'] ) ){
            throw new Exception('unable to unserialize. invalid token in APNs request', $binary);
        }
        
        $this->setMessageId( $parts['message_id'] );
        $this->setDeviceToken( $parts['token'] );
        $this->setExpires( $parts['expires'] );
        if( $parts['message_len'] + self::HEADER_LEN != $blen ){
            throw new Exception('unable to unserialize. invalid message length', $binary);

        }
        
        $this->setRawMessage( substr( $binary, self::HEADER_LEN, self::HEADER_LEN + $parts['message_len']) );
        
    }
    
    public function serialize(){
        if( ! isset( $this->device_token ) ) {
            throw new Exception('no device token defined for APNS request');
        }
        $message = $this->getMessage()->serialize();
        
		return pack('CNNnH*n', 
		    self::COMMAND_PUSH, 
		    $this->message_id, 
		    $this->expires > 0 ? $this->expires : 0, 
		    self::DEVICE_BINARY_SIZE, 
		    $this->device_token, 
		    strlen($message)) .
		$message;
    }

}