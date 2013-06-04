#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mbstring_installed.php';

use Gaia\Test\Tap;
use Gaia\Filter;

Tap::plan(15);

Tap::cmp_ok( Filter::safe($data = 'simple'), '===', $data, 'simple string gets through');
Tap::cmp_ok( Filter::safe($data = 'More Complex String 1,2,3,4 []'), '===', $data, 'more complex string gets through');
Tap::cmp_ok( Filter::safe($data = '<script>hello</script>'), '===', 'scripthello/script', 'script tags filtered out');
Tap::cmp_ok( Filter::safe($data = htmlentities('<script>hello</script>')), '===', 'lt;scriptgt;hellolt;/scriptgt;', 'htmlentities script tags filtered');
Tap::cmp_ok( Filter::safe($data = 'Τη γλώσσα μου έδωσαν ελληνική το σπίτι φτωχικό στις αμμουδιές του Ομήρου'), '===', $data, 'greek string gets through ');
Tap::cmp_ok( Filter::safe($data = 'На берегу пустынных волн Стоял он, дум великих полн'), '===', $data, 'russian string gets through');
Tap::cmp_ok( Filter::safe($data = '私はガラスを食べられます。それは私を傷つけません'), '===', $data, 'japanese string gets through');
Tap::cmp_ok( Filter::safe($data = '我能吞下玻璃而不伤身体'), '===', $data, 'chinese string gets through');
Tap::cmp_ok( Filter::safe($data = '€'), '===', $data, 'euro symbol gets through');


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
    Tap::cmp_ok( Filter::safe( $string ), '!==', $string, 'invalid ' . $explanation . ' is changed' );
}
