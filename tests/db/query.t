#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DB\Query;
Tap::plan(8);
Tap::is(Query::format('test=%i', array(1) ), "test=1", 'inject query param by sprintf rule');
Tap::is(Query::format('test=?', array(1) ), "test='1'", 'inject query param by question mark with equals sign next to it');
Tap::is(Query::format('test>?', array(1) ), "test>'1'", 'inject query param by question mark with GT sign next to it');
Tap::is(Query::format('test<?', array(1) ), "test<'1'", 'inject query param by question mark with LT sign next to it');
Tap::is(Query::format('test in (?)', array(1) ), "test in ('1')", 'inject query param by question mark wrapped in parenthesis');
Tap::is(Query::format('test=:id', array('id'=>1) ), "test='1'", 'inject query param by name');
Tap::is(Query::format('test=:id AND test2 = :2k', array('id'=>1, '2k'=>1) ), "test='1' AND test2 = '1'", 'inject multiple query params by name');
Tap::is(Query::format('test %%s ?, (?,?),(:magic?)', array(array(1, 2), 3, 4, 'magic?'=>'test')), "test %s '1', '2', ('3','4'),('test')", 'format complex query string');

