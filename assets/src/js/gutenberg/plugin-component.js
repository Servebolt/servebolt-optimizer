const { _x } = wp.i18n;
const { Fragment } = wp.element;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
const {
	Button,
} = wp.components;

const PluginComponent = () => (

	// Every component must have only one parent element.
	// In this case, the parent element is a Fragment.
	<Fragment>
		<PluginSidebarMoreMenuItem target="sidebar-name">
			{ _x( 'Servebbolt Optimizer', 'text', 'sb-optimizer' ) }
		</PluginSidebarMoreMenuItem>
		<PluginSidebar name="sidebar-name" title="Servebolt Optimizer">

			<Button className="sb-button yellow" onClick={ () => window.sb_purge_post_cache_with_auto_resolve() }>
				{ _x( 'Purge current post cache', 'text', 'sb-optimizer' ) }
			</Button>

			<Button className="sb-button yellow" onClick={ () => window.sb_purge_all_cache() }>
				{ _x( 'Purge all cache', 'text', 'sb-optimizer' ) }
			</Button>

			<Button className="sb-button yellow" onClick={ () => window.sb_purge_url_cache() }>
				{ _x( 'Purge a URL', 'text', 'sb-optimizer' ) }
			</Button>

		</PluginSidebar>
	</Fragment>
);
export default PluginComponent;
