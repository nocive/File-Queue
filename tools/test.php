<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );
$fq = new FileQueue();

$job = $fq->add(null,array(1,2,3),true);

var_dump( $job );

$job->dispatch( function( $id, $payload ) {
	$payload[] = uniqid('meh', true);
	echo serialize( $payload );
	return true;
});

var_dump( $job );











//$job = $fq->job();
//var_dump($job);
//$fqj = new FileQueueJob( $fq->config );
//$fqj->create();

/*$fqj->create();
var_dump($fqj);
$fqj->enqueue();
$fqj->complete();
$fqj->archive();
var_dump($fqj);*/

//$fqj->load( '/home/nocive/git/File-Queue/queue/tmp/job-uid-1234-12345' );

//var_dump($fqj);
//var_dump($r);


?>
