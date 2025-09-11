<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>
<div class="wrap">
	<h2><?php echo $pageTitle; ?></h2>
    <?php
        // Build tab navigation (PHP / HTTP)
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (function_exists('remove_query_arg')) {
            // When switching tabs, drop level and paging; we'll start at page 1
            $tabsBaseUrl = remove_query_arg(['level','paged'], $currentUrl);
        } else {
            $tabsBaseUrl = $currentUrl;
        }
        // Determine if deprecations are hidden (from controller or fallback to GET)
        if (!isset($deprecationsHidden)) {
            $deprecationsHidden = isset($_GET['deprecations']) ? (strtolower((string) $_GET['deprecations']) !== 'show') : true;
        }
    ?>
    <?php if (!empty($tabs) && is_array($tabs)) : ?>
        <h2 class="nav-tab-wrapper" style="margin-top:12px;">
            <?php foreach ($tabs as $tab): ?>
                <?php
                    $isActive = !empty($tab['active']);
                    $type = isset($tab['type']) ? (string)$tab['type'] : '';
                    $label = isset($tab['label']) ? (string)$tab['label'] : $type;
                    if (function_exists('add_query_arg')) {
                        $tabUrl = add_query_arg(['log' => $type, 'paged' => 1], $tabsBaseUrl);
                    } else {
                        $sep = (strpos($tabsBaseUrl, '?') === false) ? '?' : '&';
                        $tabUrl = preg_replace('/([&?])level=[^&#]*/', '$1', $tabsBaseUrl);
                        $tabUrl = preg_replace('/([&?])log=[^&#]*/', '$1', $tabUrl);
                        $tabUrl = preg_replace('/([&?])paged=\d+/', '$1', $tabUrl);
                        $tabUrl .= $sep . 'log=' . urlencode($type) . '&paged=1';
                    }
                ?>
                <a href="<?php echo esc_url($tabUrl); ?>" class="nav-tab <?php echo $isActive ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?><?php if (isset($tab['count'])): ?><span class="sb-tab-badge" title="<?php echo esc_attr(__('Important errors (fatal+error)', 'servebolt-wp')); ?>"><?php echo (int) $tab['count']; ?></span><?php endif; ?></a>
            <?php endforeach; ?>
        </h2>
    <?php endif; ?>

	<p>Log file path: <?php echo $logFilePath; ?></p>
    <style>
        .sb-log .sb-level-fatal td { background: #fbeaea; }
        .sb-log .sb-level-warning td { background: #fff8e5; }
        .sb-log .sb-level-deprecated td { background: #f6f7f7; color: #666; }
        .sb-log .sb-level-error td { background: #fde8ef; }
        .sb-level-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; vertical-align:middle; }
        .sb-level-dot-fatal { background:#d63638; }
        .sb-level-dot-error { background:#e11d48; }
        .sb-level-dot-warning { background:#dba617; }
        .sb-level-dot-deprecated { background:#757575; }
        .sb-level-badge { display:inline-block; min-width:18px; padding:0 6px; margin-left:6px; border-radius:12px; background:#f0f0f1; color:#50575e; font-size:11px; line-height:18px; text-align:center; }

        /* Cleaner filter toolbar */
        .sb-filter-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin:10px 0 6px; padding:10px 12px; background:#f6f7f7; border:1px solid #e0e0e0; border-radius:8px; }
        .sb-filter-left { display:flex; align-items:center; flex-wrap:wrap; gap:10px; }
        .sb-filter-chips { display:flex; flex-wrap:wrap; gap:8px; }
        .sb-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border:1px solid #dcdcde; border-radius:999px; background:#fff; color:#1d2327; text-decoration:none; line-height:1.4; }
        .sb-chip:hover { background:#f0f0f1; border-color:#b3b6b9; color:#1d2327; }
        .sb-chip.active { background:#2271b1; border-color:#2271b1; color:#fff; box-shadow:0 0 0 1px #2271b1 inset; }
        .sb-chip.active .sb-level-badge { background:rgba(255,255,255,0.2); color:#fff; }
        .sb-chip .sb-level-dot { margin-right:2px; }
        .sb-filter-actions { display:flex; align-items:center; gap:12px; }
        .sb-filter-note { color:#50575e; }
        .sb-per-page { display:flex; align-items:center; gap:6px; }
        .sb-per-page-label { color:#50575e; }
        .sb-pagination { display:flex; gap:6px; align-items:center; margin:8px 0; }
        .sb-pagination.inline { margin:0; }
        .sb-page-link { padding:6px 10px; border:1px solid #dcdcde; border-radius:6px; background:#fff; text-decoration:none; color:#1d2327; line-height:1; }
        .sb-page-link:hover { background:#f0f0f1; border-color:#b3b6b9; }
        .sb-page-link.current { background:#2271b1; color:#fff; border-color:#2271b1; }
        .sb-page-spacer { color:#50575e; }
        .sb-divider { width:1px; height:22px; background:#e0e0e0; }
        .sb-status-badge { display:inline-block; padding:6px 10px; border-radius:999px; background:#fff8e5; color:#7a5d00; border:1px solid #f0c36d; font-weight:600; margin:6px 0; }
        .sb-status-badge a { color:#7a5d00; text-decoration:underline; font-weight:600; margin-left:8px; }
        .sb-tab-badge { display:inline-block; margin-left:8px; padding:0 8px; border-radius:999px; background:#e11d48; color:#fff; font-size:11px; line-height:18px; }
    </style>
	<?php if (!$logFileExists) : ?>
    <div class="notice notice-warning">
		<p><?php _e('The log file does not exist.', 'servebolt-wp'); ?></p>
    </div>
	<?php elseif (!$logFileReadable) : ?>
		<p><?php _e('Log file is not readable.', 'servebolt-wp'); ?></p>
	<?php elseif (!$log) : ?>
		<p><?php _e('Your error log is empty.', 'servebolt-wp'); ?></p>
	<?php else : ?>
        <?php
            // Build level filter links (below tabs)
            $levels = isset($availableLevels) && is_array($availableLevels) ? $availableLevels : [];
            if (function_exists('remove_query_arg')) {
                $levelsBaseUrl = remove_query_arg(['level'], $currentUrl);
                $levelsBaseUrl = add_query_arg(['log' => isset($selectedType) ? (string)$selectedType : ''], $levelsBaseUrl);
            } else {
                $levelsBaseUrl = $currentUrl;
            }
        ?>
        <?php if (!empty($deprecationsHidden) && empty($selectedLevel)) : ?>
            <?php
                // Link to show deprecations (same behavior as the button)
                if (function_exists('add_query_arg')) {
                    $badgeToggleUrl = add_query_arg('deprecations', 'show');
                } else {
                    $sep = (strpos($_SERVER['REQUEST_URI'], '?') === false) ? '?' : '&';
                    $badgeToggleUrl = $_SERVER['REQUEST_URI'] . $sep . 'deprecations=show';
                }
            ?>
            <div>
                <span class="sb-status-badge">
                    <?php echo esc_html(__('Deprecations are hidden', 'servebolt-wp')); ?>
                    <a href="<?php echo esc_url($badgeToggleUrl); ?>"><?php echo esc_html(__('Show', 'servebolt-wp')); ?></a>
                </span>
            </div>
        <?php endif; ?>
        <?php if (!empty($levels)) : ?>
            <div class="sb-filter-toolbar">
                <div class="sb-filter-left">
                    
                    <?php
                        // Inline pagination controls (same vars as used below)
                        $totalPages = isset($totalPages) ? (int)$totalPages : 1;
                        $currentPage = isset($currentPage) ? (int)$currentPage : 1;
                        $pageBase = function_exists('remove_query_arg') ? remove_query_arg(['paged'], $currentUrl) : $currentUrl;
                        $mk = function($page) use ($pageBase) {
                            if (function_exists('add_query_arg')) {
                                return add_query_arg(['paged' => (int)$page], $pageBase);
                            }
                            $sep = (strpos($pageBase, '?') === false) ? '?' : '&';
                            $tmp = preg_replace('/([&?])paged=[^&#]*/', '$1', $pageBase);
                            return $tmp . $sep . 'paged=' . (int)$page;
                        };
                    ?>
                    <?php if ($totalPages > 1): ?>
                        <div class="sb-pagination inline">
                            <?php $prev = max(1, $currentPage - 1); $next = min($totalPages, $currentPage + 1); ?>
                            <a class="sb-page-link" href="<?php echo esc_url($mk($prev)); ?>" title="<?php echo esc_attr(__('Previous page', 'servebolt-wp')); ?>">&laquo;</a>
                            <span class="sb-page-spacer"><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'servebolt-wp'), $currentPage, $totalPages)); ?></span>
                            <a class="sb-page-link" href="<?php echo esc_url($mk($next)); ?>" title="<?php echo esc_attr(__('Next page', 'servebolt-wp')); ?>">&raquo;</a>
                        </div>
                    <?php endif; ?>
                    <div class="sb-divider"></div>
                    <div class="sb-filter-chips">
                    <?php
                        $allUrl = function_exists('remove_query_arg') ? remove_query_arg(['level'], $levelsBaseUrl) : $levelsBaseUrl;
                        $allActive = empty($selectedLevel);
                    ?>
                    <a href="<?php echo esc_url($allUrl); ?>" class="sb-chip <?php echo $allActive ? 'active' : ''; ?>" aria-current="<?php echo $allActive ? 'true' : 'false'; ?>">
                        <?php echo esc_html(__('All', 'servebolt-wp')); ?>
                        <?php if (isset($allCount)) : ?><span class="sb-level-badge"><?php echo (int) $allCount; ?></span><?php endif; ?>
                    </a>
                    <?php foreach ($levels as $i => $lvl): ?>
                        <?php if ($deprecationsHidden && strtolower((string)$lvl) === 'deprecated') { continue; } ?>
                        <?php
                            $isCurrent = isset($selectedLevel) && strtolower((string)$selectedLevel) === strtolower((string)$lvl);
                            if (function_exists('add_query_arg')) {
                                $lvlUrl = add_query_arg(['level' => strtolower((string)$lvl)], $levelsBaseUrl);
                            } else {
                                $sep = (strpos($levelsBaseUrl, '?') === false) ? '?' : '&';
                                $lvlUrl = preg_replace('/([&?])level=[^&#]*/', '$1', $levelsBaseUrl);
                                $lvlUrl .= $sep . 'level=' . urlencode(strtolower((string)$lvl));
                            }
                            $count = isset($levelCounts[strtolower((string)$lvl)]) ? (int) $levelCounts[strtolower((string)$lvl)] : 0;
                        ?>
                        <a href="<?php echo esc_url($lvlUrl); ?>" class="sb-chip <?php echo $isCurrent ? 'active' : ''; ?>" aria-current="<?php echo $isCurrent ? 'true' : 'false'; ?>">
                            <span class="sb-level-dot sb-level-dot-<?php echo esc_attr(strtolower((string)$lvl)); ?>"></span>
                            <?php echo esc_html(ucfirst((string)$lvl)); ?>
                            <span class="sb-level-badge"><?php echo $count; ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="sb-filter-actions">
                    <?php if (empty($selectedLevel)) : ?>
                        <?php
                            // Build toggle link for deprecations (only relevant when not level-filtering)
                            $hide = isset($_GET['deprecations']) ? (strtolower((string) $_GET['deprecations']) !== 'show') : true;
                            if (function_exists('add_query_arg')) {
                                $toggleUrl = add_query_arg('deprecations', $hide ? 'show' : 'hide');
                            } else {
                                $sep = (strpos($_SERVER['REQUEST_URI'], '?') === false) ? '?' : '&';
                                $toggleUrl = $_SERVER['REQUEST_URI'] . $sep . 'deprecations=' . ($hide ? 'show' : 'hide');
                            }
                        ?>
                        <?php if (!$hide): // Only show button when deprecations are currently shown ?>
                            <a href="<?php echo esc_url($toggleUrl); ?>" class="button button-secondary">
                                <?php echo __('Hide Deprecations', 'servebolt-wp'); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php
                        // Per-page chips (100/250/500) — only show if pagination is needed
                        $perPage = isset($perPage) ? (int)$perPage : 100;
                        $perPageOptions = isset($perPageOptions) && is_array($perPageOptions) ? $perPageOptions : [100,250,500];
                        $baseUrl = function_exists('remove_query_arg') ? remove_query_arg(['paged','per_page'], $currentUrl) : $currentUrl;
                        $needsPagination = isset($totalPages) ? ((int)$totalPages > 1) : false;
                    ?>
                    <?php if ($needsPagination): ?>
                        <div class="sb-per-page">
                            <span class="sb-per-page-label"><?php echo esc_html(__('Per page', 'servebolt-wp')); ?>:</span>
                            <?php foreach ($perPageOptions as $opt): ?>
                                <?php
                                    if (function_exists('add_query_arg')) {
                                        $ppUrl = add_query_arg(['per_page' => (int)$opt, 'paged' => 1], $baseUrl);
                                    } else {
                                        $sep = (strpos($baseUrl, '?') === false) ? '?' : '&';
                                        $tmp = preg_replace('/([&?])per_page=[^&#]*/', '$1', $baseUrl);
                                        $tmp = preg_replace('/([&?])paged=[^&#]*/', '$1', $tmp);
                                        $ppUrl = $tmp . $sep . 'per_page=' . (int)$opt . '&paged=1';
                                    }
                                    $isActive = ((int)$opt === (int)$perPage);
                                ?>
                                <a class="sb-chip <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo esc_url($ppUrl); ?>"><?php echo (int)$opt; ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
            $nowTs = function_exists('current_time') ? (int) current_time('timestamp') : time();
            $humanDiff = function ($from, $to) {
                if (function_exists('human_time_diff')) {
                    return human_time_diff($from, $to);
                }
                $delta = max(0, (int)($to - $from));
                $units = [
                    86400 => 'day',
                    3600  => 'hour',
                    60    => 'minute',
                    1     => 'second',
                ];
                foreach ($units as $secs => $label) {
                    if ($delta >= $secs) {
                        $val = floor($delta / $secs);
                        return $val . ' ' . $label . ($val !== 1 ? 's' : '');
                    }
                }
                return '0 seconds';
            };
        ?>
        <p><?php printf( __('Showing %1$s per page — %2$s entries in view', 'servebolt-wp'), (int)$numberOfEntries, (int)$totalEntries ); ?>:</p>
        <table class="wp-list-table widefat striped posts sb-log">
            <thead>
            <tr>
                <th><?php _e('Timestamp', 'servebolt-wp'); ?></th>
                <th><?php _e('Age', 'servebolt-wp'); ?></th>
                <?php if (isset($selectedType) && $selectedType === 'http'): ?>
                    <th><?php _e('IP', 'servebolt-wp'); ?></th>
                <?php endif; ?>
                <th><?php _e('Error', 'servebolt-wp'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $entry) : ?>
                <?php
                    $rowClass = '';
                    if (is_object($entry) && isset($entry->level) && is_string($entry->level)) {
                        $lvl = strtolower($entry->level);
                        if (in_array($lvl, ['fatal','warning','deprecated','error'], true)) {
                            $rowClass = 'sb-level-' . $lvl;
                        }
                    }
                ?>
                <tr class="<?php echo esc_attr($rowClass); ?>">
                    <?php if ($unparsedLine = arrayGet('unparsed_line', $entry)): ?>
                        <?php if (isset($selectedType) && $selectedType === 'http'): ?>
                            <td colspan="4" title="Could not parse error line, showing raw content"><?php echo esc_html($unparsedLine); ?></td>
                        <?php else: ?>
                            <td colspan="3" title="Could not parse error line, showing raw content"><?php echo esc_html($unparsedLine); ?></td>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php $ts = is_string($entry->date) ? strtotime($entry->date) : 0; ?>
                        <td><?php echo esc_html($entry->date); ?></td>
                        <td><?php echo $ts ? esc_html($humanDiff($ts, $nowTs) . ' ' . __('ago', 'servebolt-wp')) : '—'; ?></td>
                        <?php if (isset($selectedType) && $selectedType === 'http'): ?>
                            <td><?php echo isset($entry->ip) ? esc_html((string) $entry->ip) : '—'; ?></td>
                        <?php endif; ?>
                        <td>
                            <?php echo esc_html((string) $entry->error); ?>
                            <?php if (is_object($entry) && !empty($entry->trace_lines) && is_array($entry->trace_lines)): ?>
                                <?php $traceCount = count($entry->trace_lines); ?>
                                <details style="margin-top:6px;">
                                    <summary><?php echo esc_html(sprintf(__('Stack trace (%d lines)', 'servebolt-wp'), $traceCount)); ?></summary>
                                    <pre style="margin:8px 0 0; padding:8px; background:#f6f7f7; border:1px solid #e0e0e0; border-radius:4px; overflow:auto; white-space:pre-wrap; word-break:break-word;">
<?php echo esc_html(implode("\n", array_map('strval', $entry->trace_lines))); ?>
                                    </pre>
                                </details>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="sb-pagination">
                <?php
                    $prev = max(1, $currentPage - 1);
                    $next = min($totalPages, $currentPage + 1);
                ?>
                <a class="sb-page-link" href="<?php echo esc_url($mk($prev)); ?>">&laquo; <?php echo esc_html(__('Prev', 'servebolt-wp')); ?></a>
                <span class="sb-page-spacer"><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'servebolt-wp'), $currentPage, $totalPages)); ?></span>
                <a class="sb-page-link" href="<?php echo esc_url($mk($next)); ?>"><?php echo esc_html(__('Next', 'servebolt-wp')); ?> &raquo;</a>
            </div>
        <?php endif; ?>
	<?php endif; ?>
</div>
