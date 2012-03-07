<?php

define( 'FILEQUEUE_SCRIPT_PATH', realpath( dirname( __FILE__ ) ) . '/' );
if (! defined( 'FILEQUEUE_QUEUE_PATH' )) {
	define( 'FILEQUEUE_QUEUE_PATH', FILEQUEUE_SCRIPT_PATH . 'queue/' );
}


class FileQueueBase
{
	const CFG_PATH_BASE = 'base_path';
	const CFG_PATH_WORK = 'work_path';
	const CFG_PATH_WORKING = 'working_path';
	const CFG_PATH_COMPLETE = 'complete_path';
	const CFG_PATH_ARCHIVE = 'archive_path';
	const CFG_PATH_TMP = 'tmp_path';
	const CFG_FILE_FORMAT = 'file_format';
	const CFG_JOBLOG = 'joblog';
	
	const PATH_BASE = 'base';
	const PATH_WORK = 'work';
	const PATH_WORKING = 'working';
	const PATH_COMPLETE = 'complete';
	const PATH_ARCHIVE = 'archive';
	const PATH_TMP = 'tmp';
	
	const CLASS_JOB = 'job';
	const CLASS_JOBLOG = 'joblog';
	const CLASS_CONFIG = 'config';

	const DIR_MODE = 0775;
	const FILE_MODE = 0666;

	protected static $_classmap = array(
		self::CLASS_JOB => 'FileQueueJob',
		self::CLASS_JOBLOG => 'FileQueueJobLog',
		self::CLASS_CONFIG => 'FileQueueConfig'
	);

	
	public function __construct()
	{
	}


	protected function _isJob( $var )
	{
		$jobclass = self::CLASS_JOB;
		return $var instanceof $jobclass;
	}


	protected function _new( $classAlias, $args )
	{
		$args = func_get_args();
		$argc = func_num_args();

		if ($argc < 1) {
			throw new InvalidArgumentException( 'Wrong number of parameters' );
		}

		$classAlias = array_shift( $args );
		$className = isset( self::$_classmap[$classAlias] ) ? self::$_classmap[$classAlias] : null;

		if (! $className) {
			throw new InvalidArgumentException( "Invalid class alias '$classAlias'" );
		}

		switch ($argc) {
		case 1:
			return new $className();
		case 2:
			return new $className( $args[1] );
		case 3:
			return new $className( $args[1], $args[2] );
		case 4:
			return new $className( $args[1], $args[2], $args[3] );
		case 5:
			return new $className( $args[1], $args[2], $args[3], $args[4] );
		default:
			throw new InvalidArgumentException( 'Unimplemented number of parameters' );
		}
	}
}


class FileQueueConfig extends FileQueueBase
{
	protected $_config = array();
	
	protected static $_defaults = array( 
		self::CFG_PATH_BASE => FILEQUEUE_QUEUE_PATH, 
		self::CFG_PATH_WORK => '%PATH%/work/',
		self::CFG_PATH_WORKING => '%PATH%/working/', 
		self::CFG_PATH_COMPLETE => '%PATH%/complete/', 
		self::CFG_PATH_ARCHIVE => '%PATH%/archive/', 
		self::CFG_PATH_TMP => '%PATH%/tmp/', 
		self::CFG_JOBLOG => '%PATH%/.jlog', 
		self::CFG_FILE_FORMAT => '%s' 
	);
	
	protected static $_pathmap = array( 
		self::PATH_BASE => self::CFG_PATH_BASE, 
		self::PATH_WORK => self::CFG_PATH_WORK, 
		self::PATH_WORKING => self::CFG_PATH_WORKING, 
		self::PATH_COMPLETE => self::CFG_PATH_COMPLETE, 
		self::PATH_ARCHIVE => self::CFG_PATH_ARCHIVE, 
		self::PATH_TMP => self::CFG_PATH_TMP 
	);


	public function __construct( $cfg = null )
	{
		$cfg = $cfg !== null ? array_merge( self::$_defaults, $cfg ) : self::$_defaults;
		$this->set( $cfg );
	}


	public function get()
	{
		if (func_num_args() === 0) {
			throw new InvalidArgumentException( 'Wrong number of arguments' );
		}
		
		$args = func_get_args();
		if (func_num_args() === 1) {
			if (is_array( $args[0] )) {
				$args = $args[0];
			} else {
				return $this->_config[$args[0]];
			}
		}
		
		$return = array();
		foreach ( $args as $a ) {
			$return[$a] = $this->_config[$a];
		}
		return $return;
	}


	public function set()
	{
		$args = func_get_args();
		$argc = func_num_args();
		
		if ($argc !== 1 && $argc !== 2) {
			throw new InvalidArgumentException( 'Wrong number of parameters' );
		}
		
		if ($argc === 1 && is_array( $args[0] )) {
			$vars = array_combine( array_keys( $args[0] ), array_values( $args[0] ) );
		} elseif ($argc === 2) {
			$vars = array_combine( array( 
				$args[0] 
			), array( 
				$args[1] 
			) );
		}
		
		foreach ( $vars as $var => $value ) {
			if (array_key_exists( $var, self::$_defaults )) {
				if ($var !== self::CFG_PATH_BASE) {
					if (in_array( $var, self::$_pathmap, true )) {
						$value = str_replace( '%PATH%', rtrim( $this->_config[self::CFG_PATH_BASE], '/' ), rtrim( $value, '/' ) . '/' );
					} elseif ($var === self::CFG_JOBLOG) {
						$value = str_replace( '%PATH%', rtrim( $this->_config[self::CFG_PATH_BASE], '/' ), $value );
					}
				}
				$this->_config[$var] = $value;
			}
		}
	}


	public function path( $type = self::PATH_BASE )
	{
		if (array_key_exists( $type, self::$_pathmap ) && isset( $this->_config[self::$_pathmap[$type]] )) {
			return $this->_config[self::$_pathmap[$type]];
		}
		return false;
	}


	public function paths()
	{
		$paths = array();
		foreach ( self::$_pathmap as $ptype => $ptypecfg ) {
			$paths[$ptype] = $this->path( $ptype );
		}
		return $paths;
	}


	public function reset()
	{
		$this->set( self::$_defaults );
	}
}


class FileQueue extends FileQueueBase
{
	/**
	 * @var FileQueueConfig
	 * @access public
	 */
	public $config;
	
	/**
	 * @var FileQueueJobLog
	 * @access protected
	 */
	protected $_joblog;


	public function __construct( $config = null )
	{
		if (strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN') {
			throw new RuntimeException( 'This class is only meant to be run on *nix systems' );
		}
		
		if ($config !== null && ! is_array( $config )) {
			throw new InvalidArgumentException( '$config must be an array' );
		}
		
		$this->config = $this->_new( self::CLASS_CONFIG, $config );
		$this->_joblog = $this->_new( self::CLASS_JOBLOG, $this->_config[self::CFG_JOBLOG] );
		
		foreach ( $this->config->paths() as $path ) {
			if (! is_dir( $path ) && ! $this->_mkdir( $path )) {
				throw new RuntimeException( "Could not create queue path '$path', check permissions" );
			}
			
			if (! is_writable( $path )) {
				throw new RuntimeException( "Queue path '$path' is not writable, check permissions" );
			}
		}
		
	}


	public function config( $config = null )
	{
		$this->config->set( $config );
	}
	
	public function work( $limit = 10 )
	{
		$limit = (int) $limit;
		$output = array();
		// get flat files only ordered by modification time and limited by $limit
		// i rather exec() my way through this than using native php code
		// grep -m $limit was necessary to fix Broken pipe grep errors when combined with head
		$cmd = 'ls -tr1p ' . escapeshellarg( $this->_config->path() ) . " 2>/dev/null | grep -v -m $limit /\\$ | head -n $limit";
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


	public function add( $id, $payload = null, $enqueue = false )
	{
		if (! $this->_joblog->addnx( $id )) {
			// failed to add job to job log, probably because a job with that id already exists
			return - 1;
		}
		
		//$jfilename = $this->_filename( $this->_config[self::CFG_PATH_TMP], $id );
		//$jobclass = self::CLASS_JOB;
		//$job = new $jobclass( $id, $payload );
		//if (! $job->store( $jfilename )) {
		//	$this->_joblog()->remove( $id );
		//	return - 1;
		//}
		
		if ($enqueue) {
		}
		return true;
	}


	/*public function add( $uid, $payload, $dispatch = false )
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
	}*/
	
	/*public function remove( $uid )
	{
		if (@unlink( $this->_qfilename( $uid ) )) {
			$this->_jlogremove( $uid );
			return true;
		}
		return false;
	}*/
	
	/*public function exists( $uid, $path = null )
	{
		return file_exists( $this->_qfilename( $uid, $path ) );
	}*/
	
	protected function _filename( $path, $job )
	{
		$job = $this->_isJob( $job ) ? $job->id() : $job;
		if (! is_string( $job )) {
			throw new InvalidArgumentException( '$job must be either a string or a job object' );
		}
		return $path . sprintf( $this->_config[self::CFG_FILE_FORMAT], $job );
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
}


class FileQueueJob extends FileQueueBase
{
	const STATUS_ENQUEUED = 'enqueued';
	const STATUS_WORKING = 'working';
	const STATUS_COMPLETE = 'complete';
	const STATUS_ARCHIVED = 'archived';
	const STATUS_TMP = 'temporary';

	public $id;
	public $payload;
	public $file;
	public $status;
	

	public function __construct( $config, $id, $payload = null )
	{
		$this->id = $id;
		$this->payload = $payload;
	}


	public function create( $payload = null )
	{
	}


	public function load( $file )
	{
	}


	public function id()
	{
		if (func_num_args() === 0) {
			return $this->id;
		}
		
		$this->id = func_get_arg( 0 );
	}


	public function payload()
	{
		if (func_num_args() === 0) {
			return $this->payload;
		}
		
		$this->payload = func_get_arg( 0 );
	}


	public function enqueue()
	{
	}


	public function dispatch()
	{
	}


	public function complete()
	{
	}


	public function archive()
	{
	}


	public function remove()
	{
		if ($this->file) {
			return @unlink( $this->file );
		}
		return false;
	}


	public function store( $file )
	{
		if (false !== ($status = @file_put_contents( $file, $this->_pack() ))) {
			@chmod( $file, self::FILE_MODE );
		}
		return $status;
	}


	public function move( $dest )
	{
	}


	protected function _pack()
	{
		if (false === ($data = serialize( $this->payload ))) {
			throw new Exception( 'Error encoding data' );
		}
		return $data;
	}


	protected function _unpack()
	{
		if (false === ($data = unserialize( $this->payload ))) {
			throw new Exception( 'Error decoding data' );
		}
		return $data;
	}


	public function lock()
	{
		/*if (is_resource( $this->_lockh)) {
			// already locked in this instance
			return false;
		}
		return false !== ($this->_lockh = @fopen( $this->file, 'x' ));*/
	}


	public function unlock()
	{
	}
}

class FileQueueJobLog extends FileQueueBase
{
	public $file;


	public function __construct( $file )
	{
		if (empty( $file )) {
			throw new InvalidArgumentException( '$file cannot be empty' );
		}
		
		if (! is_file( $file )) {
			if (! @touch( $file )) {
				throw new RuntimeException( "Could not create joblog file '$file'" );
			}
			@chmod( $this->file, self::FILE_MODE );
		}
		$this->file = $file;
	}


	public function addnx( $job )
	{
		$job = $this->_isJob( $job ) ? $job->id() : $job;
		if (! is_string( $job )) {
			throw new InvalidArgumentException( '$job must be either a string or a job object' );
		}
		
		$output = array();
		$pattern = '^' . preg_quote( $job ) . '$';
		$cmd = 'grep ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->file ) . ' 2>/dev/null || echo ' . escapeshellarg( $job ) . ' >> ' . escapeshellarg( $this->file );
		exec( $cmd, $output );
		return count( $output ) === 0;
	}


	public function remove( $job )
	{
		$job = $this->_isJob( $job ) ? $job->id() : $job;
		if (! is_string( $job )) {
			throw new InvalidArgumentException( '$job must be either a string or a job object' );
		}
		
		$output = array();
		$pattern = '/^' . preg_quote( $job ) . '$/d';
		$cmd = 'sed -i ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->file );
		exec( $cmd, $output );
		return true;
	}


	public function exists( $job )
	{
		$job = $this->_isJob( $job ) ? $job->id() : $job;
		if (! is_string( $job )) {
			throw new InvalidArgumentException( '$job must be either a string or a job object' );
		}

		$output = array();
		$pattern = '/^' . preg_quote( $job ) . '$/d';
		//$cmd = 'grep ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->file );
		//exec( $cmd, $output );
		//return true;
	}
}

?>
