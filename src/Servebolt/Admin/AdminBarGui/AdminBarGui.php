<?php

namespace Servebolt\Optimizer\Admin\AdminBarGui;

if (!defined('ABSPATH')) exit;

use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class AdminBarGui
 *
 * This class initiates the WP Admin bar item for the Optimizer plugin.
 */
class AdminBarGui
{
    use Singleton;

    /**
     * Array of menu nodes.
     *
     * @var null|array
     */
    private $nodes = null;

    /**
     * WP_Admin_Bar-instance.
     *
     * @var null|WP_Admin_Bar
     */
    private $wpAdminBar = null;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * AdminBarGui constructor.
     */
    private function __construct()
    {
        add_action('admin_bar_menu', [$this, 'adminBar'], 100);
    }

    /**
     * Add our items to the admin bar.
     *
     * @param WP_Admin_Bar $wpAdminBar
     * @return void|null
     */
    public function adminBar($wpAdminBar)
    {
        if (!apply_filters('sb_optimizer_display_admin_bar_menu', true, $wpAdminBar)) {
            return;
        }
        $this->wpAdminBar = $wpAdminBar;

        if (!$this->hasNodes()) {
            return;
        }

        $this->addNodes();
    }

    /**
     * Initialize menu nodes.
     *
     * @return array
     */
    private function initNodes(): array
    {
        $allNodes = [];
        $nodeClassFiles = glob(__DIR__ . '/Nodes/*.php');
        foreach ($nodeClassFiles as $nodeClassFile) {
            require_once $nodeClassFile;
            $fileNameParts = explode('_', basename($nodeClassFile, '.php'));
            $className = end($fileNameParts);
            $class = __NAMESPACE__ . '\\Nodes\\' . $className;
            if ($class::shouldDisplayNodes() && $nodes = $class::generateNodes()) {
                $allNodes = array_merge($allNodes, $nodes);
            }
        }
        return $allNodes;
    }

    /**
     * Add the nodes to the WP Admin bar.
     */
    private function addNodes(): void
    {
        $nodes = $this->getNodes();

        // Add parent item if we got more than one node
        if (count($nodes) > 1) {
            $parentId = 'servebolt-optimizer';
            $nodes = array_map(function($node) use ($parentId) {
                $node['parent'] = $parentId;
                return $node;
            }, $nodes);
            $nodes = array_merge([
                [
                    'id' => $parentId,
                    'title' => __('Servebolt Optimizer', 'servebolt-wp'),
                    'href' => false,
                ]
            ], $nodes);
        }

        // Add the Servebolt-icon to first menu element
        if (isset($nodes[0])) {
            $nodes[0]['title'] = $this->iconMarkup() . $nodes[0]['title'];
        }

        // Add nodes to the WP Admin bar
        foreach ($nodes as $node) {
            $this->wpAdminBar->add_node($node);
        }
    }

    private function hasNodes(): bool
    {
        if (is_null($this->nodes)) {
            $this->nodes = $this->initNodes();
        }
        return !empty($this->nodes);
    }

    private function getNodes(): ?array
    {
        if (is_null($this->nodes)) {
            $this->nodes = $this->initNodes();
        }
        return $this->nodes;
    }

    /**
     * Get the markup used for the icon in the WP Admin bar.
     *
     * @return string
     */
    private function iconMarkup(): string
    {
        return '<span class="servebolt-icon"></span>';
    }
}
