<?php
// sit in an loop, running dispatch, and read from STDIN
while( $this->dispatch(__DIR__ . '/run') ) {
    $this->line = trim( fgets( $this->STDIN ) );
}

// EOF