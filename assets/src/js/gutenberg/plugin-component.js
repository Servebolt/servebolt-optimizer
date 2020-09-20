const { _x } = wp.i18n;
const { Fragment } = wp.element;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
const {
	Button,
} = wp.components;

/**
 * Check whether a post status is considered public.
 *
 * @param status
 * @returns {boolean}
 */
function statusIsPublic(status) {
	var non_cache_purgeable_post_statuses = ['auto-draft'];
	return ! non_cache_purgeable_post_statuses.includes(status);
	//var public_post_statuses = ['publish'];
	//return public_post_statuses.includes(status);
}

/**
 * Get the initial post status.
 *
 * @returns {*}
 */
function getInitialPostStatus() {
	const element = document.getElementById('original_post_status');
	return element ? element.value : false;
}

/**
 * Get the current post status.
 *
 * @returns {*}
 */
function getCurrentPostStatus() {
	return wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
}

let isPublic = statusIsPublic(getInitialPostStatus());

/**
 * Listen for events in the editor and check if the post gets published/unpublished.
 */
wp.data.subscribe(function () {
	isPublic = statusIsPublic(getCurrentPostStatus());
});

const PluginComponent = () => (

	// Every component must have only one parent element.
	// In this case, the parent element is a Fragment.
	<Fragment>
		<PluginSidebarMoreMenuItem target="sidebar-name">
			{ _x( 'Servebbolt Optimizer', 'text', 'sb-optimizer' ) }
		</PluginSidebarMoreMenuItem>
		<PluginSidebar name="sidebar-name" title="Servebolt Optimizer">

			{ isPublic &&
				<Button className="sb-button yellow" onClick={ () => window.sb_purge_post_cache_with_auto_resolve() }>
					{ _x( 'Purge current post cache', 'text', 'sb-optimizer' ) }
				</Button>
			}
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
