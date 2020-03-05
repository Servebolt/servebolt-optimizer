<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Logviewer
 */
class Servebolt_Logviewer {

	/**
	 * @var int The number of log entries to display.
	 */
	private $number_of_entries = 100;

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return Servebolt_Logviewer|null
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Logviewer;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_Logviewer constructor.
	 */
	private function __construct() {}

	/**
	 * Display error log.
	 */
	public function view() {
		$log_dir = str_replace('/public', '/logs', $_SERVER['DOCUMENT_ROOT']);
		$log_file_path = $log_dir. '/ErrorLog';
		$log_file_exists = file_exists($log_file_path);
		$log_file_readable = is_readable($log_file_path);
		$log = $this->tail($log_file_path, $this->number_of_entries);
		$entries = $this->prepare_entries($log);
		sb_view('admin/views/log-viewer', compact('log_file_path', 'log_file_exists', 'log_file_readable', 'log', 'entries'));
	}

	/**
	 * Parse log entry line.
	 *
	 * @param $entry
	 *
	 * @return object
	 */
	private function parse_line($entry) {
		preg_match("/^\[(.*)\] (\[.*\] )(\[.*\] )(\[.*Client:([\d\.\:]+):.*\] )(.*)$/Ui", $entry, $matches);
		$unixtime = strtotime($matches[1]);
		return (object) [
			'date'  => date('H:i:s', $unixtime),
			'ip'    => $matches[5],
			'error' => $matches[6],
		];
	}

	/**
	 * Prepare entries from log file.
	 *
	 * @param $log
	 *
	 * @return array
	 */
	private function prepare_entries($log) {
		$lines = explode(PHP_EOL, $log);
		$lines = array_reverse($lines);
		$lines = array_filter($lines);
		$lines = array_map(function ($line) {
			return $this->parse_line($line);
		}, $lines);
		return $lines;
	}

	/**
	 * Read lines from file.
	 *
	 * @param $filename
	 * @param int $lines
	 * @param int $buffer
	 *
	 * @return bool|string
	 */
	public function tail($filename, $lines = 50, $buffer = 4096){
		if(!is_file($filename)){
			return false;
		}
		if(!$f = fopen($filename, "rb")){
			return false;
		}

		fseek($f, -1, SEEK_END);
		if(fread($f, 1) != "\n") $lines -= 1;
		$output = '';
		$chunk = '';
		while(ftell($f) > 0 && $lines >= 0) {
			$seek = min(ftell($f), $buffer);
			fseek($f, -$seek, SEEK_CUR);
			$output = ($chunk = fread($f, $seek)).$output;
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			$lines -= substr_count($chunk, "\n");
		}
		while($lines++ < 0) {
			$output = substr($output, strpos($output, "\n") + 1);
		}
		fclose($f);
		return $output;
	}

}
