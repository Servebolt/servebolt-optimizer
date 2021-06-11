import ServeboltCachePurgeMenuComponent from './gutenberg-cache-purge-menu-component.js';
import ServeboltCachePurgeMenuElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

registerPlugin('servebolt-optimizer', {
  icon: <ServeboltCachePurgeMenuElementIcon />,
  render: ServeboltCachePurgeMenuComponent,
});
