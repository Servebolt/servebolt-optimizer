=== Servebolt Optimizer ===
Contributors: audunhus, erlendeide, robsat91, servebolt
Tags: performance, optimization, cache, cloudflare, log, multisite, wp-cli, full page cache
Donate link: https://servebolt.com
Requires at least: 4.9.2
Tested up to: 5.7
Requires PHP: 7.3
Stable tag: 2.1.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds functionality to implement Servebolt WordPress best practices. This includes database optimizations, log review, automatic cache purging, automatic image optimization/resizing, performance recommendations and support for down stream full page caching.

This project is maintained on [Github](https://servebo.lt/sog).

== Description ==
= Features =
- Database optimization - Convert tables to InnoDB
- Database optimization - Add performance improving indexes
- Automatic cache purge with Cloudflare and Servebolt Accelerated Domain
- Cloudflare Image Resize-support (beta feature)
- Recommendations on additional performance improvements
- Rewrite headers to allow down stream full page caching (Servebolt clients only)
- View Apache/PHP error log (Servebolt clients only)
- Multisite support
- WP CLI support

Read more about the plugin and its features in our [help center](https://servebo.lt/servebolt-optimize-documentation).

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

=== Filter and constant reference ===
The plugin has various filters and PHP constants that allows third-party developers to alter the behaviour of the plugin. Please read the article [Filters and PHP constants](https://servebo.lt/servebolt-optimizer-filters-and-php-constants) in our help center to learn more.

== Changelog ==
= 3.0.0 =
* Rewritten codebase - The whole plugin code base is rewritten. This was done since the previous structure did not allow for automated testing (using PHP Unit) nor was it up to par with modern PHP. To achieve this the code base was rewritten to use PSR-4 autoloading as well as making the existing code testable. The code standard was also changed to PSR-1. The new required PHP version is 7.3 or higher.
* PHP Unit tests - PHP Unit tests have been added as an attempt to prevent errors, speed up the development process, and ensure better overall code quality.
* Accelerated Domains by Servebolt - The plugin has support for activating Accelerated Domains by Servebolt. The addition of this feature affects the cache purge system which previously only worked with Cloudflare, but now also supports Accelerated Domains and its cache feature. This can be controlled by selecting the cache provider in the cache purge settings.
* CLI-commands - JSON-support - The CLI-commands now has an optional JSON return format. This can be done by adding “--format=json” to the command call. Note that the Full Page Cache-related commands do not have this support yet, but all other commands has.
* CLI-commands - Cloudflare CLI commands removed - Since the cache purge feature now works with both Cloudflare and Accelerated Domains then the feature is no longer only specific to Cloudflare. This lead to most of the Cloudflare-related commands being migrated to the “wp servebolt cache settings“-namespace. The only remaining Cloudflare-related CLI command is “wp servebolt cf setup“ which works like before, but now also has JSON return format supported as mentioned earlier.
* Improved cache purging - The cache purge feature is improved and expanded. See below.
* Cache purging of old URLs - Whenever a post/term URL is changed then the system will purge the old URL. This is useful since otherwise you would possibly get conflicting URLs and/or duplicated content.
* WooCommerce compatibility - The cache will now be purged for a WooCommerce product whenever the stock amount/status changes, like during a checkout. This is necessary to keep the stock up to date for the visitors/customers, especially on high traffic sites. Cache purging will now also purge all URLs of a variable WooCommerce product.
* Improved exception handling - Whenever there is an error during a cache purge request the system now has an improved handling of exceptions being thrown.
* WP Rocket compatibility - WP Rocket’s cache feature was previously in conflict with Servebolts own cache feature. This should now be solved since WP Rocket’s cache feature is disabled as long as the Full Page Cache-feature is active in Servebolt Optimizer. This allow users to still use the other features of WP Rocket without conflicts.
* Improved queue handling when purging cache - In the cache purge feature there is an option to use a queue that will send the list URLs (that should be cached) in delayed chunks instead of sending them all immediately. These requests would typically originate from a post update, or whenever someone does a WooCommerce checkout and the product stock changes. This update improves the handling where the amount of URLs previously would have exceeded the allowed amount according to Cloudflare.
* Third party cache purge functions - Third party developers can now call publicly available cache purge functions. This allows for purging by post, term, URL or purge all.
* Single site plugin activation constraint - The plugin can now only be activated site-wide when used in a multisite network.
* Cloudflare Image Resizing removed from the GUI - The beta-feature “Cloudflare Image Resizing” has now been removed due to it not being tested properly. It is still available through the CLI, but not in the GUI.
* Removed network cache purge action - The feature to purge all cache for all sites in a multisite-network was removed due to lack of time to integrate this with Accelerated Domains and the improved queue system.
* Removed the cache purge queue GUI - The queue GUI (list) in the cache purge settings was removed due to lack of time to integrate this with Accelerated Domains and the improved queue system.
* Cache purge links in post/term list - It is now possible to trigger cache purge actions from the row actions of posts and terms in WP Admin.
* Added purge actions for terms in the WordPress Admin bar - When viewing a term - either in WP Admin or front-end - you can now purge the cache via the Admin bar.
* Added custom HTTP User Agent string to API requests - The outgoing requests from the plugin now uses a custom user agent string that will allow for easier identification of requests originating from the plugin.
* Log viewer GUI update - The log viewer GUI has gotten overhauled with better styling.
* Fixed bug - The form containing the cache purge configuration had autocompletion on. This lead to problems with information wrongfully being submitted. This is now fixed.
* Fixed bug - Host determination function failed when in CLI context. The function “isHostedAtServebolt“ returned false regardless of hosting environment when running in CLI-context. This is fixed in this version.
* Fixed bug - Cache headers absent in archives. When using Full page caching and setting the post type to “all” then the archives got not cache headers. This version fixes that.

= 2.1.5 =
* Hotfix in Cloudflare cache purge feature

= 2.1.4 =
* Added basic Cloudflare APO-support
* Changed order of URLs when purging cache for a post in Cloudflare
* Fixed bug in Full Page Cache-logic for archives
* Fixed bug in Cloudflare Image Resize

= 2.1.3 =
* Fixed styling issue in Gutenberg-editor sidebar menu

= 2.1.2 =
* Fixed styling issue in Gutenberg-editor sidebar menu

= 2.1.2 =
* Fixed JavaScript error related to the Gutenberg-editor

= 2.1.1 =
* Fixed issue with script inclusion causing errors
* Added missing CLI argument for FPC deactivation

= 2.1 =
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
