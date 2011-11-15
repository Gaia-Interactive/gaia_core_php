<form action="<?php echo $this->instance(array('view'=>$this->action) )->dispatch(__DIR__ . '/selfurl.php'); ?>" method="<?php echo $this->method;?>" >
<input name="name" type="text" /> <input type="submit" value="Go!" />
</form>