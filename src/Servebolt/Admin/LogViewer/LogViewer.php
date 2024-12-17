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

    private $sl8_log_paths = [
        [
            'title' => 'PHP Error Log',
            'slug' => 'site/public',
            'log_location' => 'logs/php',
            'filename' => 'ErrorLog',
            'template' => 'log-viewer.sl8-viewer',
        ],
        [
            'title' => 'HTTP Error Log',
            'slug' => 'site/public',
            'log_location' => 'logs/http',
            'filename' => 'ErrorLog',
            'template' => 'log-viewer.sl8-viewer',
        ],
    ];

    private $sl7_log_paths = [
        [
            'title' => 'Error Log',
            'slug' => '/public',
            'log_location' => '/logs',
            'filename' => 'ErrorLog',
            'template' => 'log-viewer.sl7-viewer',
        ],
    ];
    /**
     * Get log file path.
     *
     * @return mixed|void
     */
    private function getErrorLogPath(): string
    {
        if(isNextGen()) {
            $logDir = str_replace('/site/public', '/logs/php', $_SERVER['DOCUMENT_ROOT']);
            $logFilePath = $logDir . '/ErrorLog';
        } else {
            $logDir = str_replace('/public', '/logs', $_SERVER['DOCUMENT_ROOT']); 
            $logFilePath = $logDir . '/ErrorLog';
        }
        
        return (string) apply_filters('sb_optimizer_log_file_path', $logFilePath);
    }

    private function getLogPaths(): array
    {
        if(isNextGen()) {
            return $this->sl8_log_paths;
        } else {
            return $this->sl7_log_paths;
        }
    }

    /**
     * Display error log.
     */
    public function render(): void
    {
        $logFileInfo = $this->getLogPaths();
        foreach($logFileInfo as $logInfo) {
            $logFilePath = str_replace($logInfo['slug'], $logInfo['log_location'], $_SERVER['DOCUMENT_ROOT']) . '/' . $logInfo['filename'];
            $logFileExists = file_exists($logFilePath);
            if(!$logFileExists) {
                continue;
            }
            $logFileReadable = is_readable($logFilePath);
            if(!$logFileReadable) {
                continue;
            }
            $log = $this->tail($logFilePath, $this->numberOfEntries);
            $entries = $this->prepareEntries($log);
            
            $numberOfEntries = $this->numberOfEntries;
            $pageTitle = $logInfo['title'];
            view($logInfo['template'], compact('numberOfEntries', 'logFilePath', 'logFileExists', 'logFileReadable', 'log', 'entries', 'pageTitle'));
        }
     }

    /**
     * Parse log entry line.
     *
     * @param $entry
     *
     * @return object
     */
    private function parseLine($entry, $logType = 'sl7')
    {
        
        if($logType == 'sl7'){
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
        } else if($logType == 'sl8-php') {
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
        } else if($logType == 'sl8-apache') {
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
