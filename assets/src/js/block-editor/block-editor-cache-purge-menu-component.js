const { _x, _n, sprintf } = wp.i18n;
const { Fragment, Component } = wp.element;
const { compose } = wp.compose;
const { registerStore, withDispatch, withSelect, dispatch, select } = wp.data;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
const { Button } = wp.components;

/**
 * Check whether the post status is purgeable.
 *
 * @param {string} status The status to be checked
 * @returns {boolean} Whether the post status is considered purgeable.
 */
function statusIsPurgeable(status) {
  const nonCachePurgeablePostStatuses = [ 'auto-draft', 'draft' ];
  return ! nonCachePurgeablePostStatuses.includes(status);
}

/**
 * Get the initial post status.
 *
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
 * @returns {boolean|string} Singular post type name.
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
  const currentStatus = getCurrentPostStatus();
  if (typeof currentStatus !== 'undefined') {
    const isPurgeable = statusIsPurgeable(currentStatus),
      currentIsPurgeable = select('servebolt-optimizer/cache-purge-buttons').getPurgeableState();
    if (isPurgeable !== currentIsPurgeable) {
      dispatch('servebolt-optimizer/cache-purge-buttons').setPurgeableState(isPurgeable);
    }
  }
});

/**
 * Run an interval until we can resolve the post type singular name, and then assign it to our cache purge button.
 *
 * @type {number}
 */
const interval = setInterval(function() {
  if (getPostTypeName()) {
    clearInterval(interval);
    dispatch('servebolt-optimizer/cache-purge-buttons').setPostTypeNameValue(getPostTypeName().toLowerCase());
  }
}, 500);

const DEFAULT_STATE = {
  postTypeName: 'post',
  isPurgeable: statusIsPurgeable(getInitialPostStatus()),
};

const reducer = (state = DEFAULT_STATE, action) => {
  switch (action.type) {
    case 'SET_POST_TYPE_NAME':
      return {
        ...state,
        postTypeName: action.postTypeName,
      };
    case 'SET_PURGEABLE_STATE':
      return {
        ...state,
        isPurgeable: action.isPurgeable,
    };
  }
  return state;
};

const actions = {
  setPostTypeNameValue(postTypeName) {
    return {
      type: 'SET_POST_TYPE_NAME',
      postTypeName,
    };
  },
  setPurgeableState(isPurgeable) {
    return {
      type: 'SET_PURGEABLE_STATE',
      isPurgeable,
    };
  },
};

const selectors = {
  getPostTypeNameValue(state) {
    return state.postTypeName;
  },
  getPurgeableState(state) {
    return state.isPurgeable;
  },
};

registerStore('servebolt-optimizer/cache-purge-buttons', {
  reducer,
  actions,
  selectors,
});

class ServeboltCachePurgeMenuComponent extends Component {
    render() {
        const { postTypeName, isPurgeable } = this.props;
        return <Fragment>
            <PluginSidebarMoreMenuItem target="sidebar-name">
                {_x('Servebolt Optimizer', 'text', 'sb-optimizer')}
            </PluginSidebarMoreMenuItem>
            <PluginSidebar name="sidebar-name" className="servebolt-optimizer-cache-bust-panel" title="Servebolt Optimizer">
                {servebolt_optimizer_ajax_object_block_editor_menu.canPurgePostCache && isPurgeable &&
                <Button className="sb-button yellow" onClick={ () => window.sbPurgePostCacheWithAutoResolve(postTypeName) }>
                    { sprintf(_n('Purge %s cache', 'sb-optimizer'), postTypeName) }
                </Button>
                }
                {servebolt_optimizer_ajax_object_block_editor_menu.canPurgeAllCache &&
                <Button className="sb-button yellow" onClick={ () => window.sb_purge_all_cache() }>
                    { _x('Purge all cache', 'text', 'sb-optimizer') }
                </Button>
                }

                {servebolt_optimizer_ajax_object_block_editor_menu.canPurgeCacheByUrl &&
                <Button className="sb-button yellow" onClick={() => window.sb_purge_url_cache()}>
                  {_x('Purge a URL', 'text', 'sb-optimizer')}
                </Button>
                }

            </PluginSidebar>
        </Fragment>
    }
};

export default compose([
  withDispatch((dispatch, props) => {
    const { setPostTypeNameValue, setPurgeableState } = dispatch('servebolt-optimizer/cache-purge-buttons');
    return {
      setPostTypeNameValue,
      setPurgeableState,
    };
  }),
  withSelect((select, props) => {
    const { getPostTypeNameValue, getPurgeableState } = select('servebolt-optimizer/cache-purge-buttons');
    return {
      postTypeName: getPostTypeNameValue(),
      isPurgeable: getPurgeableState(),
    };
  }),
])(ServeboltCachePurgeMenuComponent);
