#!/usr/bin/env php
<?php
$view = 'intro';

use Gaia\Test\Tap;
include __DIR__ . '/base.php';

Tap::plan(10);
Tap::like($output, '/<html>/', 'output is html' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/Introduction/i', 'title says introduction' );
Tap::like($dom->getElementsByTagName('title')->item(0)->nodeValue, '/glu app/i', 'title says glu app' );
Tap::like($dom->getElementsByTagName('h1')->item(0)->nodeValue, '/view-first/i', 'title says view first' );
Tap::like($dom->getElementsByTagName('h2')->item(0)->nodeValue, '/Welcome to GLU/i', 'h2 says welcome');
Tap::like( $output, '/This illustrates the basics/i', 'output gives demo message');
Tap::like( $output, '/page generated in ([+-]?\\d*\\.\\d+)(?![-+0-9\\.]) seconds/i', 'page generated time in message' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/index\">Home<\\/a>#", 'nav link points home' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/helloworld\">Hello World<\\/a>#", 'nav link points to hello world' );
Tap::like( $output, "#<a href=\"([a-z-0-9_\.\/]+)\\/intro\">Introduction<\\/a>#", 'nav link points to intro' );
