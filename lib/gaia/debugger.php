<?php
namespace Gaia;

/**
 * Simple debugger class.
 */
class Debugger {

   /**
    * @type int     debug stream
    */
    protected $io = NULL;
    
    protected $dateformat = 'H:i:s';
    
    protected $prefix = '';

    
   /**
    * turn on debug output.
    * @return void
    */
    public function __construct( $io = NULL ){
        if( ! $io ) $io = STDIN;
        if( is_resource( $io ) ) return $this->io = $io;
        if( $fp = fopen( $io, 'w' ) ) return $this->io = $fp;
    }
    
   /**
    * print out a line of debug. not sure if this should be public or not.
    */
    public function render( $v ){
        if( ! $this->io  ) return;
        if( $v instanceof Exception ) $v = $v->__toString();
        if( ! is_scalar( $v ) ) strval( $v );
        $dt =  "\n[" . date($this->dateformat) . '] ' . $this->prefix;        
        fwrite( $this->io, $dt . str_replace("\n", $dt, trim( $v )) );
    }
    
    public function setPrefix( $v ) {
        $this->prefix = $v;
    }
    
    public function setDateFormat( $v ){
        $this->dateformat = $v;
    }
}
// EOC
