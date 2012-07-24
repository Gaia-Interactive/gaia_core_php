<?php
set_time_limit(0);
$vars = array('ct', 'add_ct', 'set_ct', 'replace_ct', 'get_ct', 'ttl', 'data');
$opts = array();
foreach( $vars as $v ) {
    $opts[] = $v . '::';
    $$v = '';
}
$options = getopt('', $opts);
extract( $options );

if( ! strlen( $ct ) ) $ct = 10000;
if( ! strlen( $add_ct ) ) $add_ct = $ct;
if( ! strlen( $set_ct ) ) $set_ct = $ct;
if( ! strlen( $replace_ct ) ) $replace_ct = $ct;
if( ! strlen( $get_ct ) ) $get_ct = $ct;
if( ! strlen( $ttl ) ) $ttl = 300;
if( ! strlen( $data ) ) $data = 'test_string';


$data = print_r( $data, TRUE);
$start = microtime(TRUE);



print "\nADDING: $add_ct\n";
for( $i = 0; $i < $add_ct; $i++){
    if( $i % 1000 == 0 ) print ".";
    $store->set( $i, $data, $ttl );
}
$end = microtime(TRUE);
$elapsed = number_format( $end - $start, 5, '.', '');
print "\nADD: $elapsed s\n";
$start = microtime(TRUE);

print "\nSETTING: $set_ct\n";
for( $i = 0; $i < $set_ct; $i++){
    if( $i % 1000 == 0 ) print ".";
    $store->set($i, $data, $ttl );
}
$end = microtime(TRUE);
$elapsed = number_format( $end - $start, 5, '.', '');
print "\nSET: $elapsed s\n";
$start = microtime(TRUE);


print "\nREPLACING: $replace_ct\n";
for( $i = 0; $i < $replace_ct; $i++){
    if( $i % 1000 == 0 ) print ".";
    $store->replace($i, $data, $ttl );
}
$end = microtime(TRUE);
$elapsed = number_format( $end - $start, 5, '.', '');
print "\nREPLACE: $elapsed s\n";
$start = microtime(TRUE);


print "\nGETTING: $get_ct\n";
for( $i = 0; $i < $get_ct; $i++){
    if( $i % 1000 == 0 ) print ".";
    $store->set( $i, $data, $ttl );
}
$end = microtime(TRUE);
$elapsed = number_format( $end - $start, 5, '.', '');
print "\nGET: $elapsed s\n";
$start = microtime(TRUE);
