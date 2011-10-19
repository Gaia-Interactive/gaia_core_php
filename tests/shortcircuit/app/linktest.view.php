<?php
$r = $this->request();
$params = array('a'=>$r->a, 'b'=>$r->b, 'c'=>$r->c);
?>
<a href="<?php echo $this->link('linktest', $params); ?>">linktest</a>

