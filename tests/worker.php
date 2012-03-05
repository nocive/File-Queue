<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );
ini_set( 'max_execution_time', 0 );

$fq = new FileQueue();

$callback = function ($uid, $payload) {
	sleep(1);
	echo "payload: " . serialize( $payload ) . "; ";
	return true;
};

while (true) {
	ob_start();
	echo 'wid: ' . uniqid() . ' >> ';

	$maxRetries = 5;
	$retries = 0;
	do {
		$job = $fq->job();
		if (! $job) {
			echo 'no jobs found, sleeping';
			sleep( 10 );
			break;
		}

		echo "got job id: $job; ";

		if (-1 !== ($status = $fq->dispatch( $job, $callback))) {
			echo "dispatch status: " . var_export( $status, true ) . '; ';
			break;
		} else {
			echo 'job was locked by another worker, will retry... ';
		}
		usleep( 100000 );
		$retries++;
	} while ($retries < $maxRetries);

	if ($retries >= $maxRetries) {
		echo "failed after $maxRetries retries";
	} else {
		sleep( 5 );
	}
	echo "\n";

	ob_end_flush();
}

?>
