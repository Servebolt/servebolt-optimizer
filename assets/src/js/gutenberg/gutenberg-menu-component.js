const { _x, _n, sprintf } = wp.i18n;
const { Fragment } = wp.element;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
const {
  Button,
} = wp.components;

/**
 * Check whether the post status is purgeable.
 * @param {string} status The status to be checked
 * @returns {boolean} Whether the post status is considered purgeable.
 */
function statusIsPurgeable(status) {
  const nonCachePurgeablePostStatuses = [ 'auto-draft', 'draft' ];
  return ! nonCachePurgeablePostStatuses.includes(status);
}

/**
 * Get the initial post status.
 * @returns {*} Initial post status.
 */
function getInitialPostStatus() {
  const element = document.getElementById('original_post_status');
  return element ? element.value : false;
}

/**
 * Get the current post status.
 * @returns {*} The current post status.
 */
function getCurrentPostStatus() {
  return wp.data.select('core/editor').getEditedPostAttribute('status');
}

/**
 * Get singular post type name.
 * @returns {boolean|*}
 */
function getPostTypeName() {
    const editor = wp.data.select('core/editor'),
        _select2 = wp.data.select('core'),
        getPostType = _select2.getPostType,
        postTypeObject = getPostType(editor.getCurrentPostType());
    if (postTypeObject && postTypeObject.labels && postTypeObject.labels.singular_name) {
        return postTypeObject.labels.singular_name;
    }
    return false;
}

/**
 * Listen for events in the editor and check if the post gets published/unpublished.
 */
wp.data.subscribe(function() {
  isPurgeable = statusIsPurgeable(getCurrentPostStatus());
});

let postType = 'post';
let isPurgeable = statusIsPurgeable(getInitialPostStatus());

const ServeboltCachePurgeMenuComponent = () => (
    <Fragment>
        <PluginSidebarMoreMenuItem target="sidebar-name">
            { _x('Servebolt Optimizer', 'text', 'sb-optimizer') }
        </PluginSidebarMoreMenuItem>
        <PluginSidebar name="sidebar-name" className="servebolt-optimizer-cache-bust-panel" title="Servebolt Optimizer">
            { isPurgeable && postType &&
            <Button className="sb-button yellow" onClick={ () => window.sb_purge_post_cache_with_auto_resolve() }>
                { sprintf(_n('Purge %s cache', 'sb-optimizer'), postType) }
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

export default ServeboltCachePurgeMenuComponent;
