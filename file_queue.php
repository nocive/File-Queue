<?php

define( 'FILEQUEUE_SCRIPT_PATH', realpath( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'FILEQUEUE_QUEUE_PATH', FILEQUEUE_SCRIPT_PATH . 'queue' . DIRECTORY_SEPARATOR );
define( 'FILEQUEUE_QUEUE_JOBLOG', FILEQUEUE_QUEUE_PATH . '.jlog' );

class FileQueue
{
	protected $_defaults = array(
		'queue_path' => FILEQUEUE_QUEUE_PATH,
		'queue_working_path' => '%QPATH%/working/',
		'queue_complete_path' => '%QPATH%/complete/',
		'queue_archive_path' => '%QPATH%/archive/',
		'queue_file_format' => '%s',
		'queue_joblog' => FILEQUEUE_QUEUE_JOBLOG
	);

	protected $_config;

	protected static $_pathTypes = array(
		'base',
		'working',
		'complete',
		'archive'
	);

	const DIR_MODE = 0775;
	const FILE_MODE = 0666;


	public function __construct( $config = null )
	{
		if ($config !== null && ! is_array( $config )) {
			throw new InvalidArgumentException( '$config must be an array' );
		}

		$this->config( $config );

		foreach( $this->qpaths() as $path ) {
			if (! is_dir( $path ) && ! $this->_mkdir( $path )) {
				throw new RuntimeException( "Could not create queue path '$path', check permissions" );
			}

			if (! is_writable( $path )) {
				throw new RuntimeException( "Queue path '$path' is not writable, check permissions" );
			}
		}

		if (! is_file( $this->_config['queue_joblog'] ) && false === @touch( $this->_config['queue_joblog'] )) {
			throw new RuntimeException( "Failed to create job log file '{$this->_config['queue_joblog']}'" );
		}
	}


	public function config( $config = null )
	{
		$this->_config = $config !== null ? array_merge( $this->_defaults, $config ) : $this->_defaults;
		foreach( self::$_pathTypes as $ptype ) {
			$path = & $this->_config[$this->_qname( $ptype )];
			$path = rtrim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
			$path = str_replace( '%QPATH%', rtrim( $this->qpath( 'base' ), DIRECTORY_SEPARATOR ), $path );
		}
	}


	public function work( $limit = 10 )
	{
		$limit = (int) $limit;
		$output = array();
		// get flat files only ordered by modification time and limited by $limit
		// i rather exec() my way through this than using native php code
		// grep -m $limit was necessary to fix Broken pipe grep errors when combined with head
		$cmd = 'ls -tr1p ' . escapeshellarg( $this->qpath() ) . " 2>/dev/null | grep -v -m $limit /\\$ | head -n $limit";
		exec( $cmd, $output );
		return $output;
	}


	public function job()
	{
		$work = $this->work( 1 );
		if (! empty( $work ) && is_array( $work )) {
			return current( $work );
		}
		return false;
	}


	public function add( $uid, $payload, $dispatch = false )
	{
		$qfilename = $this->_qfilename( $uid );
		if ($dispatch !== false) {
			if (! is_callable( $dispatch )) {
				throw new InvalidArgumentException( '$dispatch must be a callable resource when not false' );
			}
			$qfilename = $this->_qfilename( $uid, 'working' );
		}

		if (! $this->_jlogaddnx( $uid )) {
			// a job with that uid already exists
			return -1;
		}

		if (false === ($fh = @fopen( $qfilename, 'x' )) || false === @file_put_contents( $qfilename, $this->_pack( $payload ) )) {
			// failed to lock or write payload to file
			return -1;
		}

		@fclose( $fh );
		@chmod( $qfilename, self::FILE_MODE );

		if ($dispatch !== false) {
			return $this->dispatch( $uid, $dispatch, $working = true );
		}
		return true;
	}


	public function dispatch( $uid, $callback, $working = false )
	{
		if (! is_callable( $callback )) {
			throw new InvalidArgumentException( '$callback must be a valid callable resource' );
		}

		$qfile = $this->_qfilename( $uid );
		$qcfile = $this->_qfilename( $uid, 'complete' );
		$qwfile = $this->_qfilename( $uid, 'working' );

		if ($working || $this->_rename( $qfile, $qwfile )) {
			if (false === ($payload = file_get_contents( $qwfile ))) {
				throw new Exception( 'Could not retrieve queue file contents' );
			}

			$payload = $this->_unpack( $payload );
			if (false !== $callback( $uid, & $payload )) {
				$moveto = $qcfile;
				$status = true;
			} else {
				$moveto = $qfile;
				$status = false;
			}
			$payload = $this->_pack( $payload );
			
			if (false === @file_put_contents( $qwfile, $payload )) {
				throw new Exception( 'Failed to update payload' );
			}
			if (! $this->_rename( $qwfile, $moveto )) {
				throw new Exception( "Could not move queue file '$qwfile' to '$moveto'" );
			}
			@chmod( $moveto, self::FILE_MODE );
			return $status;
		}
		return -1;
	}


	public function remove( $uid )
	{
		if (@unlink( $this->_qfilename( $uid ) )) {
			$this->_jlogremove( $uid );
			return true;
		}
		return false;
	}

	
	public function exists( $uid, $path = null )
	{
		return file_exists( $this->_qfilename( $uid, $path ) );
	}


	public function qpath( $type = null )
	{
		return $this->_config[$this->_qname( $type )];
	}


	public function qpaths()
	{
		$paths = array();
		foreach( self::$_pathTypes as $p) {
			$paths[$p] = $this->qpath( $p );
		}
		return $paths;
	}

	protected function _qname( $type = null )
	{
		return ! in_array( $type, self::$_pathTypes, true ) || $type === 'base' ? 'queue_path' : 'queue_' . $type . '_path';
	}


	protected function _qfilename( $uid, $path = null )
	{
		return $this->qpath( $path ) . sprintf( $this->_config['queue_file_format'], $uid );
	}


	protected function _pack( $data )
	{
		if (false === ($data = serialize( $data ))) {
			throw new Exception( 'Error encoding data' );
		}
		return $data;
	}


	protected function _unpack( $data )
	{
		if (false === ($data = unserialize( $data ))) {
			throw new Exception( 'Error decoding data' );
		}
		return $data;
	}


	protected function _rename( $src, $dest )
	{
		// php's rename doesn't allow not to overwrite if file exists
		// this works for *nix systems
		if (@link( $src, $dest )) {
			@unlink( $src );
			return true;
		} else {
			return false;
		}
	}


	protected function _mkdir( $path, $mode = self::DIR_MODE, $recursive = true )
	{
		$mode = $mode !== null ? $mode : self::DIR_MODE;
		$old = umask( 0 );
		$status = @mkdir( $path, $mode, $recursive );
		umask( $old );
		return $status;
	}


	protected function _jlogaddnx( $uid )
	{
		$output = array();
		$pattern = '^' . preg_quote( $uid ) . '$';
		$cmd = 'grep ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->_config['queue_joblog'] ) . ' 2>/dev/null || echo ' . escapeshellarg( $uid ) . ' >> ' . escapeshellarg( $this->_config['queue_joblog'] );
		exec( $cmd, $output );
		return count( $output ) === 0;
	}


	protected function _jlogremove( $uid )
	{
		$output = array();
		$pattern = '/^' . preg_quote( $uid ) . '$/d';
		$cmd = 'sed -i ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->_config['queue_joblog'] );
		exec( $cmd, $output );
		return true;
	}
}


?>
