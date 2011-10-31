<?php
namespace Gaia\Store;

/**
 * cache sessions.
 * @copyright 2003-present GAIA Interactive, Inc.
 */

class Session
{

    protected $core;
    protected $session_name = '';
    protected $retries = 0;
    protected $read_checksum = '';
    protected $last_touch = 0;
    const RETRY_MAX = 10;

    public function __construct( Iface $core ){
        $this->core = $core;
    }

    public function open( $savepath, $session_name ){
        $this->session_name = $session_name;
    }

    public function close(){

    }

    public function write( $id, $data ){
        if( md5( $data ) == $this->read_checksum && $this->last_touch + 60 > time()  ) return;
        if( $this->retries++ > self::RETRY_MAX ) {
            trigger_error('unable to write session');
            return;
        }

        $maxlife = get_cfg_var('session.gc_maxlifetime');
        $key = $this->prefix() . $id;
        $res = $this->core->set( $key, array('data'=>$data, 'touch'=>time()), $maxlife);
        if( $res ) {
            if( $this->core->add( $key . '-lock', 1, 86400 * 30 ) ){
                $this->cachelist()->add( $id );
            }
            return;
        }
        session_start();
        session_regenerate_id(FALSE);
        session_write_close();
    }

    public function read( $id ){
        $res = $this->core->get( $this->prefix() . $id );
        if( ! is_array( $res ) ) $res = array();
        if( ! isset( $res['data'])) $res['data'] = '';
        if( ! isset( $res['touch'])) $res['touch'] = 0;
        $this->last_touch = $res['touch'];
        $this->read_checksum = md5( $res['data'] );
        return $res['data'];
    }

    public function destroy( $id ){
        $this->core->delete( $this->prefix() . $id );
    }

    public function gc(){

    }

    public function cachelist(){
        return new Stack( new Prefix( $this->core, $this->prefix() . '-list') );
    }

    public static function init( Iface $core ){
        return self::set_save_handler( new self( $core ) );
    }

    protected static function set_save_handler( $handler ){
        session_set_save_handler(
            array( $handler, 'open'),
            array( $handler, 'close'),
            array( $handler, 'read'),
            array( $handler, 'write'),
            array( $handler, 'destroy'),
            array( $handler, 'gc')
        );
        return $handler;
    }

    protected function prefix(){
        return 'SESS-' . $this->session_name . '/';
    }
}