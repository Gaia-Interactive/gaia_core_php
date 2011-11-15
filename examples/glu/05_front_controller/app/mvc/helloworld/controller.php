<?php
// let's call the helloworld model
$this->data = $this->instance($this->request)->dispatch(__DIR__ . '/model.php');

// render the view
$this->dispatch(__DIR__ . '/view.php');

// EOF