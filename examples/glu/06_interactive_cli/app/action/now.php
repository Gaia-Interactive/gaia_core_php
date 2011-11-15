<?php
// setup the timezone
$timezone = $this->timezone;
if( ! $timezone ) $timezone = 'GMT';
date_default_timezone_set($timezone);

// setup the time to be used
$now = $this->now;
if( ! $now ) $now = time();

// pick the date format
$format = $this->format;
if( ! $format ) $format = 'Y/m/d H:i:s e';
?>
The time is: <?php echo  date($format, $now); ?>
