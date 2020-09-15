import '../../css/admin/plugin.scss';

import PluginComponent from './plugin-component.js';
import ElementIcon from '../../images/icon.svg';

const { registerPlugin } = wp.plugins;

// More info:
// https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-plugins/
registerPlugin( 'plugin-name', {
	icon: <ElementIcon />,
	render: PluginComponent,
} );
