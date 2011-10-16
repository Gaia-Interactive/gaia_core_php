<?php
$this->title = 'page not found';
$this->message = 'could not find the page you were looking for';

$this->render('site/tpl/header');
$this->render('site/tpl/message');
$this->render('site/tpl/footer');

