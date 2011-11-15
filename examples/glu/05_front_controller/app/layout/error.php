<?php
$this->instance(array('title'=>'Error'))->dispatch(__DIR__ . '/header.php');
$this->instance(array('header'=>'An error occurred', 'body'=>$this->exception ))->dispatch(__DIR__ . '/message.php');
$this->instance(array('start'=>$this->start))->dispatch(__DIR__ . '/footer.php');
