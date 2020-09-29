=== Servebolt Optimizer ===
Contributors: audunhus, erlendeide, robsat91, servebolt
Tags: performance, optimization, cache, cloudflare, log, multisite, wp-cli, full page cache
Donate link: https://servebolt.com
Requires at least: 4.9.2
Tested up to: 5.5.1
Requires PHP: 7
Stable tag: 2.1.2
License: GPLv3 or later
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
