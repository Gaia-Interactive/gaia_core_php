#!/usr/bin/env php
<?php
use Gaia\Test\Tap;

$route = 'index';
include __DIR__ . '/base.php';

Tap::plan(11);
Tap::like($output, '/<html>/', 'output is html' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/home/i', 'title says home');
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/glu app/i', 'title says glu app' );
Tap::like($dom->getElementsByTagName('h1')->item(0)->nodeValue, '/front-end/i', 'h1 tag says front end');
Tap::like($dom->getElementsByTagName('h2')->item(0)->nodeValue, '/home page/i', 'h2 tag says home page');
Tap::like( $output, '/This is a demo of how GLU can work/i', 'output says demo message');
Tap::like( $output, '/page generated in ([+-]?\\d*\\.\\d+)(?![-+0-9\\.]) seconds/i', 'page generated time in message' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/index\">Home<\\/a>#", 'nav link points home' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/helloworld\">Hello World<\\/a>#", 'nav link points to hello world' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/intro\">Introduction<\\/a>#", 'nav link points to intro' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/ajaxdemo\">Ajax Demo<\\/a>#", 'nav link points to ajax demo' );        
