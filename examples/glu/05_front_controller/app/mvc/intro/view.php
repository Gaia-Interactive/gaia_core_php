<?php
// render the header
$this->instance( array('title'=>'Introduction') )->dispatch(DIR_APP . 'layout/header.php');

// pull down the data
$data = $this->instance( $this->data );


// build the body of the page
$this->instance( array('header'=>$data->title, 'body'=>$data->message) )->dispatch(DIR_APP . 'layout/message.php'); 

// render the footer
$this->instance(array('start'=>$this->start))->dispatch(DIR_APP . 'layout/footer.php'); 