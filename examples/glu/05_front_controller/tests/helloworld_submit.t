#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
$route = 'helloworld';
$input = array('name'=>'stranger');
include __DiR__  . '/base.php';

Tap::plan(12);
Tap::like($output, '/<html>/', 'output is html' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/Hello, World/i', 'title says hello');
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/glu app/i', 'title says glu app' );
Tap::like($dom->getElementsByTagName('h1')->item(0)->nodeValue, '/front-end/i', 'h1 tag says front end');
Tap::like($dom->getElementsByTagName('h2')->item(0)->nodeValue, '/Hello, World!/i', 'h2 tag says Hello');
Tap::like( $output, '/Howdy, stranger/i', 'output says howdy');
Tap::like( $output, '/page generated in ([+-]?\\d*\\.\\d+)(?![-+0-9\\.]) seconds/i', 'page generated time in message' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/index\">Home<\\/a>#", 'nav link points home' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/helloworld\">Hello World<\\/a>#", 'nav link points to hello world' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/intro\">Introduction<\\/a>#", 'nav link points to intro' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/ajaxdemo\">Ajax Demo<\\/a>#", 'nav link points to ajax demo' );
Tap::isa( $dom->getElementsByTagName('form'), 'DOMNodeList', 'form dom element is present');
