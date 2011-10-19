#!/usr/bin/env php
<?php
namespace Demo;
use Gaia\Nonce;
use Gaia\Test\Tap;
include __DIR__ . '/common.php';

// @see https://github.com/gaiaops/gaia_core_php/wiki/nonce
class AddFriendPage {

    protected static $session_id;
    const NONCE_TIMEOUT = 10;
    
    public static function render(){
        if( ! isset( $_REQUEST['mynonce'] ) ) return self::renderForm('submit nonce, please');
        if( ! self::checkNonce( $_REQUEST['mynonce'] ) ) return self::renderForm('invalid nonce .. try again');
        self::addFriend( $_REQUEST['friend'] );
        return self::renderSuccess($_REQUEST['friend']);
    }
    
    public static function addFriend( $friend ){
        // write to the database.
    }
    
    public static function renderForm($message){
        return 
            '<h1>' . $message  . '<h1>' . "\n" .
            '<form action="/site/addfriend" method="post">' . "\n" .
            '<input type="hidden" name="mynonce" value="' . self::createNonce() . '"/>' . "\n" .
            '<input type="field" name="friend" />' . "\n" .
            '<input type="submit"/>' . "\n" .
            '</form>';
    }
    
    public static function renderSuccess($friend){
        return '<h1>successfully added friend: ' . $friend . '</h1>';
    }
    
    public static function createNonce(){
        return self::nonce()->create(self::token(), time() + self::NONCE_TIMEOUT);
    }
    
    public static function checkNonce( $nonce ){
        return self::nonce()->check( $nonce, self::token() );
    }
    
    // the secret should probably come from something like a config file or setting somewhere.
    protected static function nonce(){
        return new Nonce( $secret = 'test12345' );
    }
    
    protected static function token(){
        return __CLASS__ . '/' . self::session_id();
    }
    
    // normally would just do: session_id() ... but this is a test, so let's control it a bit.
    // you could do anything unique to the user, like a facebook user id, or some other unique
    // value to the user.
    protected static function session_id(){
        if( isset( self::$session_id ) ) return self::$session_id;
        return self::$session_id = md5('test' . time());
    }
}

// ----
// 
// fake a nonce secret here, since we don't want to depend on the ./.nonce.secret.php file in the demo.
Tap::plan(2);
$_REQUEST['friend'] = 'davy crockett';

$input_form = AddFriendPage::render();
Tap::like($input_form, '/form/i', 'input form rendered' );
Tap::debug( $input_form, 'input form' );

// fake the nonce value
$_REQUEST['mynonce'] = AddFriendPage::createNonce();
$success_page = AddFriendPage::render();
Tap::like($success_page, '/success/i', 'success page prints after nonce validates');
Tap::debug( $success_page, 'validation page' );

