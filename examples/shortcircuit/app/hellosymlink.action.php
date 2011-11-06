<?php 
use Gaia\ShortCircuit;

ShortCircuit::dispatch('hello/' . implode('/', $this->request()->args()));
return ShortCircuit::ABORT;
