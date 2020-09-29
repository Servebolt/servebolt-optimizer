import '../../css/gutenberg-menu.scss';

import GutenbergMenuComponent from './gutenberg-menu-component.js';
import ElementIcon from '../../images/servebolt-icon.svg';

const { registerPlugin } = wp.plugins;

// More info:
// https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-plugins/
registerPlugin( 'servebolt-optimizer', {
	icon: <ElementIcon />,
	render: GutenbergMenuComponent,
} );
