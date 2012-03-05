<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );
$fq = new FileQueue();
var_dump($fq->add( 'job12345' ));


?>
