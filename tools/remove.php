<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );

$fq = new FileQueue();
$fq->remove( '3ccba07df704ecde89ec6f671ccd768d69b20a43' );

?>
