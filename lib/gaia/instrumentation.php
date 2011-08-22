<?php
/*
/* Copywrite 2010 Justin Swanhart and Percona Inc.
 All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

modifications for gaiaonline.com by John Loehrer <jloehrer@gaiaonline.com>
*/

/*
 * class for easing the implementation of instrumentation in your application.
 * Instrumentation - Provides an interface for storing counters and exporting them to the apache environment
 * 
 * 
 * BASIC USAGE
 * --------------------------------------------------------------------------------------
 * To automatically record CPU usage, memory usage and other metrics be sure to 
 * start the instrumentation request very early in the life of your application.
 * Ideally, this should be the first thing your application does.
 
 * Instrumentation::start_request();
 * 
 */

/* The instrumentation class implements a set of counter variables similar to MySQL 
 * status counters.  Counters are created dynamically, there is no
 * fixed list.
 * 
 * The counters are automatically exported to the Apache environment
 * for consumption by Apache logs.
 * 
 * CPU usage, memory usage, and other metrics are recorded.
 * 
 */
 namespace Gaia;
 
 class Instrumentation {

    private static $data;
    private static $at_start = array();
    private static $started_at=0;

    /* If the timer was started, then return the amount
     * of time elapsed in seconds with microsecond resolution.
     * 
     * By default, the function resets the timer to the
     * current time.
     */
    public static function timer($reset = true) {
        $elapsed = 0;
        if(self::$started_at) $elapsed = microtime(true) - self::$started_at; 
        if($reset) self::$started_at = microtime(true);
        return $elapsed;
    }
    
    public function run( $cmd, array $params = array() ){
        if( ! is_callable( $cmd ) ) throw new Exception('invalid command');
        $name = $cmd;
        if( is_array( $name ) ){
            if( is_object( $name[0] ) ) $name[0] = get_class( $name[0] );
            $name = implode('->', $name);
        }
        $_ts = microtime(TRUE);
        $res = call_user_func_array($cmd, $params );
        self::increment($name . '_count');
        self::increment( $name . '_time', microtime(TRUE) - $_ts);
        return $res;
    }
    
    /* Export all of the counters into the apache 
     * environment with a CTR_ prefix on each counter name.
     * 
     * Any counters on the blacklist will not be added.
     */
    public static function export_counters() {
        if( ! function_exists('apache_setenv') || config()->get('instrumentation_export_disable') ) return;
        apache_setenv('php_instrumented', 1, true);
        $data = self::data()->all();
        $data['basedir'] = DIR_BASE;
        apache_setenv("php_instrumented_json", json_encode( $data ));
    }


    /* This function is called from the 'config.php' script to
     * indicate the start of a new request.
     * 
     * We check to see if the id is already set, but this should
     * note be necessary since config.php is included with require_once().
     * 
     * The Instrumentation::end_request function is added as a shutdown 
     * function.
     */
    public static function start_request() {
        $data = self::data();
        if(!$data->get('request_id')) {
            $usage = getrusage();

            /* Capture current memory usage */
            self::$at_start['memory_usage'] = memory_get_usage();

            /* Capture CPU usage information */
            self::$at_start['cpu_user'] = $usage["ru_utime.tv_sec"]*1e6 + $usage["ru_utime.tv_usec"];  	
            self::$at_start['cpu_system'] = $usage["ru_stime.tv_sec"]*1e6 + $usage["ru_stime.tv_usec"];
            $data->set('request_id', sha1(posix_getpid() . '-' . get_server_ip()  . '-' . microtime(true)));

            register_shutdown_function(array('Instrumentation','end_request'));
        }
    }
    
    /* Export the performance counters to the
     * Apache environment and does some calculations, like
     * calculating the total request time in microseconds.
     */
    public static function end_request() {
        self::buildstats();
        self::export_counters();
    }
    
    public static function buildstats(){
        $usage = getrusage();
        $data = self::data();
        $data->set('cpu_user', (($usage["ru_utime.tv_sec"]*1e6+$usage["ru_utime.tv_usec"]) - self::$at_start['cpu_user']) / 1e6);  	
        $data->set('cpu_system', (($usage["ru_stime.tv_sec"]*1e6+$usage["ru_stime.tv_usec"]) - self::$at_start['cpu_system']) / 1e6);
        $data->set('total_cpu_time', $data->get('cpu_user') + $data->get('cpu_system') );
        $data->set('memory_usage', memory_get_usage() - self::$at_start['memory_usage']);
        $data->set('php_service_time', microtime(true) - MICROTIME);
        return $data;
    }
    
    public static function get( $name ){ return self::data()->get( $name );}
    public static function set( $name, $value ){return self::data()->set( $name, $value ); }
    public static function append($name, $value){ return self::data()->append( $name, $value ); }
    public static function increment($name, $value = 1){ return self::data()->increment( $name, $value ); }
    public static function remove( $name ){ return self::data()->remove( $name );}
    public static function exists($name ){ return self::data()->exists( $name ); }   
    public static function isEmpty( $name ){ return self::data()->isEmpty( $name ); }
    public static function getNames(){ return self::data()->getNames(); }
    public static function size($name = ""){ return self::data()->size( $name ); }
    public static function all(){ return self::data()->all();}
=    public static function data() {
        if ( self::$data !== NULL ) return self::$data;
        return self::$data = new Container( array(
                'request_id' => NULL,
                'mysql_query_count' => 0,
                'mysql_conn_count' => 0,
                'mysql_conn_time' => 0, 
                'mysql_query_time' => 0, 
                'mc_delete_count' => 0,
                'mc_delete_time' => 0,
                'mc_miss_count' => 0, 
                'mc_get_count' => 0, 
                'mc_getkey_count' => 0, 
                'mc_get_time' => 0,
                'mc_set_count' => 0,
                'mc_set_time' => 0,
                'mc_add_count' => 0,
                'mc_add_time' => 0,
                'mc_replace_count' => 0,
                'mc_replace_time' => 0,
                'mc_increment_count' => 0,
                'mc_increment_time' => 0,           
            ));
    }
}
