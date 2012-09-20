<?php
use Gaia\Test\Tap;

define('BASEPATH', 'phar://' . __DIR__ . '/../../vendor/codeigniter.phar/system/');
define('APPPATH', __DIR__ . '/../db/lib/codeigniter/app/');
@include BASEPATH . 'database/DB.php';
@include BASEPATH . 'core/Common.php';

if( ! function_exists('DB') ){
	Tap::plan('skip_all', 'CodeIgniter database library not loaded');
}
