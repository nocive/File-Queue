<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );

echo "wid: " . uniqid( true );

$jobs = 5000;
$fq = new FileQueue();

for($i=0; $i<$jobs; $i++) {
	$id = hash( 'sha1', uniqid( true ) );
	$payload = array(
		'some',
		'random',
		'data',
		hash( 'crc32', uniqid( true ) ),
	);

	echo "adding job id: $id\n";
	$fq->add( $id, $payload, $enqueue = true );
	//usleep(100000);
}

?>
