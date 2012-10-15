<?php
namespace Gaia;

/**
* A simple wrapper for the mcrypt_encrypt and mcrypt_decrypt functions.
*/
class Crypto {
    
    // the default options to use.
    // most of the time you won't need to change these
    // unless your client doesn't support these.
    private $options = array( 
        'method' => MCRYPT_RIJNDAEL_256, 
        'mode'=> MCRYPT_MODE_ECB, 
        'rand_source'=>MCRYPT_RAND,
        );
    
    // a secret string to salt the encryption
    private $secret = '';
    
    
    /**
    * set up the object. pass in the secret and 
    * optionally change method, mode or rand_source in options.
    */
    public function __construct( $secret, array $options = array() ){  
        $this->secret = $secret;
        $this->options = array_merge( $this->options, $options );
    }
    
    /**
    * encrypt the pain text string. 
    * the returned value is a raw binary string, so if you plan to pass it over the 
    * wire it makes sense to base64 encode it.
    */
    public function encrypt( $plaintext ){
        return mcrypt_encrypt( 
            $this->options['method'],
            $this->secret, 
            $plaintext, 
            $this->options['mode'], 
            mcrypt_create_iv(
                mcrypt_get_iv_size(
                    $this->options['method'], 
                    $this->options['mode']
                ), 
                $this->options['rand_source']
            )
        );
    }
    
    // decrypt the data.
    // note: you may lose "\0" at the end of the original string because the php implementation of
    // mcrypt_decrypt pads the end of the string with this character to preserve a given block size.
    // (sloppy, but I don't know of a work-around.
    public function decrypt($encryptedtext){
        return rtrim( mcrypt_decrypt(
                $this->options['method'], 
                $this->secret, 
                $encryptedtext, 
                $this->options['mode'],
                mcrypt_create_iv(
                    mcrypt_get_iv_size(
                        $this->options['method'],
                        $this->options['mode']
                    ), 
                    $this->options['rand_source']
                )
            ), "\0");
    }
}