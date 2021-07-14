import ServeboltCachePurgeMenuComponent from './block-editor-cache-purge-menu-component.js';
import ServeboltCachePurgeMenuElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

if (sb_ajax_object_block_editor_menu.cacheFeatureActive) {
  registerPlugin('servebolt-optimizer', {
    icon: <ServeboltCachePurgeMenuElementIcon />,
    render: ServeboltCachePurgeMenuComponent,
  });
}
