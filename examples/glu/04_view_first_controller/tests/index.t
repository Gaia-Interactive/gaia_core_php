#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
$view = 'index';
include 'base.php';

Tap::plan(10);
Tap::like($output, '/<html>/', 'output is html' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/home/i', 'title says home' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/glu app/i', 'title says glu app' );
Tap::like($dom->getElementsByTagName('h1')->item(0)->nodeValue, '/view-first/i', 'title says view first' );
Tap::like($dom->getElementsByTagName('h2')->item(0)->nodeValue, '/home page/i', 'h2 says home page');
Tap::like( $output, '/This is a demo of how GLU can work/i', 'output gives demo message');
Tap::like( $output, '/page generated in ([+-]?\\d*\\.\\d+)(?![-+0-9\\.]) seconds/i', 'page generated time in message' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/index\">Home<\\/a>#", 'nav link points home' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/helloworld\">Hello World<\\/a>#", 'nav link points to hello world' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/intro\">Introduction<\\/a>#", 'nav link points to intro' );
