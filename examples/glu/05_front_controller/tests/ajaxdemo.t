#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
$route = 'ajaxdemo';
include __DIR__ . '/base.php';

Tap::plan(15);
Tap::like($output, '/<html>/', 'output is html' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/Ajax Demo/i', 'title says ajax demo');
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/glu app/i', 'title says glu app' );
Tap::like($dom->getElementsByTagName('h1')->item(0)->nodeValue, '/front-end/i', 'h1 tag says front end');
Tap::like($dom->getElementsByTagName('h2')->item(0)->nodeValue, '/Ajax Demo/i', 'h2 tag says ajax demo');
Tap::like( $output, '/this text will be replaced/i', 'output says this text will be replaced');
Tap::like( $output, '/page generated in ([+-]?\\d*\\.\\d+)(?![-+0-9\\.]) seconds/i', 'page generated time in message' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/index\">Home<\\/a>#", 'nav link points home' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/helloworld\">Hello World<\\/a>#", 'nav link points to hello world' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/intro\">Introduction<\\/a>#", 'nav link points to intro' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/ajaxdemo\">Ajax Demo<\\/a>#", 'nav link points to ajax demo' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/ajaxdemo\?response=1&amp;dummy=data(.+)\">run test<\\/a>#", 'link to run test' );
Tap::like( $output, "#yahoo-min.js#", 'YUI main script present' );
Tap::like( $output, "#event-min.js#", 'YUI event script present' );
Tap::like( $output, "#ajaxdemo\?script=1#", 'script ajax demo present' );

