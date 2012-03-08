<?php


require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );
$loop = false;


ini_set( 'max_execution_time', 0 );
$workerId = uniqid();
$fq = new FileQueue();

$callback = function ($uid, $payload) {
	sleep(1);
	echo "payload: " . serialize( $payload ) . "; ";
	return true;
};

while (true) {

	$start = microtime( true );

	ob_start();
	echo "wid: $workerId >> ";

	$sleep = false;
	$job = $fq->job();
	if (! $job) {
		echo 'no jobs found, sleeping';
		$sleep = 10000000;
	} else {
		echo "got job id: {$job->id()}; ";
		if (-1 !== ($status = $job->dispatch( $callback ))) {
			echo "dispatch status: " . var_export( $status, true ) . '; ';
		} else {
			echo 'job was locked by another worker or is no longer available';
		}
		$sleep = 500000;
	}
	echo " | took: " . round( (float) microtime( true ) - $start, 4 ) . " s \n";
	ob_end_flush();

	if (! $loop) {
		break;
	}

	if ($sleep) {
		usleep( $sleep );
		$sleep = false;
	}
}

?>
