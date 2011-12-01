<?php
$usleep = isset( $_REQUEST['usleep'] ) ? $_REQUEST['usleep'] : 0;
$iterations = isset( $_REQUEST['iterations'] ) ? $_REQUEST['iterations'] : 1;
$size = isset( $_REQUEST['size'] ) ? $_REQUEST['size'] : 100;
$charset = '!@#$%^&*()\'\".,`;:/\[]{}|-_+=<>?' . implode('', array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9') ) );
$charset_len = strlen( $charset );
$start = microtime(TRUE);
for( $i = 0; $i < $iterations; $i++){
    if( $usleep ) usleep( $usleep );
    for( $ii=0; $ii < $size; $ii++) echo $charset[ mt_rand(0, $charset_len)];
    echo "\n";

}
$elapsed = number_format( microtime(TRUE) - $start, 5);

print "\ntime elapsed: $elapsed secs\n";