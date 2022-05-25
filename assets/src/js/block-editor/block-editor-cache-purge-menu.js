import ServeboltCachePurgeMenuComponent from './block-editor-cache-purge-menu-component.js';
import ServeboltCachePurgeMenuElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

if (
    servebolt_optimizer_ajax_object_block_editor_menu.cacheFeatureActive
    && (
        servebolt_optimizer_ajax_object_block_editor_menu.canPurgePostCache
        || servebolt_optimizer_ajax_object_block_editor_menu.canPurgeAllCache
        || servebolt_optimizer_ajax_object_block_editor_menu.canPurgeCacheByUrl
    )
) {
  registerPlugin('servebolt-optimizer', {
    icon: <ServeboltCachePurgeMenuElementIcon />,
    render: ServeboltCachePurgeMenuComponent,
  });
}
