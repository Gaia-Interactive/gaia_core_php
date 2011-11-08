<?php
// autoload yaml
spl_autoload_register(function($class) {
    $class = strtolower($class);
    if( $class == 'sfyaml' ) @include  __DIR__ . '/../yaml/lib/sfYaml.php';
});