=== Servebolt Optimizer ===
Contributors: audunhus
Tags: performance, optimization, cache, log
Donate link: https://servebolt.com
Requires at least: 4.9.1
Tested up to: 4.9.1
Requires PHP: 7
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds functionality to implement Servebolt Word Preses best practices. This includes database optimizations, log review, performance recommendations and support for down stream full page caching.

== Description ==
Features
- Database optimization - Convert tables to InnoDB
- Database optimization - Add indexes
- Recommendations on additional performance improvements
- Rewrite headers to allow down stream full page caching
- View Apache/PHP error log

NGINX Full Page Caching
This plugin rewrites HTTP headers of HTML to allow Nginx and the browser to cache HTML. Full Page Caching may introduce all sorts of problems for end users, so installation and testing should be performed by a professional.

== Installation ==
1. Upload `servebolt-optimizer` to the `/wp-content/plugins/` directory
1. Activate the plugin through the \'Plugins\' menu in WordPress