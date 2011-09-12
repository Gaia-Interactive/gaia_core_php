<?php
namespace Gaia\Shard;

class Date {

    protected $by = 'month';
    protected $cutoff = '365'; // days
    protected $ts;
    protected $start;
    
    public function __construct( array $args = NULL ){
        if( $args ){
            foreach( $args as $k => $v ){
                switch( $k ){
                    case 'by': 
                            $this->by = $v;
                            break;
                            
                    case 'cutoff': 
                            $this->setCutoff( $v );
                            break;

                    case 'ts': 
                    case 'timestamp':
                            $this->setTimestamp( $v );
                            break;                        

                    case 'start': 
                            $this->start = $v;
                            break;                    
                    
                }
            }
            if( ! isset( $this->start ) ) $this->start = time();
        }
    }

    public function shard(){ 
        if(  $this->ts == NULL ) $this->ts = $this->start; 
        switch ($this->by ) {
            case 'd':
            case 'day' :    return date('Ymd', $this->ts);
            case 'y':
            case 'year' :   return date('Y', $this->ts );
            case 'w':
            case 'wk':
            case 'week' :   return date('Y', $this->ts ) . str_pad(ceil(date('z', $this->ts) / 7), 2, '0', STR_PAD_LEFT);
            case 'm':
            case 'mo':
            case 'month' : 
            default:       return date('Ym', $this->ts);
        }
    }
    
    public function setShard( $suffix ){
        switch ( $this->by ) {
            case 'd':
            case 'day' :    return $this->ts = mktime( $hour = 0, $minute = 0, $second = 0, $month = substr( $suffix, 4,2), $day = substr($suffix, 6,2), $year = substr($suffix, 0,4) );
            case 'y':
            case 'year' :    return $this->ts = mktime( $hour = 0, $minute = 0, $second = 0, $month = 1, $day = 1, $year = substr($suffix, 0,4) );
            case 'w':
            case 'wk':
            case 'week' :   return $this->ts = mktime( $hour = 0, $minute = 0, $second = 0, $month = 1, $day = intval(substr($suffix, 4,2)) * 7, $year = substr($suffix, 0,4) );
            case 'm':
            case 'mo':
            case 'month' : 
            default:       return $this->ts = mktime( $hour = 0, $minute = 0, $second = 0, $month = substr( $suffix, 4,2), $day = 1, $year = substr($suffix, 0,4) );
        }
    
    
    }
    
    public function timestamp( $v = NULL ){ 
        if(  $this->ts == NULL ) $this->ts = $this->start; 
        return $this->ts; 
    }
    
    public function setTimestamp( $v ){
        return $this->ts = $v;
    }
    
    public function next(){
        if(  $this->ts == NULL ) $this->ts = $this->start; 
        switch ( $this->by ) {
            case 'd':
            case 'day' :    $this->ts -= (3600 * 24);
                            break;
            case 'y':                 
            case 'year' :
                            $this->ts = mktime( $hour = 0, $minute = 0, $second = 0, $month = 1, $day = 1, $year = (date('Y', $this->ts) - 1) );
                            break;
            case 'w':
            case 'wk':
            case 'week' :   $this->ts -= (3600 * 24 * 7);
                            break;
            case 'm':
            case 'mo':
            case 'month' : 
            default : 
                            $this->ts = mktime( $hour = 0, $minute = 0, $second = 0, date('m', $this->ts) - 1, $day = 1, date('Y', $this->ts) );
                            break;
        }
        if( $this->ts < $this->cutoff() ){
            $this->ts = NULL;
            return FALSE;
        }
        return TRUE;

    }

    public function setCutoff( $cutoff ){
         return $this->cutoff = $cutoff;
    }
    
    public function cutoff(){
	    $cutoff = strval($this->cutoff);
        if( ctype_digit( $cutoff ) ) return $this->start - (intval( $cutoff ) * 24 * 3600);
        return strtotime($cutoff);
    }
}
