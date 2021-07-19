import ServeboltCachePurgeMenuComponent from './block-editor-cache-purge-menu-component.js';
import ServeboltCachePurgeMenuElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

if (
    sb_ajax_object_block_editor_menu.cacheFeatureActive
    && (
        sb_ajax_object_block_editor_menu.canPurgePostCache
        || sb_ajax_object_block_editor_menu.canPurgeAllCache
        || sb_ajax_object_block_editor_menu.canPurgeCacheByUrl
    )
) {
  registerPlugin('servebolt-optimizer', {
    icon: <ServeboltCachePurgeMenuElementIcon />,
    render: ServeboltCachePurgeMenuComponent,
  });
}
