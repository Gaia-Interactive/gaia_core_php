<?php
return array(
    'title'=>$this->request()->get('title', 'safe', 'hello world'),
    'message'=>$this->request()->get('message', 'safe', 'welcome to shortcircuit'),
);