<?php
namespace Gaia\Apn;
use \Gaia\Exception;

/**
 * @file
 * ApnsPHP_Message class definition.
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://code.google.com/p/apns-php/wiki/License
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to aldo.armiento@gmail.com so we can send you a copy immediately.
 *
 * @author (C) 2010 Aldo Armiento (aldo.armiento@gmail.com)
 * @version $Id: Message.php 100 2011-09-12 13:50:56Z aldo.armiento $
 * @modified by John Loehrer <jloehrer@yahoo.com> 
 */

/**
 * The Push Notification Message.
 *
 * The class represents a message to be delivered to an end user device.
 * Notification Service.
 *
 * @ingroup ApnsPHP_Message
 * @see http://tinyurl.com/ApplePushNotificationPayload
 */
class Message
{
	const MAXIMUM_SIZE = 256; /**< @type integer The maximum size allowed for a notification payload. */
	const APS = 'aps'; /**< @type string The Apple-reserved aps namespace. */
	
	
	protected $auto_truncate = true; /**< @type boolean If the JSON payload is longer than maximum allowed size, shorts message text. */

	protected $data = array( 
	    self::APS => array(
	        'alert'=>NULL,  /**< @type string / array Alert message to display to the user. */
	        'badge'=>NULL,  /**< @type integer Number to badge the application icon with. */
	        'sound'=>NULL,  /**< @type string Sound to play. */
	        )
	);
	
	public function __construct( $data = NULL ){
	    if( $data === NULL ) return;
	    
	    if( is_string( $data ) ) {
	        return $this->unserialize( $data );
	    }
	    
        if( is_array( $data ) ) {
            return $this->setData( $data );
        }
	}
	
	
	/**
	 * Set the alert message to display to the user.
	 *
	 * @param  $sText @type string An alert message to display to the user.
	 */
	public function setAlert(array $alert)
	{
	    $valid_keys = array('body'=>'string', 'action-loc-key'=>'string', 'loc-key'=>'string', 'loc-args'=>'array', 'launch-image'=>'string');
	    foreach( $alert as $k => $v ){
	        if( ! isset( $valid_keys[ $k ] ) ) {
	            throw new Exception("invalid key for alert: $k");
	        }
	        if( $valid_keys[ $k ] == 'string' && ! is_string( $v ) ){
	            throw new Exception("invalid value for alert: $k");
	        }
	        
            if( $valid_keys[ $k ] == 'array' && ! is_array( $v ) ){
	            throw new Exception("invalid value for alert: $k");
	        }
	    }
	    
		$this->data[self::APS]['alert'] = $alert;
	}

	/**
	 * Get the alert message to display to the user.
	 *
	 * @return @type string The alert message to display to the user.
	 */
	public function getAlert()
	{
		return $this->data[self::APS]['alert'];
	}
	
	public function setText( $string ){
	    $this->data[self::APS]['alert'] = (string) $string;
	}
	
	public function getText(){
	    $alert = $this->data[self::APS]['alert'];
	    if( is_string( $alert ) ) return $alert;
	    return '';
	}

	/**
	 * Set the number to badge the application icon with.
	 *
	 * @param  $nBadge @type integer A number to badge the application icon with.
	 * @throws Exception if badge is not an
	 *         integer.
	 */
	public function setBadge($nBadge)
	{
		if (!is_int($nBadge)) {
			throw new Exception(
				"Invalid badge number '{$nBadge}'"
			);
		}
		$this->data[self::APS]['badge'] = $nBadge;
	}

	/**
	 * Get the number to badge the application icon with.
	 *
	 * @return @type integer The number to badge the application icon with.
	 */
	public function getBadge()
	{
		return $this->data[self::APS]['badge'];
	}

	/**
	 * Set the sound to play.
	 *
	 * @param  $sSound @type string @optional A sound to play ('default sound' is
	 *         the default sound).
	 */
	public function setSound($sound = 'default')
	{
		$this->data[self::APS]['sound'] = $sound;
	}

	/**
	 * Get the sound to play.
	 *
	 * @return @type string The sound to play.
	 */
	public function getSound()
	{
		return $this->data[self::APS]['sound'];
	}

	/**
	 * Get all custom properties names.
	 *
	 * @return @type array All properties names.
	 */
	public function getCustomPropertyNames()
	{
		$data = $this->data;
		unset( $data[ self::APS ] );
		return array_keys( $data );
	}


	/**
	 * Set a custom property.
	 *
	 * @param  $sName @type string Custom property name.
	 * @param  $mValue @type mixed Custom property value.
	 * @throws ApnsPHP_Message_Exception if custom property name is not outside
	 *         the Apple-reserved 'aps' namespace.
	 */
	public function setCustomProperty($sName, $mValue)
	{
		if ($sName == self::APS) {
			throw new ApnsPHP_Message_Exception(
				"Property name '" . self::APS . "' can not be used for custom property."
			);
		}
		$this->data[trim($sName)] = $mValue;
	}

	/**
	 * Get the custom property value.
	 *
	 * @param  $sName @type string Custom property name.
	 * @throws Exception if no property exists with the specified
	 *         name.
	 * @return @type string The custom property value.
	 */
	public function getCustomProperty($sName)
	{
		if (!array_key_exists($sName, $this->data)) {
			throw new Exception(
				"No property exists with the specified name '{$sName}'."
			);
		}
		return $this->data[$sName];
	}
	
	/**
	 * Set the auto-adjust long payload value.
	 *
	 * @param  $bAutoAdjust @type boolean If true a long payload is shorted cutting
	 *         long text value.
	 */
	public function setAutoAdjustLongPayload($auto_truncate)
	{
		$this->auto_truncate = (boolean)$auto_truncate;
	}

	/**
	 * Get the auto-adjust long payload value.
	 *
	 * @return @type boolean The auto-adjust long payload value.
	 */
	public function getAutoAdjustLongPayload()
	{
		return $this->auto_truncate;
	}


	/**
	 * PHP Magic Method. When an object is "converted" to a string, JSON-serialized
	 * payload is returned.
	 *
	 * @return @type string JSON-serialized payload.
	 */
	public function __toString()
	{
		try {
			return $this->serialize();
		} catch (Exception $e) {
			return '';
		}
	}

	/**
	 * Get the payload dictionary.
	 *
	 * @return @type array The payload dictionary.
	 */
	public function getData()
	{
		$data = $this->data;
		foreach( array('alert', 'badge', 'sound' ) as $key ){
		    if( $data[ self::APS ][ $key ] === NULL ) {
		        unset( $data[ self::APS ][ $key ] );
		    }
		}

		return $data;
	}
	
	public function setData( array $data ){
	    foreach( $data as $k => $v ){
            if( $k != self::APS ){
                $this->setCustomProperty( $k, $v );
                continue;
            }
            foreach( $v as $kk => $vv ){
                if( $kk == 'alert'){
                    if( is_array( $vv ) ){
                        $this->setAlert( $vv );
                    } else {
                        $this->setText( $vv );
                    }
                } elseif( $kk == 'badge'){
                    $this->setBadge( $vv );
                }elseif( $kk == 'sound'){
                    $this->setSound( $vv );
                }
            }
        }
	}

	/**
	 * Convert the message in a JSON-serialized payload.
	 *
	 * @throws Exception if payload is longer than maximum allowed size
	 * @return @type string JSON-serialized payload.
	 */
	public function serialize()
	{
		$json = str_replace(
			'"' . self::APS . '":[]',
			'"' . self::APS . '":{}',
			json_encode($this->getData())
		);
		$len = strlen($json);

		if ($len <= self::MAXIMUM_SIZE) return $json;

			
		$e = new Exception(
                "JSON Payload is too long: {$len} bytes. Maximum size is " .
                self::MAXIMUM_SIZE . " bytes. The message text can not be auto-adjusted."
            );
		
		if (! $this->auto_truncate ) throw $e;		
		if( ! is_string( $this->data[ self::APS ]['alert'] ) ) throw $e;
		
		$txt =& $this->data[ self::APS ]['alert'];
		

		$txtlen_max = $txtlen = strlen( $txt ) - ($len - self::MAXIMUM_SIZE);
		
		if ($txtlen_max < 1) throw $e;
		
        while (strlen($txt = mb_substr($txt, 0, $txtlen--, 'UTF-8')) > $txtlen_max);
		
		return $this->serialize();
	}
	
	
	public function unserialize( $string ){
	    $data = json_decode( $string, TRUE );
        if( ! is_array( $data ) ) {
            throw new Exception('invalid data, cannot unserialize data for ' . __CLASS__);
        }
        $this->setData( $data );	    
	}
	
}