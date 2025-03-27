=== Servebolt Optimizer ===
Contributors: audunhus, erlendeide, servebolt, andrewkillen
Tags: performance, optimization, html cache, cloudflare , multisite
Donate link: https://servebolt.com
Requires at least: 4.9.2
Tested up to: 6.7.2
Requires PHP: 7.4
Stable tag: 3.5.55
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin implements Servebolt's WordPress best practices, and connects your site to the Servebolt Admin Panel.

== Description ==

The Servebolt Optimizer plugin adds functionality to implement Servebolt's best practices for WordPress. This includes database optimizations, errorlog review, automatic cache purging, automatic image optimization and resizing, performance recommendations, and support for down stream HTML caching.

Specifically, Servebolt Optimizer does two things for your site:

1. It connects your [WordPress hosted](https://servebo.lt/e3ke3) or [WooCommerce hosted](https://servebo.lt/724lz) Servebolt site to your [Servebolt Admin Panel](https://servebo.lt/pf3hu).
2. Its features implement Servebolt's best practices for performance. These best practizes include database optimizations, error log review, automatic cache purging, automatic image optimization/resizing, performance recommendations and support for down stream HTML caching.

This project is maintained on [Github](https://servebo.lt/sog).

### Features

- Configures HTML caching to speed up your site (**Servebolt clients only**)
- Integrates with [Accelerated Domains](https://servebo.lt/4c9dw) (**Servebolt clients only**)
- Rewrite headers to allow down stream HTML caching (**Servebolt clients only**)
- View Apache/PHP error log (**Servebolt clients only**)
- Database optimization - Convert tables to InnoDB
- Database optimization - Add performance improving indexes
- Automatic cache purge for Cloudflare and Accelerated Domains
- Recommendations on additional performance improvements
- Multi-site support
- WP CLI support
- Cloudflare Image Resize-support (beta feature)
- WP Rocket compatability (**Servebolt clients only**)

Read more about the plugin and all its features in our [Help Center](https://servebo.lt/servebolt-optimize-documentation).

### Accelerated Domains

The integration with our revolutionairy add-on performance and security enhancing service [Accelerated Domains](https://servebo.lt/4c9dw) is made possible by Servebolt Optimizer. Installing the Servebolt Optimizer will provide the required HTTP headers to make use of Accelerated Domains.

### Automatic purge of Cloudflare cache
Servebolt Optimizer supports the most complete solution for Cloudflare cache purging. The HTML Cache is automatically purged when any post type or term has been updated. You can also purge directly from the admin bar. This Cloudflare integration supports both the use of API key and API token authentication when communicating with the Cloudflare API.

### HTML Caching
This plugin rewrites HTTP headers of HTML to allow for HTML Caching, and for the browser to cache HTML. HTML Caching may introduce all sorts of problems for end users, so installation and testing should be performed by a professional.

### Configuration

This plugin can be controlled via the WordPress Dashboard or WP CLI. Additionally there are various filters and PHP constants at your disposal.

### Filter and constant reference

The plugin has various filters and PHP constants that allows third-party developers to alter the behaviour of the plugin. Please read the article [Filters and PHP constants](https://servebo.lt/servebolt-optimizer-filters-and-php-constants) in our help center to learn more.

### Testing

We test against the current production version of WordPress and the next beta/development version

== Installation ==

Navigate to your WordPress Dashboard > Plugins > Add New and then search for **Servebolt** and follow instructions.

Alternatively, you can also install Servebolt Optimizer via sFTP or WP CLI:

With sFTP:

1. Download this plugin and unzip
2. Upload servebolt-optimizer folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Servebolt > Performance Optimizer and run optimizations if necessary

With WP-CLI:

1. Log in to your site with SSH
2. cd to your sites root wordpress folder
3. Run `wp plugin install servebolt-optimizer --activate`
4. Run optimizations `wp servebolt db optimize`

Run `wp help servebolt` to see all the available commands that can configure the plugin.

== Frequently Asked Questions ==
= Will Servebolt Optimizer plugin make my site faster? =

The Servebolt Optimizer plugin is primarily designed to make best use of our [WordPress hosting](https://servebo.lt/e3ke3) or [WooCommerce hosting](https://servebo.lt/724lz). Its database optimizing feature does indeed help in making your database faster and thus your site.

= Can I install this plugin if I'm not a Servebolt client? =

Yes, you can. The database optimizations are beneficial for everyone as well as the Cloudflare cache purge options. You would miss out on the [fastest WordPress hosting out there](https://servebo.lt/5xbqg), but you can use it on a non-Servebolt site.

= What if I discover a bug? =

If you're a Servebolt client, please reach out to our Support Team and we'll be happy to help you out there. Alternatively, you can create a support forum request [here](https://wordpress.org/support/plugin/servebolt-optimizer/).

== Changelog ==

= 3.5.55 =
* Confirmed WordPress 6.7.2 compatibility
* cleaned off the tail of the readme file

= 3.5.54 =
* Added the ability to allow for Private post types to be purged.
* Bugfix: fixed deprecation errors on PHP 8.4 for nullable types

= 3.5.53 =
* Bugfix: Prevent additional db writes to options table on Admin pages by skipping them when the db migration version is the current migration version. 

= 3.5.52 =
* Added more image sizes to Image Resizer for Accelerated Domains. This overcomes image quality issues on sites that have minimal SRCSET image sizes implemented.

= 3.5.51 =
* Added purge all on Customizer Update and Theme change.
* Added error messaging for when the 'post_row_actions' actions array is invalid, and Servebolt Optmizer is unable to add purge actions to CRUD pages.
* Bugfix: Updated Servebolt PHP-SDK so that the error "PHP Deprecated:  Creation of dynamic property" does not show for PHP8.2+

= 3.5.50 =
* Bugfix: added extra error checking around WooCommerce product purge after customer reported errors were found.
* Confirmed WordPress 6.7.1 support

= 3.5.49 =
* Added purge on WooCommerce stock change or product update, to cover purge events when save_post hook is not fired.
* Bugfix: Forcing max-age=0 on all posts that are status "Password Protected" to prevent ever being cached. 

= 3.5.48 =
* Bugfix: on Servebolt Linux 8/php 8.3+ the purge candidate urls were filtering too many out of being purgable.
* Bugfix: False positive error in logs when logged in at that Cache-Tag headers could not be sent. Cache-Tags should never be present for logged in users. 

= 3.5.47 =
* Added option to purge all caches, including the Server and CDN. This applies to those hosted on Servebolt Linux 8 only and using Accelerated Domains or Servebolt CDN. 
* Fixed some deprecation errors on admin sub menus that have been converted to tabs. 
* Improved the log file ready on Servebolt Linux 8 to now include PHP and HTTP.
* Update to the Servebolt PHP-SDK to support the new purge method.

= 3.5.46 =
* Accelerated Domains Image Resizer: added filter to manage problems when WordPress is unable to produce image dimensions by defaulting to the thumbnail size.
* code re-orgainisation for easier reading/debugging in the purge post section.

= 3.5.45 =
* added extra checks to CacheTag creation to deal with plugins that prevent the ID from being readable on is_singular() queries

= 3.5.44 =
* added extra checks to prevent php 8.3 deprecation errors on some requests.

= 3.5.43 =
* Removed unneeded url purge request for Servebolt-CDN, reducing purge request by 50%.
* Adapted purge logic for Servebolt-CDN to reduce unneded purges at the CDN. If less than 17 urls are needed to purge properly, it will choose that, if not will purge by tag all HTML.
* Adapted API error output from code based failures during AJAX request to make it reable.


= 3.5.42 =
* forcing release. 45mins after success message from WordPress and its not live. bumping.

= 3.5.41 =
* further improvement to purging, reducing total payloads for Files as Tags are performing the same job
* Bug fix: Updated the PHP SDK again, added type to all Servebolt CDN purges to add extra validation. 

= 3.5.40 =
* Added stable tag so it gets deployed!
* Removed changelog pre v 3.5 to make it shorter as requested
* Adapted the tags to match requested count. 

= 3.5.39 =
* Added sanitization to host calls for purging, and better validation, via update to Servebolt SDK.  
* Made purge based AJAX error messages readable in the modal window.
* Reduced number of cache tags, removing ones that were never used.
* Adapted purge call for Servebolt CDN for single post purges, no more purge errors.

= 3.5.38 =
* Adapted the action scheduler cron script to check active status of WooCommerce per site, not per network.
* Removed /favicon.ico from fast404 capability. 
* Updated Servebolt Linux 8 users admin panel link from top menu.

= 3.5.37 =
* Changed password on SVN/WordPress.org, trying to authenticate again and deploy.

= 3.5.36 =
* Bump release due to authentication issue during last release, failed uploading the tag to WordPress. 

= 3.5.35 =
* Confirmed WordPress 6.6.1 support.
* Added Admin UI elements to manage Caching of 404's, and fast 404's reponses for static files
* Implemented fast 404's for static files, that give a reponse after either mu_plugins_loaded or plugins_loaded. No extra processing or file sizes.
* Added purge all trigger to options->permalink_structure 

= 3.5.34 =
* fixed php sdk depreciation error
* removed php 7.3 support, minimum level is 7.4, which is also minumum level Servebolt hosts.

= 3.5.33 =
* Support for WordPress 6.5.2 confirmed.
* Added auto healing for environment files where if the cached filepath is incorrect, it is automatically replaced.
* Bugfix - On some cron based jobs that do not have HTTP_USER_AGENT set, were failing on newer versions of PHP. Added check for 'HTTP_USER_AGENT' before trying to use it in part of the prefetching checks.
* Bugfix - Added additional checks on the strContains() helper function to deal with PHP8 requirements on null values.

= 3.5.32 =
* Updated changelog with 3.5.31 reason for release. 

= 3.5.31 =
* release bump

= 3.5.30 =
* When on Servebolt the plugin now checks the environement.json file to see if the key `api_url` exists. If its there it will use that, if not it will continue to use the pre-defined url for communication with the Servebolt API. 

= 3.5.29 =
* adapted cache tags prefix to be unique: Chosing between the shortest of bolt_id plus environment_id as one string, the domain name without dots as the other string.  

= 3.5.28 =
* Found extra typo and replaced 'sb_optimizer_cach_tags_fine_grain_control' to be 'sb_optimizer_cache_tags_fine_grain_control'

= 3.5.27 =
* fixed typo in 'sb_optimizer_cach_tags_fine_grain_control' to be 'sb_optimizer_cache_tags_fine_grain_control'
* added new brand icon
* added copy text for Servebolt CDN

= 3.5.26 =
* added filter 'sb_optimizer_cach_tags_fine_grain_control' that when set to false will use a single tag for all HTML and RSS
* converted cachetags from a human readable format to a machine readable format to reduce header size
* added new branding logo
* forcing a cache purge all on update of this version of the plugin to move sites to the to new CacheTags schema

= 3.5.25 =
* Allows for NextGen servers to be supported for reading Servebolt Environment files and obtaining the site id from the path.
* Using hook set_object_terms, so that it checks if default_category is used on first save of a post, and if its is being replace with newer terms on first publish.
* Tested upto 6.4.1
* Fixed bug in cache by term id, now uses CacheTags whenever possible.
* Added check for Image sizes on Accellerated Domains image resizer so that it can never have a zero value.

= 3.5.24 =
* fixed small bug of missing save button on advanced tab of new installs
* proven support for 6.3.1

= 3.5.23 =
* Small text changes in WP Cron configuration area.
* Adapted links to include a link to the advanced tab for enabling or disabling cron.
* Cron enabled checkbox also takes account of the DISABLE_WP_CRON constant, not just the option.
* proven support for WP 6.3

= 3.5.22 =
* bump release

= 3.5.21 =
* added cache-tag headers to search pages, so that they can optionally be cached. Acellerated Domains feature only.

= 3.5.20 =
* fixed "PHP Deprecated" error when using PHP8.0+ and WP_DEBUG is set to TRUE.

= 3.5.19 =
* updated supported WordPress version.

= 3.5.18 =
* hid the 'purge by taxonomy term' menu and quick menu buttons for Servebolt CDN customers. This feature is not possible for them and should not have been showing.

= 3.5.17 =
bump release. no changes.

= 3.5.16 =
* Added CacheTags to Servebolt CDN
* Added HTML cache purging to Servebolt CDN

= 3.5.15 =
* Bugfix for purge queue on large sites. It was giving CRON errors.
* Added filter to allow for adaption of headers on Full Page Caching.

= 3.5.14 =
* version bump to force release 

= 3.5.13 =
* Bugfix for Cloudflare direct purging via purge queue. it was not created purge records correctly.
