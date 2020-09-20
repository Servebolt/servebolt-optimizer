=== Servebolt Optimizer ===
Contributors: audunhus, erlendeide, robsat91, servebolt
Tags: performance, optimization, cache, cloudflare, log, multisite, wp-cli, full page cache
Donate link: https://servebolt.com
Requires at least: 4.9.2
Tested up to: 5.5.1
Requires PHP: 7
Stable tag: 2.0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds functionality to implement Servebolt WordPress best practices. This includes database optimizations, log review, automatic cache purging, automatic image optimization/resizing, performance recommendations and support for down stream full page caching.

This project is maintained on [Github](https://servebo.lt/sog).

== Description ==
= Features =
- Database optimization - Convert tables to InnoDB
- Database optimization - Add performance improving indexes
- Automatic Cloudflare cache purge
- Cloudflare Image Resize-support (beta feature)
- Recommendations on additional performance improvements
- Rewrite headers to allow down stream full page caching (Servebolt clients only)
- View Apache/PHP error log (Servebolt clients only)
- Multisite support
- WP CLI support

= Automatic purge of Cloudflare cache =
Full Page Cache is automatically purged when a post/term has been updated. You also have a purge-feature in the admin bar. This Cloudflare integration supports both the use of API key and API token authentication when communicating with the Cloudflare API.

= Full Page Caching =
This plugin rewrites HTTP headers of HTML to allow for Full Page Caching, and for the browser to cache HTML. Full Page Caching may introduce all sorts of problems for end users, so installation and testing should be performed by a professional.

== Installation ==
1. Download this plugin and unzip
1. Upload servebolt-optimizer folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Servebolt > Performance Optimizer and run optimizations if necessary

With WP-CLI
1. Log in to your site with SSH
2. cd to your sites root wordpress folder
3. Run `wp plugin install servebolt-optimizer --activate`
4. Run optimizations `wp servebolt db optimize`

Run `wp help servebolt` to see all the available commands that can configure the plugin.

== Configuration ==
This plugin can be controlled via WP Admin, WP CLI, various filters and PHP constants.

=== Filter overviews ===
The plugin uses various filters to allow third-party developers to alter the behaviour of the plugin. See the list below:

`sb_optimizer_add_gutenberg_plugin_menu` (boolean)
Whether to display the plugin menu in the Gutenberg editor.

`sb_optimizer_fpc_should_debug_headers` (boolean)
Whether to print debug headers related to the full page cache (only for Servebolt-hosted sites).

`sb_optimizer_fpc_woocommerce_pages_no_cache_bool` (boolean)
In full page cache context - use this boolean to override whether the current request is to a WooCommerce page that should not be cached.

`sb_optimizer_fpc_woocommerce_pages_cache_bool` (boolean)
In full page cache context - use this boolean to override whether the current request is to a WooCommerce page that should be cached.

`sb_optimizer_display_admin_bar_menu` (boolean)
Whether to display the plugin admin bar menu item.

`sb_optimizer_display_admin_bar_menu_by_user_capabilities` (boolean)
Use this filter to control which users (based on capabilities) that can see the admin bar.

`sb_optimizer_display_network_super_admin_menu` (boolean)
Whether to display the network admin menu.

`sb_optimizer_display_subsite_menu` (boolean)
Whether to display the subsite admin menu.

`sb_optimizer_display_single_site_admin_menu` (boolean)
Whether to display the admin menu on a single site.

`sb_optimizer_purge_item_list_limit` (integer)
The number of items to display in the cache purge list.

`sb_optimizer_log_file_path` (string)
The path of the log file - only available when hosted in Servebolt.

`sb_optimizer_cf_cache_form_validation_active` (boolean)
Whether to use JavaScript-based validation when editing the Cloudflare cache configuration.

`sb_optimizer_should_generate_other_urls` (boolean)
Whether to generate other URLs (archives, front page) when busting Cloudflare cache for a WP object.

`sb_optimizer_alter_urls_for_cache_purge_object` (array)
Use this filter to alter the URLs when purging Cloudflare cache.

`sb_optimizer_disable_automatic_purge` (boolean)
Whether to disable the automatic Cloudflare cache purge feature (for example when saving a post etc.).

`sb_optimizer_automatic_purge_on_post_save` (boolean)
Whether to purge Cloudflare purge automatically on post update.

`sb_optimizer_automatic_purge_on_comment` (boolean)
Whether to purge Cloudflare purge automatically on comment post.

`sb_optimizer_automatic_purge_on_comment_approval` (boolean)
Whether to purge Cloudflare purge automatically on comment approval.

`sb_optimizer_automatic_purge_on_term_save` (boolean)
Whether to purge Cloudflare purge automatically on term update.

`sb_optimizer_should_purge_term_cache` (boolean)
Use this filter to override the check that decides whether we should purge term cache in Cloudflare or not.

`sb_optimizer_should_purge_post_cache` (boolean)
Use this filter to override the check that decides whether we should purge post cache in Cloudflare or not.

`sb_optimizer_prevent_cache_purge_on_unapproved_comments` (boolean)
Whether we should purge cache on unapproved comment post.

`sb_optimizer_comment_approved_cache_purge` (boolean)
Whether a comment is considered as approved on comment post in the context of our automatic cache purge.

`sb_optimizer_cf_image_resize_alter_srcset` (boolean)
Whether to alter the srcset-attribute when using the Cloudflare image resize feature.

`sb_optimizer_cf_image_resize_alter_src` (boolean)
Whether to alter the src-attribute when using the Cloudflare image resize feature.

`sb_optimizer_cf_image_resize_alter_intermediate_sizes` (boolean)
Whether to affect the generation of image sizes when uploading an image.

`sb_optimizer_cf_image_resize_always_create_sizes` (array)
When generating images and using the Cloudflare image resize feature, then use this filter to decide which image sizes that should always be created files for.

`sb_optimizer_cf_image_resize_upscale_images` (boolean)
Whether to use upscale the images when using the Cloudflare image resize-feature.

`sb_optimizer_cf_image_resize_max_width` (float)
Cloudflare image resize max width.

`sb_optimizer_cf_image_resize_max_height` (float)
Cloudflare image resize max height.

`sb_optimizer_cf_image_resize_url` (string)
The image URL after modification when using the Cloudflare image resize feature.

`sb_optimizer_cf_image_resize_default_params_additional` (array)
The additional URL parameters (before arguments are merged with default arguments) used when modifying the URL to use Cloudflare image resize.

`sb_optimizer_cf_image_resize_default_params` (array)
The default URL parameters (before arguments are merged with additional arguments) used when modifying the URL to use Cloudflare image resize.

`sb_optimizer_cf_image_resize_default_params_concatenated` (array)
The URL parameters (after arguments are merged with default arguments) used when modifying the URL to use Cloudflare image resize.

`sb_optimizer_cf_image_resize_upscale_dimensions` (array)
When using the Cloudflare image resize feature - use this filter to adjust the upscale dimensions of the image.

`sb_optimizer_should_clean_cache_purge_queue` (boolean)
Whether to run automatic cache purge queue cleaning of items older than 7 days.

`sb_optimizer_clean_cache_purge_queue_time_threshold` (integer)
The timestamp (uses the timezone set in the WP settings) used to determine if a cache purge queue item is old and should be deleted.

`sb_optimizer_should_purge_cache_queue` (boolean)
Whether the system should parse the cache purge queue and execute the cache purge.

Whether to debug the request to the Cloudflare API.
`sb_optimizer_cf_api_request_debug` (boolean)

`sb_optimizer_max_number_of_urls_to_be_purged` (integer)
Limit the number of URLs being sent for purging in the Cloudflare API. This is due to limitations in the Cloudflare API - maximum 30 URLs per purge request. Using this filter is recommended, but it will prevent an error until a better solution is implemented.

`sb_optimizer_urls_to_be_purged` (array)
The array of URLs to be purged in Cloudflare.

`sb_optimizer_mcrypt_key` (array)
The mcrypt key/secret used to store options.

`sb_optimizer_openssl_keys` (array)
The OpenSSL key/secret used to store options.

`sb_optimizer_evaluate_multidomain_setup` (array)
When checking if a multisite contains multiple domains, use this filter to alter the result of the domain lookup.

`sb_optimizer_evaluate_multidomain_setup_conclusion` (boolean)
A boolean value concluding whether the multisite-setup contains multiple domains.

`sb_optimizer_get_option_[option name]` (string)
Filter for value of option.

`sb_optimizer_get_blog_option_[option name]` (string)
Filter for value of blog option.

`sb_paginate_links_as_array_args` (array)
The arguments passed to the function "paginate_links" when generating pagination links in the context of the Cloudflare cache purge-feature.

`sb_optimizer_skip_generic_optimizations` (boolean)
Whether to apply the generic optimizations.

`sb_optimizer_ajax_user_allowed` (boolean)
Used to control the allowance (user capabilities) to send AJAX request to the AJAX endpoints of this plugin.

`sb_optimizer_skip_pages_needed_request` (boolean)
When busting cache witt Cloudflare, when looking up URLs to an archive and the number of pages in pagination - use this boolean to disable the pagination lookup feature (this feature can be slow on slow sites). When set to false then the number of pages will revert to 250, but this number can be overridden with the filter "sb_optimizer_pages_needed_override".

`sb_optimizer_pages_needed_override` (integer)
When busting cache witt Cloudflare, this is the number of pages-number used when generating the paginated URLs to an archive. Used when the filter "sb_optimizer_skip_pages_needed_request" is set to false. Defaults to 250.

`sb_optimizer_record_max_num_pages_filter_hook` (string)
The filter used to "record" the number of pages-number when generating paginated archive URLs.

`sb_optimizer_site_iteration` (array)
Filter for when fetching all the sites in the multisite-network.

`sb_optimizer_add_version_parameter_to_asset_src` (boolean)
Whether to add version parameter to assets URLs.

`sb_optimizer_version_parameter_name` (string)
The string used to create the version parameter for asset URLs.

`sb_optimizer_asset_base_path` (string)
The base path used when converting an asset URL to asset path.

`sb_optimizer_asset_parsed_url_path` (string)
The URL path used when converting an asset URL to asset path.

`sb_optimizer_add_version_parameter_to_script_src` (boolean)
Whether to add version parameter to asset script URLs.

`sb_optimizer_add_version_parameter_to_style_src` (boolean)
Whether to add version parameter to asset style URLs.

`sb_optimizer_add_version_parameter_to_script_src_[handle]` (boolean)
Whether to add version parameter to the asset script URL for a certain handle.

`sb_optimizer_add_version_parameter_to_style_src_[handle]` (boolean)
Whether to add version parameter to the asset style URL for a certain handle.

`sb_optimizer_asset_url_to_path_conversion` (string)
The converted path of an asset URL.

`sb_optimizer_asset_url_to_path_conversion_[handle]` (string)
The converted path of an asset URL for a certain handle.

`sb_optimizer_throttle_queue_items_on_cron_purge` (boolean)
When using the cache purge cron feature - set this to true to throttle the amount of items that gets parsed at a time.

`sb_optimizer_throttle_queue_max_items` (integer)
Use this filter in relation to the filter "sb_optimizer_throttle_queue_items_on_cron_purge" to control the amount of items to be included in each purge request.

=== Constant overview ===
The plugin also has various php constants that allows third-party developers to alter the behaviour of the plugin. See the list below:

`SERVEBOLT_FPC_CACHE_TIME` (integer)
Used to set the "Expires"-header. Defaults to 600 seconds. Only active for Servebolt-customers when using full page cache.

`SERVEBOLT_BROWSER_CACHE_TIME` (integer)
Used to set the "max-age"-parameter in the "Cache-Control"-header. Defaults to 600 seconds. Only active for Servebolt-customers when using full page cache.

`SB_CF_REQUEST_DEBUG` (boolean)
Whether to debug Cloudflare API request data to the error log.

`SERVEBOLT_CF_PURGE_CRON` (boolean)
Whether to use the WP cron to purge the cache (the alternative is that the cache purge happens immediately, without any queue).

`SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE` (boolean)
Whether to execute cache purge on items in the queue. Can be used to only queue up items for cache purge, but not execute the cache purge.

`SERVEBOLT_CF_PURGE_CRON_CLEANER_ACTIVE` (boolean)
Whether to clean the cache purge queue items that are older than 7 days (amount of days can be changed).

`SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE` (boolean)
Whether to activate the Cloudflare Image Resize feature (beta).

== Changelog ==
= 2.0.10 =
* Added extended cache invalidation for Cloudflare cache - archives and other related URLs will also be purged.
* Added a SweetAlert-fallback so that native JavaScript alerts, prompts, confirmations will be used instead. This is due to SweetAlert being prone to conflicts with themes and other plugins.
* Made the Cloudflare Image Resize-feature available (through WP CLI and PHP constant).
* Completed the WP CLI with more configuration commands.
* Added more meta-data to the cache purge queue when using the Cloudflare cache purge feature + added a cleaner that will remove old elements if not automatically purged.
* Added full overview over available PHP constants and filters.
* Various bug fixes
* Various GUI improvements
* Bug fix for WooCommerce-implementations (corrected cache headers for WooCommerce-pages).

= 2.0.9 =
* Hotfix related to the admin bar

= 2.0.8 =
* Improved GUI - now possible to purge cache (via the admin bar menu) for single pages when viewing / editing
* Fixed minor CF settings page validation bug

= 2.0.7 =
* Bugfix in comment cache purge

= 2.0.6 =
* Improved feedback when purging cache
* Code cleanup and refactor
* Various bugfixes
* Added automatic cache purge on comment post/approval

= 2.0.5 =
* Swapped Guzzle with WP HTTP API to prevent namespace conflicts with other plugins also using Guzzle.

= 2.0.4 =
* Bugfix in function that checks whether current site is hosted at Servebolt.

= 2.0.3 =
* Various bugfixes and improvements to WP CLI-commands

= 2.0.2 =
* Various bugfixes

= 2.0.1 =
* Various bugfixes

= 2.0 =
* [Added Automatic Cloudflare cache purge feature](https://servebo.lt/5z7xw)
* Major code refactor

= 1.6.4 =
* Minor bugfix

= 1.6.3 =
* Minor bugfix

= 1.6.2 =
* Minor bugfix

= 1.6.1 =
* Removed security from dashboard

= 1.6 =
* New: Control Full page cache settings with WP CLI (`wp servebolt fpc`)
* Improvement: Turn off vulnerable plugins check with `define('SERVEBOLT_VULN_ACTIVATE', false);`
* Removed: Scanning of plugins for security vulnerabilities. This will be released in a separate plugin.
* Removed: Transient cleaner
* Added a exit if installed on PHP versions lower than 7

= 1.5.1 =
* Bugfix: Unable to add indexes on non-multisite installs

= 1.5 =
* Added multisite support
* Fixed a bug in the wpvulndb security checker
* Added a nice animation when optimizer runs
* Updated readme.txt


= 1.4.2 =
* Important bugfix

= 1.4.1 =
* Important bugfix

= 1.4 =
* Github #8 Added a transients cleaner to wp-cron
* Added transients cleaner to WP-CLI
* Changes to WP CLI commands
* NEW: Added a view to see vulnerabilities in WordPress and plugins from WPVULNDB.COM
* NEW: Added email notifications when WP or plugins is vulnerable

= 1.3.4 =
* added on/off switch for Nginx cache
* remove WP version number and generator tag
* skip concatenation of admin scripts, we use http2
* Added WP-CLI support
* issues on Github #8 added uninstall.php + bug fixes #7 #9
* added changelog to Readme.txt
