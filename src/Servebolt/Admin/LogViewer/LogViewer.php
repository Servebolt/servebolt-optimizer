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

        // Resolve file paths and availability for each log type (used for tabs and selection)
        $resolvedInfos = [];
        foreach ($logFileInfo as $info) {
            $path = str_replace($info['slug'], $info['log_location'], $_SERVER['DOCUMENT_ROOT']) . '/' . $info['filename'];
            if (isDevDebug()) {
                $path = '/fake-logs/' . $info['log_location'] . '/' . $info['filename'];
            }
            $exists = file_exists($path) && is_readable($path);
            $info['resolved_path'] = $path;
            $info['exists'] = $exists;
            $resolvedInfos[] = $info;
        }
        $existingInfos = array_values(array_filter($resolvedInfos, function ($i) { return !empty($i['exists']); }));

        // If no readable logs are present, render an empty state
        if (empty($existingInfos)) {
            $pageTitle = __('Logs', 'servebolt-wp');
            view('log-viewer.empty', compact('pageTitle'));
            return;
        }

        // Determine selected tab (log type) and selected level
        $availableTypes = array_values(array_unique(array_map(function ($i) { return (string) ($i['parser'] ?? ''); }, $existingInfos)));
        $defaultType = $availableTypes[0] ?? 'php';
        $selectedType = isset($_GET['log']) ? strtolower((string) $_GET['log']) : $defaultType;
        if (!in_array($selectedType, $availableTypes, true)) {
            $selectedType = $defaultType;
        }

        $selectedLevel = isset($_GET['level']) ? strtolower((string) $_GET['level']) : null; // fatal|error|warning|deprecated|null

        // Configure display + pagination; cap read window to 2500 lines total
        $maxReadWindow = 2500; // hard cap for file scanning
        $perPageOptions = [100, 250, 500];
        $perPageReq = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 100;
        $perPage = in_array($perPageReq, $perPageOptions, true) ? $perPageReq : 100;
        $numberOfEntries = $perPage; // for UI copy

        // Grouping toggle: default on; allow ?group=off to disable
        $groupParam = isset($_GET['group']) ? strtolower((string) $_GET['group']) : 'on';
        $groupEnabled = !in_array($groupParam, ['off','0','false','no'], true);

        // Compute important counts (fatal + error) per existing tab within the 2500-line cap
        $tabImportantCounts = [];
        foreach ($existingInfos as $info) {
            $path = (string) ($info['resolved_path'] ?? '');
            $raw = $this->tail($path, 2500);
            $parsed = $this->prepareEntries($raw, $info['parser'] ?? null, false);
            $imp = 0;
            foreach ($parsed as $p) {
                if (is_object($p) && isset($p->level) && is_string($p->level)) {
                    $lvl = strtolower($p->level);
                    if ($lvl === 'fatal' || $lvl === 'error') {
                        $imp++;
                    }
                }
            }
            $tabImportantCounts[(string) ($info['parser'] ?? '')] = $imp;
        }

        // Tabs meta for the view (only logs with existing files)
        $tabs = array_map(function ($info) use ($selectedType, $tabImportantCounts) {
            $type = (string) ($info['parser'] ?? '');
            return [
                'label'  => (string) $info['title'],
                'type'   => $type,
                'active' => $type === $selectedType,
                'count'  => (int) ($tabImportantCounts[$type] ?? 0),
            ];
        }, $existingInfos);

        // If no explicit level chosen, default to fatal if any exist, else error if any, else All
        if (!isset($_GET['level'])) {
            // Find the info for the selected type to read its file
            $selectedInfo = null;
            foreach ($existingInfos as $info) {
                if (($info['parser'] ?? null) === $selectedType) { $selectedInfo = $info; break; }
            }
            if ($selectedInfo && !empty($selectedInfo['exists']) && !empty($selectedInfo['resolved_path'])) {
                $rawDefaultScan = $this->tail((string)$selectedInfo['resolved_path'], 2500);
                $parsedDefaultScan = $this->prepareEntries($rawDefaultScan, $selectedInfo['parser'] ?? null, false);
                $fatalCount = 0; $errorCount = 0;
                foreach ($parsedDefaultScan as $e) {
                    if (is_object($e) && isset($e->level) && is_string($e->level)) {
                        $lvl = strtolower($e->level);
                        if ($lvl === 'fatal') { $fatalCount++; }
                        elseif ($lvl === 'error') { $errorCount++; }
                    }
                }
                if ($fatalCount > 0) { $selectedLevel = 'fatal'; }
                elseif ($errorCount > 0) { $selectedLevel = 'error'; }
                else { $selectedLevel = null; }
            }
        }

        // Available levels per type
        $levelsByType = [
            'php'  => ['fatal', 'error', 'warning', 'deprecated'],
            'http' => ['fatal', 'error', 'warning', 'deprecated'], // http log may contain embedded PHP messages
        ];
        $availableLevels = $levelsByType[$selectedType] ?? ['fatal', 'error', 'warning'];
        // Normalize explicit "all" to no level filter
        if ($selectedLevel === 'all') {
            $selectedLevel = null;
        }
        if ($selectedLevel && !in_array($selectedLevel, $availableLevels, true)) {
            $selectedLevel = null;
            $lineLimit = 1000;
            $numberOfEntries = $lineLimit;
        }

        // Find the log config for the selected type
        $logInfo = null;
        foreach ($resolvedInfos as $info) {
            if (($info['parser'] ?? null) === $selectedType) {
                $logInfo = $info;
                break;
            }
        }
        if (!$logInfo) {
            return; // Nothing to show
        }

        $logFilePath = $logInfo['resolved_path'] ?? (str_replace($logInfo['slug'], $logInfo['log_location'], $_SERVER['DOCUMENT_ROOT']) . '/' . $logInfo['filename']);

        $logFileExists = file_exists($logFilePath);
        $logFileReadable = $logFileExists ? is_readable($logFilePath) : false;

        $entries = [];
        $log = '';

        $levelCounts = [];
        $allCount = 0;
        // Determine UI deprecation hidden state regardless of active level filter
        $deprecationsHidden = $this->getExcludeDeprecations();

        if ($logFileExists && $logFileReadable) {
            if ($selectedLevel) {
                // Read up to maxReadWindow lines, parse, then filter to selected level and slice to 100
                $chunkSize = $maxReadWindow;
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
                // Group (optional), then paginate
                $items = $groupEnabled ? $this->groupConsecutiveEntries($filtered) : $filtered;
                $totalEntries = count($items);
                $totalPages = max(1, (int) ceil($totalEntries / $perPage));
                $currentPage = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
                if ($currentPage > $totalPages) { $currentPage = $totalPages; }
                $offset = ($currentPage - 1) * $perPage;
                if ($groupEnabled) {
                    $groupedEntries = array_slice($items, $offset, $perPage);
                } else {
                    $entries = array_slice($items, $offset, $perPage);
                }
                // For UI consistency
                $log = $raw;
            } else {
                // No level filter: parse up to 2500 lines, then paginate
                $raw = $this->tail($logFilePath, $maxReadWindow);
                $excludeDeprecations = $this->getExcludeDeprecations();
                $parsed = $this->prepareEntries($raw, $logInfo['parser'] ?? null, $excludeDeprecations);
                // Count levels across full parsed window
                foreach ($parsed as $p) {
                    if (is_object($p) && isset($p->level) && is_string($p->level)) {
                        $lvl = strtolower($p->level);
                        if (in_array($lvl, $availableLevels, true)) {
                            $levelCounts[$lvl] = ($levelCounts[$lvl] ?? 0) + 1;
                        }
                    }
                }
                $allCount = is_array($parsed) ? count($parsed) : 0;
                // Group (optional), then paginate
                $items = $groupEnabled ? $this->groupConsecutiveEntries($parsed) : $parsed;
                $totalEntries = count($items);
                $totalPages = max(1, (int) ceil($totalEntries / $perPage));
                $currentPage = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
                if ($currentPage > $totalPages) { $currentPage = $totalPages; }
                $offset = ($currentPage - 1) * $perPage;
                if ($groupEnabled) {
                    $groupedEntries = array_slice($items, $offset, $perPage);
                } else {
                    $entries = array_slice($items, $offset, $perPage);
                }
                $log = $raw;
            }
        }

        $pageTitle = $logInfo['title'];
        $template = $logInfo['template'];

        // Ensure pagination vars set even if file missing
        if (!isset($totalEntries)) { $totalEntries = 0; }
        if (!isset($totalPages)) { $totalPages = 1; }
        if (!isset($currentPage)) { $currentPage = 1; }

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
            'allCount',
            'deprecationsHidden',
            'perPageOptions',
            'perPage',
            'totalEntries',
            'totalPages',
            'currentPage',
            'groupedEntries',
            'groupEnabled'
        ));
    }

    /**
     * Group consecutive identical entries by their visible error text (or raw line) and count repeats.
     * Returns an array of ['entry' => object|array, 'count' => int].
     */
    private function groupConsecutiveEntries(array $entries): array
    {
        $grouped = [];
        $prevKey = null;
        foreach ($entries as $e) {
            $key = '';
            if (is_array($e) && isset($e['unparsed_line'])) {
                $key = 'raw:' . strtolower(trim((string)$e['unparsed_line']));
            } elseif (is_object($e) && isset($e->error)) {
                $key = 'err:' . strtolower(trim((string)$e->error));
            } else {
                $key = 'other:' . md5(serialize($e));
            }
            if ($prevKey !== null && $key === $prevKey) {
                $last = count($grouped) - 1;
                $grouped[$last]['count']++;
            } else {
                $grouped[] = [ 'entry' => $e, 'count' => 1 ];
                $prevKey = $key;
            }
        }
        return $grouped;
    }

    /**
     * Prepare entries from a log file.
     */
    private function prepareEntries($log, ?string $parser = null, bool $excludeDeprecations = true)
    {
        $rawLines = preg_split("/(\r\n|\n|\r)/", (string) $log);
        $rawLines = array_values(array_filter($rawLines, function ($l) { return trim((string)$l) !== ''; }));

        // Special handling for PHP logs to group multi-line stack traces
        if ($parser === 'php') {
            $entries = [];
            $current = null;
            foreach ($rawLines as $line) {
                $line = (string) $line;
                $trim = trim($line);
                if ($trim === '') { continue; }

                $isStart = (bool) preg_match('/^\[[^\]]+\]\s*(?:PHP\s+)?(?:Fatal error|Parse error|Warning|Notice|Deprecated|Error|Exception|Recoverable fatal error|Core Warning|Core Error|Strict|User Deprecated|User Warning|User Notice|User Error)?/i', $trim);

                if ($isStart) {
                    if ($current) {
                        $entries[] = $current;
                    }
                    $parsed = $this->parsePhpLine($trim);
                    if (is_object($parsed)) {
                        $parsed->trace_lines = [];
                        $current = $parsed;
                    } else {
                        $current = (object) [
                            'date' => '',
                            'ip' => null,
                            'error' => $trim,
                            'level' => null,
                            'trace_lines' => [],
                        ];
                    }
                    continue;
                }

                // Continuation line: part of a previous PHP entry (stack trace or thrown-in line)
                if ($current) {
                    // unify whitespace a bit
                    $current->trace_lines[] = $trim;
                } else {
                    // Orphan continuation; keep as unparsed
                    $entries[] = ['unparsed_line' => $trim];
                }
            }
            if ($current) {
                $entries[] = $current;
            }
            // Newest first
            $results = array_reverse($entries);
        } else {
            // HTTP and generic: parse line-by-line (newest first), while supporting multi-emit from HTTP lines
            $lines = array_reverse($rawLines);
            $results = [];
            foreach ($lines as $line) {
                $parsed = null;
                if ($parser === 'http') {
                    $parsed = $this->parseHttpLine($line);
                } else {
                    $parsed = ['unparsed_line' => $line];
                }
                if ($parsed === null) { continue; }
                if (is_array($parsed) && (isset($parsed[0]) || $parsed === [])) {
                    foreach ($parsed as $p) { if ($p !== null) { $results[] = $p; } }
                } else {
                    $results[] = $parsed;
                }
            }
        }

        if ($excludeDeprecations) {
            $results = array_filter($results, function ($entry) {
                if (is_array($entry) && isset($entry['unparsed_line'])) {
                    return stripos($entry['unparsed_line'], 'deprecated') === false;
                }
                if (is_object($entry)) {
                    if (isset($entry->level) && is_string($entry->level)) {
                        if (stripos($entry->level, 'deprecated') !== false) { return false; }
                    }
                    if (isset($entry->error) && is_string($entry->error)) {
                        if (stripos($entry->error, 'deprecated') !== false) { return false; }
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

        // Helper to extract IP from various Apache/Nginx patterns
        $extractIp = function(string $text) {
            // Prefer bracket tokens first: [client 1.2.3.4:12345] or [remote 2a10:...]
            if (preg_match('/\[(?:client|remote)\s+([^\]]+)\]/i', $text, $m)) {
                $token = trim($m[1]);
                // If token ends with :<digits>, strip the port while preserving IPv6
                if (preg_match('/^(.*?):(\d+)$/', $token, $pm)) {
                    // only strip if left side looks like IP (contains ':' for IPv6 or '.' for IPv4)
                    if (strpos($pm[1], ':') !== false || strpos($pm[1], '.') !== false) {
                        return $pm[1];
                    }
                }
                return $token;
            }
            // Fallbacks: client: x.x.x.x or remote: x:x::x etc within free text
            if (preg_match('/(?:,\s*)?(?:client|remote):\s*([0-9a-fA-F:\.]+)/', $text, $mi)) {
                $token = $mi[1];
                // Strip trailing :port for IPv4; for IPv6 ambiguous, attempt same numeric port strip
                if (preg_match('/^(.*?):(\d+)$/', $token, $pm)) {
                    if (strpos($pm[1], ':') !== false || strpos($pm[1], '.') !== false) {
                        return $pm[1];
                    }
                }
                return $token;
            }
            return null;
        };

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

            $ip = $extractIp($rest) ?: null;

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
            $ip = $extractIp($rest) ?: null;
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

        // Apache-like without explicit date, e.g.:
        // [authz_core:error] [pid ...] [remote 2a10:...] AH01630: client denied by server configuration: ...
        if (preg_match('/^\[[^\]]+\]\s+(?P<rest>.*)$/', $entry, $m)) {
            $rest = $m['rest'];
            $ip = $extractIp($entry) ?: $extractIp($rest);
            return (object) [
                'date'  => '',
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
