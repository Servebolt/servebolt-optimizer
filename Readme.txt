=== Servebolt Optimizer ===
Contributors: audunhus, erlendeide
Tags: performance, optimization, cache, log, wpvulndb, multisite, wp-cli
Donate link: https://servebolt.com
Requires at least: 4.9.2
Tested up to: 4.9.6
Requires PHP: 7
Stable tag: 1.6-alpha
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds functionality to implement Servebolt WordPress best practices. This includes database optimizations, log review, performance recommendations and support for down stream full page caching.

== Description ==
= Features =
- Database optimization - Convert tables to InnoDB
- Database optimization - Add performance improving indexes
- Recommendations on additional performance improvements
- Rewrite headers to allow down stream full page caching
- View Apache/PHP error log
- View security vulnerabilities in WordPress and installed plugins, with email alerts to site admin if there are critical vulnerabilities
- Multisite support

= NGINX Full Page Caching =
This plugin rewrites HTTP headers of HTML to allow Nginx and the browser to cache HTML. Full Page Caching may introduce all sorts of problems for end users, so installation and testing should be performed by a professional.

Note: Some features are only enabled for hosts on Servebolt.com due to dependencies in the hosting stack.

== Installation ==
1. Upload 'servebolt-optimizer' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Servebolt > Performance Optimizer and run optimizations if necessary

With WP-CLI
1. Log in to your site with SSH
2. cd to your sites root wordpress folder
3. Run 'wp plugin install servebolt-optimizer --activate'
4. Run optimizations 'wp servebolt db optimize'

== Changelog ==

= 1.6 =
* New: Control Full page cache settings with WP CLI (wp servebolt fpc)
* Improvement: Turn off vulnerable plugins check with `define('SERVEBOLT_VULN_ACTIVATE', false);`
* Removed the transient cleaner
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


This project is maintained on Github: https://github.com/Servebolt/servebolt-optimizer