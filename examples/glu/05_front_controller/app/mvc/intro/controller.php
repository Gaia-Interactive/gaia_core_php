<?php
$this->data = $this->instance($this->request)->dispatch(__DIR__ . '/model.php');
return $this->dispatch(__DIR__ .'/view.php');

// EOF
