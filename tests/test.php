<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );
//$fq = new FileQueue();

$config = new FileQueueConfig();

var_dump($config);
var_dump($config->path());
var_dump($config->paths());


?>
