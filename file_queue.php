<?php

define( 'FILEQUEUE_SCRIPT_PATH', realpath( dirname( __FILE__ ) ) . '/' );
if (! defined( 'FILEQUEUE_QUEUE_PATH' )) {
	define( 'FILEQUEUE_QUEUE_PATH', FILEQUEUE_SCRIPT_PATH . 'queue/' );
}

/**
 * Base class
 * 
 * This class is extended by all all others
 * Shares some globally useful constants and functions
 * 
 * @package		FileQueue
 * @subpackage	FileQueueBase
 */
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
		$class = $this->_className( self::CLASS_JOB );
		return $var instanceof $class;
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
			return new $className( $args[0] );
		case 3:
			return new $className( $args[0], $args[1] );
		case 4:
			return new $className( $args[0], $args[1], $args[2] );
		case 5:
			return new $className( $args[0], $args[1], $args[2], $args[3] );
		default:
			throw new InvalidArgumentException( 'Unimplemented number of parameters' );
		}
	}


	protected function _className( $classAlias )
	{
		if (empty( self::$_classmap[$classAlias] )) {
			throw new InvalidArgumentException( "Class alias '$classAlias' is either not mapped or empty" );
		}
		return self::$_classmap[$classAlias];
	}
}

/**
 * Config class
 * 
 * Holds configuration shared across classes
 * 
 * @package		FileQueue
 * @subpackage	FileQueueConfig
 */
class FileQueueConfig extends FileQueueBase
{
	/**
	 * @var		FileQueueJobLog
	 * @access	protected
	 */
	protected $_joblog;
	
	/**
	 * @var		array
	 * @access	protected
	 */
	protected $_config = array();
	
	protected static $_defaults = array( 
		self::CFG_PATH_BASE => FILEQUEUE_QUEUE_PATH, 
		self::CFG_PATH_WORK => '%PATH%/work/', 
		self::CFG_PATH_WORKING => '%PATH%/working/', 
		self::CFG_PATH_COMPLETE => '%PATH%/complete/', 
		self::CFG_PATH_ARCHIVE => '%PATH%/archive/', 
		self::CFG_PATH_TMP => '%PATH%/tmp/', 
		self::CFG_JOBLOG => '%PATH%/.jlog', 
		self::CFG_FILE_FORMAT => 'job:%s' 
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


	public function reset()
	{
		$this->set( self::$_defaults );
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
		foreach ( self::$_pathmap as $pathAlias => $pathCfgName ) {
			$paths[$pathAlias] = $this->path( $pathAlias );
		}
		return $paths;
	}


	public function joblog()
	{
		$argc = func_num_args();
		if ($argc === 0) {
			return $this->_joblog;
		}
		$this->_joblog = func_get_arg( 0 );
	}

}

/**
 * Main class
 * 
 * Holds logic for getting and creating work
 * 
 * @package		FileQueue
 * @subpackage	FileQueue
 */
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
		
		foreach ( $this->config->paths() as $path ) {
			if (! is_dir( $path ) && ! $this->_mkdir( $path )) {
				throw new RuntimeException( "Could not create queue path '$path', check permissions" );
			}
			
			if (! is_writable( $path )) {
				throw new RuntimeException( "Queue path '$path' is not writable, check permissions" );
			}
		}
		
		// this must come after directory tree is created
		$this->config->joblog( $this->_new( self::CLASS_JOBLOG, $this->config->get( self::CFG_JOBLOG ) ) );
	}


	public function config( $config = null )
	{
		$this->config->set( $config );
	}


	public function work( $limit = 10 )
	{
		$workPath = $this->config->path( self::PATH_WORK );
		
		$limit = (int) $limit;
		$output = array();
		// get flat files only ordered by modification time and limited by $limit
		// i rather exec() my way through this than using native php code
		// grep -m $limit was necessary to fix Broken pipe grep errors when combined with head
		$cmd = 'ls -tr1p ' . escapeshellarg( $workPath ) . " 2>/dev/null | grep -v -m $limit /\\$ | head -n $limit";
		exec( $cmd, $output );
		
		$jobs = array();
		foreach ( $output as $line ) {
			$jobs[] = $this->_new( self::CLASS_JOB, $this->config, null, null, $workPath . $line );
		}
		return $jobs;
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

/**
 * Job class
 * 
 * Contains logic for handling jobs
 * 
 * @package		FileQueue
 * @subpackage	FileQueueJob
 */
class FileQueueJob extends FileQueueBase
{
	/**
	 * @var		FileQueueConfig
	 * @access	protected
	 */
	protected $_config;
	
	protected $_id;
	protected $_payload;
	protected $_file;
	protected $_status;
	protected $_path;
	protected $_valid = false;
	
	const STATUS_WORK = 'enqueued';
	const STATUS_WORKING = 'working';
	const STATUS_COMPLETE = 'complete';
	const STATUS_ARCHIVE = 'archived';
	const STATUS_TMP = 'temporary';
	const STATUS_UNKNOWN = 'unknown';
	
	protected static $_statuses = array( 
		self::PATH_WORK => self::STATUS_WORK, 
		self::PATH_WORKING => self::STATUS_WORKING, 
		self::PATH_COMPLETE => self::STATUS_COMPLETE, 
		self::PATH_ARCHIVE => self::STATUS_ARCHIVE, 
		self::PATH_TMP => self::STATUS_TMP 
	);


	public function __construct( $config, $id = null, $payload = null, $file = null )
	{
		$this->_config = $config;
		
		if ($id !== null) {
			$this->id( $id );
		}
		
		if ($payload !== null) {
			$this->payload( $payload );
		}
		
		if ($file !== null) {
			$this->load( $file );
		}
	}


	public function create( $id = null, $payload = null )
	{
		if ($id !== null) {
			$this->id( $id );
		}
		
		$id = $this->id();
		if (! $id) {
			return false;
		}
		
		if ($payload !== null) {
			$this->payload( $payload );
		}
		
		$file = $this->_filename( self::PATH_TMP );
		if (! $this->_config->joblog()->addnx( $file )) {
			return false;
		}
		
		$path = $this->_config->path( self::PATH_TMP );
		
		if ($this->store( $path . $file )) {
			$this->path( $path );
			$this->file( $file );
			$this->_status = self::STATUS_TMP;
			$this->_valid = true;
			return true;
		}
		$this->config->joblog()->remove( $this );
		return false;
	}


	public function load( $file )
	{
		if (! is_file( $file ) || ! is_writable( $file )) {
			return false;
		}
		
		if (false !== ($payload = @file_get_contents( $file ))) {
			$payload = $this->_unpack( $payload );
			if (empty( $payload['id'] ) || ! array_key_exists( 'data', $payload )) {
				throw new RuntimeException( 'Invalid or malformed payload' );
			}
			
			$path = dirname( $file );
			
			$this->id( $payload['id'] );
			$this->payload( $payload['data'] );
			$this->file( $file );
			$this->path( $path );
			$this->_status = $this->_statusFromPath( $path );
			$this->_valid = true;
			return true;
		}
		return false;
	}


	public function id()
	{
		if (func_num_args() === 0) {
			return $this->_id;
		}
		
		$this->_id = func_get_arg( 0 );
	}


	public function payload()
	{
		if (func_num_args() === 0) {
			return $this->_payload;
		}
		
		$this->_payload = func_get_arg( 0 );
	}


	public function file()
	{
		if (func_num_args() === 0) {
			return $this->_file;
		}
		
		$this->_file = basename( func_get_arg( 0 ) );
	}


	public function path()
	{
		if (func_num_args() === 0) {
			return $this->_path;
		}
		
		$this->_path = rtrim( func_get_arg( 0 ), '/' ) . '/';
	}


	public function enqueue()
	{
		if ($this->_valid && $this->_status !== self::STATUS_WORK) {
		
		}
		return false;
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
		if ($this->_valid) {
			return @unlink( $this->_path . $this->_file );
		}
		return false;
	}


	public function store( $file = null )
	{
		$file = $file === null ? $this->file() : $file;
		if (false !== ($status = @file_put_contents( $file, $this->_pack() ))) {
			@chmod( $file, self::FILE_MODE );
		}
		return $status;
	}


	public function move( $path )
	{
		$src = $this->path() . $this->file();
		$dst = $this->_config->path( $path ) . $this->file();
		return @rename( $src, $dst );
	}


	/*public function lock()
	{

	}


	public function unlock()
	{
	}*/
	
	protected function _filename()
	{
		return sprintf( $this->_config->get( self::CFG_FILE_FORMAT ), $this->id() );
	}


	protected function _pack( $data = null )
	{
		$data = $data !== null ? $data : $this->payload;
		$data = array( 
			'id' => $this->id(), 
			'data' => $data 
		);
		if (false === ($data = serialize( $data ))) {
			throw new RuntimeException( 'Error encoding data' );
		}
		return $data;
	}


	protected function _unpack( $data = null )
	{
		$data = $data !== null ? $data : $this->payload;
		if (false === ($data = unserialize( $data ))) {
			throw new RuntimeException( 'Error decoding data' );
		}
		return $data;
	}


	protected function _statusFromPath( $path )
	{
		$path = rtrim( $path, '/' ) . '/';
		foreach ( $this->_config->paths() as $pathAlias => $pathCfgName ) {
			if ($path === $this->_config->path( $pathAlias )) {
				return self::$_statuses[$pathAlias];
			}
		}
		return self::STATUS_UNKNOWN;
	}
}

/**
 * Job log class
 * 
 * Handles file queue job log, which is basically a
 * text file containing all job id's for job tracking
 * 
 * @package		FileQueue
 * @subpackage	FileQueueBase
 */
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
		$return = null;
		$pattern = '/^' . preg_quote( $job ) . '$/d';
		$cmd = 'sed -i ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->file );
		exec( $cmd, $output, $return );
		
		return true;
	}


	public function exists( $job )
	{
		$job = $this->_isJob( $job ) ? $job->id() : $job;
		if (! is_string( $job )) {
			throw new InvalidArgumentException( '$job must be either a string or a job object' );
		}
		
		$output = array();
		$return = null;
		$pattern = '^' . preg_quote( $job ) . '$';
		$cmd = 'grep ' . escapeshellarg( $pattern ) . ' ' . escapeshellarg( $this->file );
		exec( $cmd, $output, $return );
		
		return $return === 0;
	}
}

?>
