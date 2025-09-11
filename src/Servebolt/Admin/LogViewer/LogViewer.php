<?php

namespace Servebolt\Optimizer\Admin\LogViewer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;

use function Servebolt\Optimizer\Helpers\isDevDebug;
use function Servebolt\Optimizer\Helpers\view;

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
    private $excludeDeprecations = true;

    // Log paths (current platform)
    private $logPaths = [
        [
            'title' => 'PHP Error Log',
            'slug' => 'site/public',
            'log_location' => 'logs/php',
            'filename' => 'ErrorLog',
            'template' => 'log-viewer.log-viewer',
            'parser' => 'php',
        ],
        [
            'title' => 'HTTP Error Log',
            'slug' => 'site/public',
            'log_location' => 'logs/http',
            'filename' => 'ErrorLog',
            'template' => 'log-viewer.log-viewer',
            'parser' => 'http',
        ],
    ];
    /**
     * Get log file path.
     *
     * @return mixed|void
     */
    private function getLogPaths(): array
    {
        return $this->logPaths;
    }

    private function getExcludeDeprecations(): bool
    {
        $exclude = $this->excludeDeprecations;
        if (isset($_GET['deprecations'])) {
            $val = strtolower((string) $_GET['deprecations']);
            if ($val === 'show') {
                $exclude = false;
            } elseif ($val === 'hide') {
                $exclude = true;
            }
        }
        if (function_exists('apply_filters')) {
            $exclude = (bool) apply_filters('sb_optimizer_log_viewer_exclude_deprecations', $exclude);
        }
        return $exclude;
    }

    /**
     * Display error log with tabs and level filtering.
     */
    public function render(): void
    {
        $logFileInfo = $this->getLogPaths();

        // Determine selected tab (log type) and selected level
        $availableTypes = array_values(array_unique(array_map(function ($i) { return (string) ($i['parser'] ?? ''); }, $logFileInfo)));
        $defaultType = $availableTypes[0] ?? 'php';
        $selectedType = isset($_GET['log']) ? strtolower((string) $_GET['log']) : $defaultType;
        if (!in_array($selectedType, $availableTypes, true)) {
            $selectedType = $defaultType;
        }

        $selectedLevel = isset($_GET['level']) ? strtolower((string) $_GET['level']) : null; // fatal|error|warning|deprecated|null

        // Configure line limits per requirements
        $lineLimit = $selectedLevel ? 100 : 1000;
        $numberOfEntries = $lineLimit; // for UI copy

        // Tabs meta for the view
        $tabs = array_map(function ($info) use ($selectedType) {
            return [
                'label'  => (string) $info['title'],
                'type'   => (string) $info['parser'],
                'active' => (string) $info['parser'] === $selectedType,
            ];
        }, $logFileInfo);

        // Available levels per type
        $levelsByType = [
            'php'  => ['fatal', 'error', 'warning', 'deprecated'],
            'http' => ['fatal', 'error', 'warning', 'deprecated'], // http log may contain embedded PHP messages
        ];
        $availableLevels = $levelsByType[$selectedType] ?? ['fatal', 'error', 'warning'];
        if ($selectedLevel && !in_array($selectedLevel, $availableLevels, true)) {
            $selectedLevel = null;
            $lineLimit = 1000;
            $numberOfEntries = $lineLimit;
        }

        // Find the log config for the selected type
        $logInfo = null;
        foreach ($logFileInfo as $info) {
            if (($info['parser'] ?? null) === $selectedType) {
                $logInfo = $info;
                break;
            }
        }
        if (!$logInfo) {
            return; // Nothing to show
        }

        $logFilePath = str_replace($logInfo['slug'], $logInfo['log_location'], $_SERVER['DOCUMENT_ROOT']) . '/' . $logInfo['filename'];

        if(isDevDebug()) {
            $logFilePath = '/fake-logs/' . $logInfo['log_location'] . '/' . $logInfo['filename'];
        }

        $logFileExists = file_exists($logFilePath);
        $logFileReadable = $logFileExists ? is_readable($logFilePath) : false;

        $entries = [];
        $log = '';

        $levelCounts = [];
        $allCount = 0;
        if ($logFileExists && $logFileReadable) {
            if ($selectedLevel) {
                // Read a larger chunk, parse, then filter to selected level and slice to 100
                $chunkSize = max(1000, $lineLimit * 50); // heuristic to find up to 100 level-matched entries
                $raw = $this->tail($logFilePath, $chunkSize);
                $excludeDeprecations = false; // ignore deprecation toggle when explicitly filtering by level
                $parsed = $this->prepareEntries($raw, $logInfo['parser'] ?? null, $excludeDeprecations);
                // Count levels across parsed chunk
                foreach ($parsed as $p) {
                    if (is_object($p) && isset($p->level) && is_string($p->level)) {
                        $lvl = strtolower($p->level);
                        if (in_array($lvl, $availableLevels, true)) {
                            $levelCounts[$lvl] = ($levelCounts[$lvl] ?? 0) + 1;
                        }
                    }
                }
                $allCount = is_array($parsed) ? count($parsed) : 0;
                $filtered = array_values(array_filter($parsed, function ($entry) use ($selectedLevel) {
                    return is_object($entry) && isset($entry->level) && strtolower((string) $entry->level) === $selectedLevel;
                }));
                $entries = array_slice($filtered, 0, $lineLimit);
                // For UI consistency
                $log = $raw;
            } else {
                // No level filter: read last 1000 lines and parse normally
                $raw = $this->tail($logFilePath, $lineLimit);
                $excludeDeprecations = $this->getExcludeDeprecations();
                $entries = $this->prepareEntries($raw, $logInfo['parser'] ?? null, $excludeDeprecations);
                // Count levels across parsed entries
                foreach ($entries as $p) {
                    if (is_object($p) && isset($p->level) && is_string($p->level)) {
                        $lvl = strtolower($p->level);
                        if (in_array($lvl, $availableLevels, true)) {
                            $levelCounts[$lvl] = ($levelCounts[$lvl] ?? 0) + 1;
                        }
                    }
                }
                $allCount = is_array($entries) ? count($entries) : 0;
                $log = $raw;
            }
        }

        $pageTitle = $logInfo['title'];
        $template = $logInfo['template'];

        view($template, compact(
            'numberOfEntries',
            'logFilePath',
            'logFileExists',
            'logFileReadable',
            'log',
            'entries',
            'pageTitle',
            'tabs',
            'selectedType',
            'availableLevels',
            'selectedLevel',
            'levelCounts',
            'allCount'
        ));
    }

    /**
     * Prepare entries from a log file.
     */
    private function prepareEntries($log, ?string $parser = null, bool $excludeDeprecations = true)
    {
        $lines = explode(PHP_EOL, $log);
        $lines = array_reverse($lines);
        $lines = array_filter($lines);
        $results = [];
        foreach ($lines as $line) {
            $parsed = null;
            if ($parser === 'php') {
                $parsed = $this->parsePhpLine($line);
            } elseif ($parser === 'http') {
                $parsed = $this->parseHttpLine($line);
            } else {
                $parsed = [ 'unparsed_line' => $line ];
            }
            if ($parsed === null) {
                continue;
            }
            if (is_array($parsed) && (isset($parsed[0]) || $parsed === [])) {
                // Parser returned multiple entries
                foreach ($parsed as $p) {
                    if ($p !== null) {
                        $results[] = $p;
                    }
                }
            } else {
                $results[] = $parsed;
            }
        }
        if ($excludeDeprecations) {
            $results = array_filter($results, function ($entry) {
                if (is_array($entry) && isset($entry['unparsed_line'])) {
                    // If we can't parse level, keep it (conservative), unless it clearly contains 'deprecated'
                    return stripos($entry['unparsed_line'], 'deprecated') === false;
                }
                if (is_object($entry)) {
                    if (isset($entry->level) && is_string($entry->level)) {
                        if (stripos($entry->level, 'deprecated') !== false) {
                            return false;
                        }
                    }
                    if (isset($entry->error) && is_string($entry->error)) {
                        if (stripos($entry->error, 'deprecated') !== false) {
                            return false;
                        }
                    }
                }
                return true;
            });
        }
        return $results;
    }

    /**
     * Parse a PHP error log line.
     * Examples:
     * [01-Jan-2025 12:34:56 UTC] PHP Warning: message in /path/file.php on line 123
     * [2025-01-01 12:34:56] PHP Fatal error: message
     */
    private function parsePhpLine($entry)
    {
        $entry = trim($entry);
        if ($entry === '') {
            return null;
        }

        if (preg_match('/^\[(?P<date>[^\]]+)\]\s*(?:PHP\s+)?(?P<level>Fatal error|Parse error|Warning|Notice|Deprecated|Error|Exception|Recoverable fatal error|Core Warning|Core Error|Strict|User Deprecated|User Warning|User Notice|User Error)?\:?(?:\s+)?(?P<message>.*)$/i', $entry, $m)) {
            $timestamp = isset($m['date']) ? strtotime($m['date']) : false;
            $date = $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
            $message = isset($m['message']) ? trim($m['message']) : $entry;
            $rawLevel = isset($m['level']) && $m['level'] !== '' ? strtolower($m['level']) : null;
            $level = null;
            if ($rawLevel) {
                if (strpos($rawLevel, 'fatal') !== false) {
                    $level = 'fatal';
                } elseif (strpos($rawLevel, 'warning') !== false) {
                    $level = 'warning';
                } elseif (strpos($rawLevel, 'deprecated') !== false) {
                    $level = 'deprecated';
                } elseif ($rawLevel === 'error') {
                    $level = 'error';
                }
            }
            if (!$level && stripos($message, 'deprecated') !== false) {
                $level = 'deprecated';
            }

            return (object) [
                'date'  => $date,
                'ip'    => null,
                'error' => $message,
                'level' => $level,
            ];
        }

        return [
            'unparsed_line' => $entry,
        ];
    }

    /**
     * Parse an HTTP (e.g., Nginx) error log line.
     * Example:
     * 2025/01/01 12:34:56 [error] 12345#0: *1 message, client: 1.2.3.4, server: ..., request: ...
     */
    private function parseHttpLine($entry)
    {
        $entry = trim($entry);
        if ($entry === '') {
            return null;
        }

        if (preg_match('/^(?P<date>\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+\[(?P<level>\w+)\]\s+[^:]*:?\s*(?:\*\d+\s+)?(?P<rest>.*)$/', $entry, $m)) {
            $timestamp = strtotime(str_replace('/', '-', $m['date']));
            $date = $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
            $rest = $m['rest'];
            $rawLevel = strtolower($m['level']);
            $level = null;
            if ($rawLevel === 'warn' || $rawLevel === 'warning') {
                $level = 'warning';
            } elseif (in_array($rawLevel, ['crit','alert','emerg'], true)) {
                $level = 'fatal';
            } elseif ($rawLevel === 'error') {
                $level = 'error';
            }

            $ip = null;
            if (preg_match('/(?:,\s*)?client:\s*(?P<ip>[0-9a-fA-F:\.]+)/', $rest, $mi)) {
                $ip = $mi['ip'];
            }

            // If the line contains embedded PHP messages, split them and emit separate entries
            if (stripos($rest, 'php message:') !== false) {
                $segments = preg_split('/php message:\s*/i', $rest);
                // First element is preamble; subsequent are messages
                $out = [];
                foreach ($segments as $idx => $seg) {
                    if ($idx === 0) { continue; }
                    $seg = trim($seg, " ;\t\r\n");
                    if ($seg === '') { continue; }
                    // Try to capture PHP level + message
                    $sm = [];
                    $normLevel = null;
                    $msg = $seg;
                    if (preg_match('/^PHP\s+(Fatal error|Parse error|Warning|Notice|Deprecated|Error)\s*:\s*(.*)$/i', $seg, $sm)) {
                        $raw = strtolower($sm[1]);
                        $msg = trim($sm[2]);
                        if (strpos($raw, 'fatal') !== false) {
                            $normLevel = 'fatal';
                        } elseif (strpos($raw, 'warning') !== false) {
                            $normLevel = 'warning';
                        } elseif (strpos($raw, 'deprecated') !== false) {
                            $normLevel = 'deprecated';
                        } elseif ($raw === 'error') {
                            $normLevel = 'error';
                        }
                    } else {
                        // Fallback: infer deprecated from message
                        if (stripos($msg, 'deprecated') !== false) {
                            $normLevel = 'deprecated';
                        }
                    }
                    $out[] = (object) [
                        'date'  => $date,
                        'ip'    => $ip,
                        'error' => $msg,
                        'level' => $normLevel,
                    ];
                }
                if (!empty($out)) {
                    return $out;
                }
            }

            return (object) [
                'date'  => $date,
                'ip'    => $ip,
                'error' => $rest,
                'level' => $level,
            ];
        }

        // Apache-like with [date] prefix
        if (preg_match('/^\[(?P<date>[^\]]+)\]\s+(?P<rest>.*)$/', $entry, $m)) {
            // Remove microseconds to improve strtotime compatibility
            $dateStr = preg_replace('/:(\d{2})\.\d+\s+/', ':$1 ', $m['date']);
            $timestamp = strtotime($dateStr ?: $m['date']);
            $date = $timestamp ? date('Y-m-d H:i:s', $timestamp) : '';
            $rest = $m['rest'];
            $ip = null;
            if (preg_match('/\bclient[\s\-]*:?\s*(?P<ip>[0-9a-fA-F:\.]+)/i', $rest, $mi)) {
                $ip = $mi['ip'];
            }
            if (stripos($rest, 'php message:') !== false) {
                $segments = preg_split('/php message:\s*/i', $rest);
                $out = [];
                foreach ($segments as $idx => $seg) {
                    if ($idx === 0) { continue; }
                    $seg = trim($seg, " ;\t\r\n");
                    if ($seg === '') { continue; }
                    $sm = [];
                    $normLevel = null;
                    $msg = $seg;
                    if (preg_match('/^PHP\s+(Fatal error|Parse error|Warning|Notice|Deprecated|Error)\s*:\s*(.*)$/i', $seg, $sm)) {
                        $raw = strtolower($sm[1]);
                        $msg = trim($sm[2]);
                        if (strpos($raw, 'fatal') !== false) {
                            $normLevel = 'fatal';
                        } elseif (strpos($raw, 'warning') !== false) {
                            $normLevel = 'warning';
                        } elseif (strpos($raw, 'deprecated') !== false) {
                            $normLevel = 'deprecated';
                        } elseif ($raw === 'error') {
                            $normLevel = 'error';
                        }
                    } else {
                        if (stripos($msg, 'deprecated') !== false) {
                            $normLevel = 'deprecated';
                        }
                    }
                    $out[] = (object) [
                        'date'  => $date,
                        'ip'    => $ip,
                        'error' => $msg,
                        'level' => $normLevel,
                    ];
                }
                if (!empty($out)) {
                    return $out;
                }
            }
            return (object) [
                'date'  => $date,
                'ip'    => $ip,
                'error' => $rest,
            ];
        }

        return [
            'unparsed_line' => $entry,
        ];
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
