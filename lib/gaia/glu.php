<?php
namespace Gaia;

/**
 * GLU :: lightweight app framework
 *
 * This is a container class with a pretty slick way of processing includes.
 * we can use the dispatch method to run actions, build views, render templates, include libraries,
 * fetch resources, and any number of other fun tricks, including a map-reduce style approach to processing.
 * @author John Loehrer <john@72squared.com>
 */
 
/**
 * Here is the actual Glu Class. Enjoy!
 */
class Glu extends Container {
    
   /**
    * Simple factory method of instantiation.
    * This is useful when writing unit tests for Glu and you need to punch out
    * the insantiation of a new Glu.
    * @param mixed      $input
    * @return Glu
    */
    public static function instance( $input = NULL ){
        return new self( $input );
    }
    
   /**
    * include a file according to the argument and return the result.
    * @param string     path to the include, minus the php file extension.
    * @param boolean    do we want to validate the file path?
    * @return mixed     returns whatever the include file decided to return. depends largely
    *                   on context.
    */
    public function dispatch( $__file, $__strict = FALSE ){
       // make sure we are using the correct filepath delimiter here
        if( '/' != DIRECTORY_SEPARATOR ) $__file = str_replace('/', DIRECTORY_SEPARATOR, $__file );
        
        // add a php extension if one can't be found.
        if( substr($__file, -4) != '.php' ) $__file .= '.php';
        
        // blow up if we can't find the path to this file.
        if( $__strict && ! file_exists( $__file ) )  throw new Exception('invalid-dispatch: ' . $__file );
        
        // include the file and return the result.
        return include $__file;
    }
}

// EOF
