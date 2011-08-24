<?php

class Greeter {
    static $messages = array('wazzup?', 'howzit?','yo yo yo!');
    public static function randomMessage(){
        return static::$messages[ array_rand( self::$messages ) ];
    }
}