<?php
// we know what we want the title to be.
$title = 'Ajax Demo';

$dir_layout = __DIR__ . '/../../layout/';

// render the header and pass our page title to the header layout
$this->instance(array('title'=>$title) )->dispatch( DIR_APP . 'layout/header.php');

$this->instance(array('id'=>'mydiv', 'header'=>$title, 'body'=>'this text will be replaced'))->dispatch(DIR_APP . 'layout/message.php');

// build the link.
$link = $this->instance(array('route'=>'ajaxdemo', 'response'=>'1', 'dummy'=>'data'))->dispatch( DIR_APP . 'util/selfurl.php');

$this->instance(array('id'=>'mylink', 'href'=>$link, 'title'=>'test now', 'body'=>'run test'))->dispatch(DIR_APP . 'layout/link.php');

// include YUI libraries.
$this->instance()->dispatch(DIR_APP . 'layout/yui.php');

// include my own ajax caller script
$this->instance()->dispatch(DIR_APP . 'layout/callajaxlink.php');

// render the page footer.
$this->instance(array('start'=>$this->start))->dispatch(DIR_APP . 'layout/footer.php'); 

// EOF