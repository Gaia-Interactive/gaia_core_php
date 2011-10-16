<?php
function apc_add($key, $value, $ttl = 0 ){ return FALSE; }
function apc_bin_dump(){ return ''; } // Get a binary dump of the given files and user variables
function apc_bin_dumpfile(){ return FALSE; } // Output a binary dump of cached files and user variables to a file
function apc_bin_load(){ return FALSE; } // Load a binary dump into the APC file/user cache
function apc_bin_loadfile(){ return FALSE; } // Load a binary dump from a file into the APC file/user cache
function apc_cache_info(){ return array(); } // Retrieves cached information from APC's data store
function apc_cas(){ return 0; } //
function apc_clear_cache(){ return FALSE; } // Clears the APC cache
function apc_compile_file(){ return FALSE; } // Stores a file in the bytecode cache, bypassing all filters.
function apc_dec(){ return FALSE; } // Decrease a stored number
function apc_define_constants(){ return FALSE; } // Defines a set of constants for retrieval and mass-definition
function apc_delete_file($v){  return is_array( $v ) ? array() : FALSE; } // Deletes files from the opcode cache
function apc_delete(){ return FALSE; } // Removes a stored variable from the cache
function apc_exists($v){ return is_array( $v ) ? array() : FALSE; } // Checks if APC key exists
function apc_fetch($v, & $success = FALSE ){ $success = FALSE; return is_array( $v ) ? array() : FALSE; } // Fetch a stored variable from the cache
function apc_inc(){ return FALSE; } // Increase a stored number
function apc_load_constants(){ return FALSE; } // Loads a set of constants from the cache
function apc_sma_info(){ return FALSE; } // Retrieves APC's Shared Memory Allocation information
function apc_store(){ return FALSE; } // Cache a variable in the data store

class APCIterator { // The APCIterator class

    public function __construct(){} // Constructs an APCIterator iterator object
    public function current(){ return 0; } // Get current item
    public function getTotalCount(){ return 0; } // Get total count
    public function getTotalHits(){ return 0; } // Get total cache hits
    public function getTotalSize(){ return 0; } // Get total cache size
    public function key(){ return FALSE; } // Get iterator key
    public function next(){ return FALSE; } // Move pointer to next item
    public function rewind(){ return FALSE; } // Rewinds iterator
    public function valid(){ return FALSE; } // Checks if current position is valid
}
