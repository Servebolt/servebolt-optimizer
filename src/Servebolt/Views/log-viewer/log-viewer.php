<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>
<div class="wrap">
	<h2><?php echo $pageTitle; ?></h2>
    <?php
        // Build tab navigation (PHP / HTTP)
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (function_exists('remove_query_arg')) {
            $tabsBaseUrl = remove_query_arg(['level'], $currentUrl);
        } else {
            $tabsBaseUrl = $currentUrl;
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
                        $tabUrl = add_query_arg(['log' => $type], $tabsBaseUrl);
                    } else {
                        $sep = (strpos($tabsBaseUrl, '?') === false) ? '?' : '&';
                        $tabUrl = preg_replace('/([&?])level=[^&#]*/', '$1', $tabsBaseUrl);
                        $tabUrl = preg_replace('/([&?])log=[^&#]*/', '$1', $tabUrl);
                        $tabUrl .= $sep . 'log=' . urlencode($type);
                    }
                ?>
                <a href="<?php echo esc_url($tabUrl); ?>" class="nav-tab <?php echo $isActive ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
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
        .sb-filter-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:10px 0 6px; }
        .sb-filter-chips { display:flex; flex-wrap:wrap; gap:8px; }
        .sb-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border:1px solid #dcdcde; border-radius:999px; background:#fff; color:#1d2327; text-decoration:none; line-height:1.6; }
        .sb-chip:hover { background:#f6f7f7; border-color:#b3b6b9; color:#1d2327; }
        .sb-chip.active { background:#2271b1; border-color:#2271b1; color:#fff; }
        .sb-chip.active .sb-level-badge { background:rgba(255,255,255,0.2); color:#fff; }
        .sb-chip .sb-level-dot { margin-right:2px; }
        .sb-filter-actions { display:flex; align-items:center; gap:12px; }
        .sb-filter-note { color:#50575e; }
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
        <?php if (!empty($levels)) : ?>
            <div class="sb-filter-toolbar">
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
                        <a href="<?php echo esc_url($toggleUrl); ?>" class="button button-secondary">
                            <?php echo $hide ? __('Show Deprecations', 'servebolt-wp') : __('Hide Deprecations', 'servebolt-wp'); ?>
                        </a>
                        <span class="sb-filter-note">
                            <?php echo $hide ? __('Deprecations are hidden', 'servebolt-wp') : __('Deprecations are shown', 'servebolt-wp'); ?>
                        </span>
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
        <p><?php printf( __('This table lists the %s last entries from today\'s error log', 'servebolt-wp'), $numberOfEntries); ?>:</p>
        <table class="wp-list-table widefat striped posts sb-log">
            <thead>
            <tr>
                <th><?php _e('Timestamp', 'servebolt-wp'); ?></th>
                <th><?php _e('Age', 'servebolt-wp'); ?></th>
                <th><?php _e('IP', 'servebolt-wp'); ?></th>
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
                    <td colspan="100%" title="Could not parse error line, showing raw content"><?php echo esc_html($unparsedLine); ?></td>
                    <?php else: ?>
                    <?php $ts = is_string($entry->date) ? strtotime($entry->date) : 0; ?>
                    <td><?php echo esc_html($entry->date); ?></td>
                    <td><?php echo $ts ? esc_html($humanDiff($ts, $nowTs) . ' ' . __('ago', 'servebolt-wp')) : '—'; ?></td>
                    <td><?php echo isset($entry->ip) ? esc_html((string) $entry->ip) : '—'; ?></td>
                    <td><?php echo esc_html((string) $entry->error); ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
	<?php endif; ?>
</div>
