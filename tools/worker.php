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
	ob_start();
	echo "wid: $workerId >> ";

	$maxRetries = 5;
	$retries = 0;
	$sleep = false;
	do {
		$job = $fq->job();
		if (! $job) {
			echo 'no jobs found, sleeping';
			$sleep = 10000000;
			break;
		} elseif (! $job->valid()) {
			echo 'job is no longer available, will retry...';
			$sleep = 500000;
			//break;
		} else {
			echo "got job id: {$job->id()}; ";
			if (-1 !== ($status = $job->dispatch( $callback))) {
				echo "dispatch status: " . var_export( $status, true ) . '; ';
				break;
			} else {
				echo 'job was locked by another worker or is no longer available, will retry... ';
			}
			$sleep = 500000;
		}
		$retries++;
	} while ($retries < $maxRetries);

	if ($retries >= $maxRetries) {
		echo "failed after $maxRetries retries";
	} else {
		$sleep = 5000000;
	}
	echo "\n";
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
