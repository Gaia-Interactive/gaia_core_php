<?php
$file = __DIR__ . '/.fb.config.php';
$config = @include $file;
if( ! is_array( $config ) ){
    die("\n<pre>\nplease configure $file: \n" . htmlspecialchars( '<?php') . "\n return array('fbId'=>'', 'secret'=>'', 'canvaspage'=>'');\n");
}
$canvaspage = $config['canvaspage'];

if (isset($_GET['code'])){
    header("Location: " . $canvaspage);
    exit;
}

ob_start();
include __DIR__ . '/../common.php';
use Gaia\Facebook\Persistence as Facebook;
use Gaia\Store;
$persistence = new Store\Signed(new Store\Cookie(array('prefix'=>'fb')), 'test');
$facebook = new Facebook($config, $persistence);
$me = null;
if( isset( $_REQUEST['debug'] ) ){
    print "<pre><b>cookies</b>\n" . print_r( $_COOKIE, TRUE ) . "\n</pre>\n";
    print "<pre><b>persistent data</b>\n" . print_r( $facebook->getAllPersistentData(), TRUE ) . "\n</pre>\n";	 
    print "<pre><b>signed request</b>\n" . print_r( $facebook->getSignedRequest(), TRUE ) . "\n</pre>\n";	 
}

$uid =  $facebook->getUser();
$loginUrl = $facebook->getLoginUrl(array('next' => $canvaspage,'cancel_url' => $canvaspage));

if ($uid) {
    try {
        $me = $facebook->api('/me');
    } catch (FacebookApiException $e) {
        if( isset( $_REQUEST['debug'] ) ){
            print "<pre><b>exception</b>\n" . $e->__toString() . "\n" . $e->getTraceAsString() . "\n</pre>\n";
        }
    }
}

if( ! $me ) {
    echo "<script type='text/javascript'>top.location.href = '$loginUrl';</script>";
    exit;
}

// time to render
?>
<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <body>
    <?php if( $me ): ?>
    <?php echo "Welcome, ".$me['name']. ".<br />"; ?>
    <?php endif; ?>
    <div id="fb-root"></div>
    <fb:login-button autologoutlink="true" size="small"></fb:login-button>
    <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId: '<?php echo $facebook->getAppID() ?>', 
          cookie: true, 
          xfbml: true,
          oauth: true
        });
        
        FB.Event.subscribe('auth.login', function(response) {
          top.location.href = '<?php echo $canvaspage;?>';
        });
        
        FB.Event.subscribe('auth.logout', function(response) {
          top.location.href = '<?php echo $loginUrl;?>';
        });
        
        <?php if( ! $me ): ?>
          top.location.href = '<?php echo $loginUrl;?>';
        <?php endif;?>
        
      };
      
      (function() {
        var e = document.createElement('script'); e.async = true;
        e.src = document.location.protocol +
          '//connect.facebook.net/en_US/all.js';
        document.getElementById('fb-root').appendChild(e);
      }());
    </script>
  </body>
</html>