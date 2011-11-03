#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

Tap::plan(24);

Tap::cmp_ok( UTF8::to($data = 'simple'), '===', $data, 'simple string gets through');
Tap::cmp_ok( UTF8::to($data = 'More Complex String 1,2,3,4 [] <h1>hello</h1>'), '===', $data, 'more complex string gets through');
Tap::cmp_ok( UTF8::to($data = 'Τη γλώσσα μου έδωσαν ελληνική το σπίτι φτωχικό στις αμμουδιές του Ομήρου'), '===', $data, 'greek string gets through ');
Tap::cmp_ok( UTF8::to($data = 'На берегу пустынных волн Стоял он, дум великих полн'), '===', $data, 'russian string gets through');
Tap::cmp_ok( UTF8::to($data = '私はガラスを食べられます。それは私を傷つけません'), '===', $data, 'japanese string gets through');
Tap::cmp_ok( UTF8::to($data = '我能吞下玻璃而不伤身体'), '===', $data, 'chinese string gets through');
Tap::cmp_ok( UTF8::to($data = '€'), '===', $data, 'euro symbol gets through');


$invalid = array(
'2 Octet Sequence' => "\xc3\x28",
//'Sequence Identifier' => "\xa0\xa1", # this one fails for some reason. not able to detect and convert?
'3 Octet Sequence (in 2nd Octet)' => "\xe2\x28\xa1",
'3 Octet Sequence (in 3rd Octet)' => "\xe2\x82\x28",
'4 Octet Sequence (in 2nd Octet)' => "\xf0\x28\x8c\xbc",
'4 Octet Sequence (in 3rd Octet)' => "\xf0\x90\x28\xbc",
'4 Octet Sequence (in 4th Octet)' => "\xf0\x28\x8c\x28",
);

foreach( $invalid as $explanation => $string ){
    Tap::cmp_ok( UTF8::to( $string ), '!==', $string, 'invalid ' . $explanation . ' is changed' );
}


foreach( $invalid as $explanation => $string ){
    Tap::cmp_ok( UTF8::to( $string ), '===', mb_convert_encoding($string, 'UTF-8', 'auto'), 'invalid ' . $explanation . ' is converted to utf-8 encoding' );
}

$valid = array(
'2 Octet Sequence' => "\xc3\xb1",
'3 Octet Sequence' => "\xe2\x82\xa1",
'4 Octet Sequence' => "\xf0\x90\x8c\xbc",
'5 Octet Sequence (but not Unicode!)' => "\xf8\xa1\xa1\xa1\xa1",
'6 Octet Sequence (but not Unicode!)' => "\xfc\xa1\xa1\xa1\xa1\xa1",
);

foreach( $valid as $explanation => $string ){
    Tap::cmp_ok( UTF8::to( $string ), '===', $string, 'valid ' . $explanation . 'is unchanged');
}
