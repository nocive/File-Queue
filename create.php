<?php

require_once( 'file_queue.php' );

echo "wid: " . uniqid( true );

$jobs = 5000;
$fq = new FileQueue();

for($i=0; $i<$jobs; $i++) {
	$uid = hash( 'sha1', uniqid( true ) );
	$payload = array(
		'some',
		'random',
		'data',
		hash( 'crc32', uniqid( true ) ),
	);

	echo "adding job id: $uid\n";
	$fq->add( $uid, $payload );
	//usleep(100000);
}

?>
