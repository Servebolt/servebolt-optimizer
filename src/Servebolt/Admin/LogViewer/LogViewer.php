<?php

namespace Servebolt\Optimizer\Admin\LogViewer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\isNextGen;

/**
 * Class Servebolt_Logviewer
 *
 * This class facilitates log viewing - only works for sites hosted at Servebolt.
 */
class LogViewer
{
    use Singleton;

    /**
     * @var int The number of log entries to display.
     */
    private $numberOfEntries = 100;

    /**
     * Get log file path.
     *
     * @return mixed|void
     */
    private function getErrorLogPath(): string
    {
        $logDir = str_replace('/public', '/logs', $_SERVER['DOCUMENT_ROOT']); 
        if(isNextGen()) {
            $logDir = str_replace('/site/public', '/logs/php', $_SERVER['DOCUMENT_ROOT']);        
        }
        $logFilePath = $logDir . '/ErrorLog';
        return (string) apply_filters('sb_optimizer_log_file_path', $logFilePath);
    }

    /**
     * Display error log.
     */
    public function render(): void
    {
        $logFilePath = $this->getErrorLogPath();
        $logFileExists = file_exists($logFilePath);
        $logFileReadable = is_readable($logFilePath);
        $log = $this->tail($logFilePath, $this->numberOfEntries);
        $entries = $this->prepareEntries($log);
        $numberOfEntries = $this->numberOfEntries;
        view('log-viewer.log-viewer', compact('numberOfEntries', 'logFilePath', 'logFileExists', 'logFileReadable', 'log', 'entries'));
    }

    /**
     * Parse log entry line.
     *
     * @param $entry
     *
     * @return object
     */
    private function parseLine($entry)
    {
        preg_match("/^\[(.*)\] (\[.*\] )(\[.*\] )(\[.*Client:(((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3})|(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))):.*\] )(.*)$/Ui", $entry, $matches);
        if (count($matches) !== 41) {
            return [
                'unparsed_line' => $entry
            ];
        }
        $unixtime = strtotime($matches[1]);
        return (object) [
            'date'  => date('Y-m-d H:i:s', $unixtime),
            'ip'    => $matches[5],
            'error' => $matches[40],
        ];
    }

    /**
     * Prepare entries from log file.
     *
     * @param $log
     *
     * @return array
     */
    private function prepareEntries($log)
    {
        $lines = explode(PHP_EOL, $log);
        $lines = array_reverse($lines);
        $lines = array_filter($lines);
        $lines = array_filter(array_map(function ($line) {
            return $this->parseLine($line);
        }, $lines));
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
    public function tail($filename, $lines = 50, $buffer = 4096)
    {
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
