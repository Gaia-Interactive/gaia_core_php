<?php
include __DIR__ . '/../common.php';

use Gaia\ShortCircuit\Router;
use Gaia\ShortCircuit\Resolver;
use Gaia\ShortCircuit\PatternResolver;

$patterns = array(
    'nested/test'=> array(
                    'regex'=>'#^/go/([0-9]+?)$#i', 
                    'params'=>array('id')
                    ),
                    
    'nested/deep/test' => array(
                    'regex'=>'#^/foo/bar/([a-z]+)/test/([a-z]+)$#i',
                    'params'=>array('a','b')
                    ),
    'id'=>array(
                    'regex' =>'#^/idtest/([0-9]+)/?$#i',
                    'params'=>array('id'),
        ),
        
    'linktest'=>array(
                    'regex' =>'#^/lt/([0-9]+)/([0-9]+)/([0-9]+)$#',
                    'params'=>array('a','b','c'),
        ),
                    
    'index' =>'#^/$#',
);

Router::resolver( new PatternResolver( new Resolver(__DIR__ . '/../shortcircuit/app/'), $patterns));
Router::run();