<html>
<head>
<title><?php echo  $this->title ? $this->title : 'Welcome'; ?> :: GLU App</title>
</head>
<body>
<div id="header">
 <h1>GLU VIEW-FIRST DEMO</h1>
 <div id="nav">
  <ul>
   <li><a href="<?php echo $this->instance(array('view'=>'index') )->dispatch(__DIR__ . '/selfurl.php'); ?>">Home</a></li>
   <li><a href="<?php echo $this->instance(array('view'=>'helloworld') )->dispatch(__DIR__ . '/selfurl.php'); ?>">Hello World</a></li>
   <li><a href="<?php echo $this->instance(array('view'=>'intro'))->dispatch(__DIR__ . '/selfurl.php' ); ?>">Introduction</a></li>
  </ul>
 </div>
</div>
<div id="content">
<?php
if( $this->message ) $this->instance($this->message )->dispatch(__DIR__ . '/message.php');

if( $this->content instanceof Iterator || is_array( $this->content ) ){
    foreach( $this->content as $tpl => $data ) $this->instance( $data )->dispatch($tpl); 
}
?>
</div>
<div id="footer">
<em>page generated in <?php echo  number_format( microtime(TRUE) - $this->start, 4); ?> seconds</em>
</div>
</body>
</html>

