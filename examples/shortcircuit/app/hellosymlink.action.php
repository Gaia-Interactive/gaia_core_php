<?php 
use Gaia\ShortCircuit\Router;

Router::dispatch('hello/' . implode('/', $this->request()->getArgs()));
return Router::ABORT;
