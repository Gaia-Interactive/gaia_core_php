#!/usr/bin/env php
<?php
namespace Demo;
use Gaia\Nonce;
use Gaia\Test\Tap;
include __DIR__ . '/common.php';

/**
* This class is a somewhat artificial demonstration of how you might use a nonce to verify the input
* came from a form. Why do you need a nonce? For starters, read:
*
* http://en.wikipedia.org/wiki/Cryptographic_nonce
*
* Nonces do not protect from SQL injection but they do help with a particular form of cross-site
* scripting attack. The hacker embeds an image or a link on a page. The embedded url is to a page 
* you have on your site that you didn't intend the user to go to without their consent. For example,
* a link where you add a friend.
* 
* The exploit might embed the url as an image in a profile picture or signature, where the image is
* sure to be seen by many people:
*
*   /site/addfriend?friend=HaxxAttack
*
* Every person who loads the image in their browser by visiting their profile page will unintentionally
* add HaxxAttack as a friend. The nonce validation is an easy way to prevent this attack, since it
* will only perform the request if the user clicks through the form. This example is a bit simplistic.
* You could probably secure the page by only accepting $_POST variables instead of $_GET or $_REQUEST 
* variables. But there are other cases where you might need to use a $_GET parameter. A nonce is an 
* easy way to keep your form from being exploited. In other cases, the exploit might use a javascript
* attack to perform an AJAX $_POST request. In this case, a nonce is a very good way to prevent the problem.
*
* Another real-world example is where an admin page for a site moderator provides tools for authorizing 
* other users to become moderators. If the hacker can embed an image on the site that the moderator sees,
* that image link could point to the admin page and uninentionally authorize users that shouldn't have access.
* When in doubt, use a nonce on all forms. It is easy to do, and prevents exploits you may not even 
* think of.
*
* The nonce is only as good as the token you use. If all i want to do is make sure that the form
* submitted came from the same user that generated the form, i can make a token out of the user id.
* other options include checksumming all of the known input values of the form and creating the token
* from that. The token must stay consistent between the input and the validation requests for the 
* nonce to work. 
* 
* We also specify how long the nonce is good for. If we want the form to only be valid for two minutes,
* we can set the NONCE_TIMEOUT class constant here to 120 seconds. This example only allows 10 seconds.
* Completely artificial example, but it works for us.
*
* You can enhance the nonce protection one layer further, by making it a single-use token. The easiest
* way to do this is to add the nonce as a memcache key. If the nonce key fails to be added to the 
* cache, we know the token has been used already once on a page load. (Example available on request).
*
*/
class AddFriendPage {

    protected static $session_id;
    const NONCE_TIMEOUT = 10;
    
    function render(){
        if( ! isset( $_REQUEST['mynonce'] ) ) return self::renderForm('submit nonce, please');
        if( ! self::checkNonce( $_REQUEST['mynonce'] ) ) return self::renderForm('invalid nonce .. try again');
        self::addFriend( $_REQUEST['friend'] );
        return self::renderSuccess($_REQUEST['friend']);
    }
    
    function addFriend( $friend ){
        // write to the database.
    }
    
    public function renderForm($message){
        return 
            '<h1>' . $message  . '<h1>' . "\n" .
            '<form action="/site/addfriend" method="post">' . "\n" .
            '<input type="hidden" name="mynonce" value="' . self::createNonce() . '"/>' . "\n" .
            '<input type="field" name="friend" />' . "\n" .
            '<input type="submit"/>' . "\n" .
            '</form>';
    }
    
    public function renderSuccess($friend){
        return '<h1>successfully added friend: ' . $friend . '</h1>';
    }
    
    public static function createNonce(){
        return self::nonce()->create(self::token(), time() + self::NONCE_TIMEOUT);
    }
    
    public function checkNonce( $nonce ){
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

