<?php

require_once( realpath( dirname( __FILE__ ) . '/..' ) . '/file_queue.php' );

ob_start();
echo 'thread id: ' . uniqid( true ) . ' | ';

$fq = new FileQueue();

$uid = '1234-123456-12345678901';
//$uid = hash( 'sha1', uniqid( true ) );
$payload = array(
	'some',
	'random',
	'data',
	hash( 'crc32', uniqid( true ) ),
);

//$r = $fq->add( $uid, $payload, function ($uid,$payload) { echo serialize( $payload ); return true; } );
$r = $fq->add( $uid, $payload );
echo 'return: ' . var_export($r, true) . "\n";

ob_end_flush();

?>
