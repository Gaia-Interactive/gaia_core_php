<?php

use Gaia\Test\Tap;

Tap::ok( $cookie instanceof Gaia\StorageIface, 'cookie is an instance of storageiface');
Tap::is( $cookie->get( $key = 'test'. mt_rand(0, 100000) ), FALSE, 'get non-existent key, returns false');
Tap::is( isset( $cookie->$key ), FALSE, 'magic isset approach returns false too');
Tap::is( $cookie->set( $key, $value = 5), TRUE, 'setting the key returns true');
Tap::is( $cookie->get( $key), $value, 'getting the key returns 5');
Tap::is( $cookie->$key, $value, 'getting using magic method approach works too');
Tap::is( $cookie->delete( $key ), TRUE, 'deleting the cookie returns true');
Tap::is( $cookie->add($key, $value), TRUE, 'adding a cookie works since nothing stored there.');
Tap::is( $cookie->add( $key, $value), FALSE, 'adding the cookie the second time fails since a key exists');
Tap::is( $cookie->$key, $value, 'getting the cookie returns expected response');
Tap::is( $cookie->replace( $key, $value = 6), TRUE, 'replacing an existing value returns true');
Tap::is( $cookie->$key, $value, 'getting the cookie returns replaced response');
Tap::is( $cookie->replace('non-existent-key', 1), FALSE, 'replacing non-existent key returns false');
Tap::is( $cookie->increment( $key ), ++$value, 'incrementing the key returns the new value');
Tap::is( $cookie->increment( $key, 10 ), $value += 10, 'incrementing the key by 10 returns the new value');
Tap::is( $cookie->increment('non-existent-key'), FALSE, 'incrementing non-existent key returns false');
Tap::is( $cookie->$key, $value, 'get returns correct value after increment');
Tap::is( $cookie->decrement( $key ), --$value, 'decrementing the key returns the new value');
Tap::is( $cookie->decrement( $key, 10 ), $value -= 10, 'incrementing the key by 10 returns the new value');
Tap::is( $cookie->$key, $value, 'get returns correct value after decrement');
Tap::is( $cookie->decrement('non-existent-key'), FALSE, 'decrementing non-existent key returns false');
