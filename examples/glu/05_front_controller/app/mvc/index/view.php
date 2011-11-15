<?php
// render the header
$this->instance(array('title'=>'Home'))->dispatch(DIR_APP . 'layout/header.php'); 

// display a message 
$this->instance(array('header'=>'Home Page', 'body'=>'This is a demo of how GLU can work'))->dispatch(DIR_APP . 'layout/message.php');

// render the footer
$this->instance(array('start'=>$this->start))->dispatch(DIR_APP. 'layout/footer.php');

// EOF