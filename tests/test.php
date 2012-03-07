<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );
$fq = new FileQueue();

//var_dump($fq->config->paths());


//var_dump($fq->config->joblog()->exists( 'job:123' ));
var_dump($fq->config->joblog()->remove( 'job:123' ));
//$job = $fq->job();
//var_dump($job->move( FileQueue::PATH_COMPLETE ));

//$fqj = new FileQueueJob( $fq->config );
//$fqj->load( '/home/nocive/git/File-Queue/queue/tmp/myjobid-123' );
//$fqj->create();
//$r = $fqj->create( '123' );
//$fqj->load( '/home/nocive/git/File-Queue/queue/tmp/job-uid-1234-12345' );

//var_dump($fqj);
//var_dump($r);


?>
