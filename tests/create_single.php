<?php

require_once( '../file_queue.php' );

echo "wid: " . uniqid( true );

$jobs = 5000;
$fq = new FileQueue();

//$uid = '1234-123456-12345678901';
$uid = hash( 'sha1', uniqid( true ) );
$payload = array(
	'some',
	'random',
	'data',
	hash( 'crc32', uniqid( true ) ),
);

//$r = $fq->add( $uid, $payload, function ($uid,$payload) { echo serialize( $payload ); return true; } );
$r = $fq->add( $uid, $payload );
var_dump($r);

?>
