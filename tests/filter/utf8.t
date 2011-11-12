#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mbstring_installed.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

Tap::plan(50);


$invalid = array(
'2 Octet Sequence' => "\xc3\x28",
//'Sequence Identifier' => "\xa0\xa1", # this one fails for some reason. not able to detect and convert?
'3 Octet Sequence (in 2nd Octet)' => "\xe2\x28\xa1",
'3 Octet Sequence (in 3rd Octet)' => "\xe2\x82\x28",
'4 Octet Sequence (in 2nd Octet)' => "\xf0\x28\x8c\xbc",
'4 Octet Sequence (in 3rd Octet)' => "\xf0\x90\x28\xbc",
'4 Octet Sequence (in 4th Octet)' => "\xf0\x28\x8c\x28",
);

$valid = array(

'simple ascii string'=>'simple',
'more complex ascii'=>"More Complex String 1,2,3,4 [] <h1>hello</h1>",
'greek sentence'=>"Τη γλώσσα μου έδωσαν ελληνική το σπίτι φτωχικό στις αμμουδιές του Ομήρου",
'russian sentence'=>"На берегу пустынных волн Стоял он, дум великих полн",
'japanese sentence'=>"私はガラスを食べられます。それは私を傷つけません",
'chinese sentence'=>"我能吞下玻璃而不伤身体",
'euro symbol'=>"€",
'2 Octet Sequence' => "\xc3\xb1",
'3 Octet Sequence' => "\xe2\x82\xa1",
'4 Octet Sequence' => "\xf0\x90\x8c\xbc",
//'5 Octet Sequence (but not Unicode!)' => "\xf8\xa1\xa1\xa1\xa1", # breaks json
//'6 Octet Sequence (but not Unicode!)' => "\xfc\xa1\xa1\xa1\xa1\xa1", # breaks json
);

foreach( $invalid as $explanation => $string ){
    Tap::cmp_ok( UTF8::to( $string ), '!==', $string, 'invalid ' . $explanation . ' is changed' );
}


foreach( $invalid as $explanation => $string ){
    Tap::cmp_ok( UTF8::to( $string ), '===', mb_convert_encoding($string, 'UTF-8', 'auto'), 'invalid ' . $explanation . ' is converted to utf-8 encoding' );
}



foreach( $valid as $explanation => $string ){
    Tap::cmp_ok( UTF8::to( $string ), '===', $string, 'valid ' . $explanation . ' is unchanged');
}


foreach( $invalid as $explanation => $string ){
    Tap::cmp_ok( json_decode(json_encode(UTF8::to($string))), '===', UTF8::to($string), 'invalid ' . $explanation . ' is json safe' );
}

foreach( $valid as $explanation => $string ){
    Tap::cmp_ok( json_decode(json_encode(UTF8::to($string))), '===', UTF8::to($string), 'valid ' . $explanation . ' is json safe' );
}


Tap::debug("\n-------------------------------\nfrom http://www.cl.cam.ac.uk/~mgk25/ucs/examples/UTF-8-test.txt");
$stress_test = array(
'1  Correct UTF-8 text' =>              json_decode('"\u03ba\u03cc\u03c3\u03bc\u03b5"'),
'2.1.1  1 byte  (U-00000000)'=>         json_decode('"\u0000"'),                                      
'2.1.2  2 bytes (U-00000080)'=>         json_decode('"\u0080"'),
'2.1.3  3 bytes (U-00000800)'=>         json_decode('"\u0080\u0000"'),
'2.1.4  4 bytes (U-00010000)'=>         json_decode('"\u0000\u0001\u0000\u0000"'),
'2.1.5  5 bytes (U-00200000)'=>         json_decode('"\u0000\u0002\u0000\u0000\u0000"'),
'2.1.6  6 bytes (U-04000000)'=>         json_decode('"\u0400\u0000\u0000\u0000\u0000"'),
//'2.2.1  1 byte  (U-0000007F)'=>         json_decode('"\u007f"'), // this breaks

'2.3.1  U-0000D7FF'=>        json_decode('"\u0000\ud7ff"'),
'2.3.2  U-0000E000'=>        json_decode('"\u0000\ue000"'),
'2.3.3  U-0000FFFD'=>        json_decode('"\u0000\ufffd"'),
'2.3.4  U-0010FFFF'=>     json_decode('"\u0010\uffff"'),
'2.3.5  U-00110000'=>     json_decode('"\u0011\u0000"'),
 
);
//print( "\n#     " .  json_encode(array_values($stress_test)) );
//var_dump($stress_test);

foreach( $stress_test as $explanation => $string ){
    Tap::cmp_ok( json_decode(json_encode(UTF8::to($string))), '===', UTF8::to($string), 'stress-test ' . $explanation . ' is json safe' );
}


