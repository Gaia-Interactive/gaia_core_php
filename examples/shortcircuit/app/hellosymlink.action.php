<?php 
use Gaia\ShortCircuit\Router;

Router::dispatch('hello/' . implode('/', Router::request()->get('__args__')));
return Router::ABORT;
