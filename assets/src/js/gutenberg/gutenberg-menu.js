import '../../css/gutenberg-menu.scss';

import ServeboltCachePurgeMenuComponent from './gutenberg-menu-component.js';
import ServeboltCachePurgeMenuElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

registerPlugin('servebolt-optimizer', {
  icon: <ServeboltCachePurgeMenuElementIcon />,
  render: ServeboltCachePurgeMenuComponent,
});
