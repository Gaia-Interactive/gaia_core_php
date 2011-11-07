<?php 
use Gaia\ShortCircuit;

ShortCircuit::dispatch('hello/echo');
return ShortCircuit::ABORT;
