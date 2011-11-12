<?php
use Gaia\Test\Tap;

define('BASEPATH', __DIR__ . '/../../vendor/CodeIgniter/system/');
define('APPPATH', __DIR__ . '/../db/lib/codeigniter/app/');
@include BASEPATH . 'database/DB.php';
@include BASEPATH . 'core/Common.php';

if( ! function_exists('DB') ){
	Tap::plan('skip_all', 'CodeIgniter database library not loaded');
}
