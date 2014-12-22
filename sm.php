<?php
require_once('lib.php');
require_once('config.php');


/* call on the donkey to do his work */
$sm = new sm($config);
/* you can just have the output echoed by using 'echo' instead of 'csv' */
$sm->process('csv');
?>
