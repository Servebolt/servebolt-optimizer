import '../../css/plugin.scss';

import PluginComponent from './plugin-component.js';
import ElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

// More info:
// https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-plugins/
registerPlugin( 'servebolt-optimizer', {
	icon: <ElementIcon />,
	render: PluginComponent,
} );