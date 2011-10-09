<?php 
use Gaia\ShortCircuit\Router;

Router::dispatch('hello/' . implode('/', $this->request()->args()));
return Router::ABORT;
