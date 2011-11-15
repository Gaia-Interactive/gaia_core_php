<?php
// Build the site index page.
// right here you would call other actions or models to retrieve the information you need.
// then you build the page.
// In a view-first controller, you go to a view and then the view calls the actions needed to
// build the page. Since we already have all the info needed to build the page, just render it!


$this->instance( 
    array(  'title'=>'Home',
            'message'=>array('header'=>'Home Page', 'body'=>'This is a demo of how GLU can work'),
            'start'=>$this->start,
        ) )->dispatch(__DIR__ . '/layout/site.php'); 
