<?php
ob_start();
include __DIR__ . '/../../common.php';
use Gaia\Http\AuthDigest;
use Gaia\Store\KVP;



// instead of a container object you should use a persistent storage option like
// Store\DBA; This just shows proof of concept.
$storage = new KVP;

// instantiate hte digest
$auth = new AuthDigest( $realm = 'Restricted Area', $domain = '/' );


$username = 'foo';
$password = 'bar';
$ttl = 0; // store forever.

// normally you would pre-load your usernames and passwords into your storage.
// using the auth object to hash the password. 

// hashing the password is as simple as doing:
// md5( $username . ':' . $realm . ':' . $password );
// don't need the authdigest object to do it technically.
// but it is more convenient.
// not super encrypted, but it is a 1 way hash and unlikely that a dictionary attack
// will work to be able to reverse a list of passwords from the hashed password.
$storage->set( $username, $auth->hashPassword( $username, $password ), $ttl );

// can also store username and password in clear text.
// only store for 1 hr
$storage->set( 'bazz', 'quux', $ttl = 3600 );



if( ! $is_authenticated = $auth->authenticate( $storage ) ){
    $headers = $auth->challenge();
    foreach($headers as $header ) header( $header );
}
?>
<?php if( ! $is_authenticated ): ?>
<html>
 <head>
  <title>401 - Unauthorized</title>
 </head>
 <body>
  <h1>401 - Unauthorized</h1>
 </body>
</html>
<?php else: ?>
<html>
<body>
<h1>all ur base r belong 2 us</h1>
</body>
</html>
<?php endif; ?>