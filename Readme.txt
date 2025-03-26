=== Servebolt Optimizer ===
Contributors: audunhus, erlendeide, servebolt, andrewkillen
Tags: performance, optimization, html cache, cloudflare , multisite
Donate link: https://servebolt.com
Requires at least: 4.9.2
Tested up to: 6.7.1
Requires PHP: 7.4
Stable tag: 3.5.54
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

= 3.5.54 =
* Added the ability to allow for Private post types to be purged. 

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

= 3.5.12 =
* Disabled WooCommerce cart url adaption when instantpage is not enabled.
* Added check for ['url'] in payload of queue creation object.

= 3.5.11 =
* Added scheduled cleanup of expired transients. 
* Added method to stop WooCommerce carts from ever being prefetched by InstantPage.
* Removed APO capability due to it being only possible now with the cloudflare plugin.
* Added CacheTag headers to Accelerated Domains reducing purge commands to only 2 for each post/page update and their related archives, taxonomy terms and feeds.
* Implemented CachTag purging for Accelerated Domains.
* Added CacheTag headers to Servebolt CDN for later use in purging.  
* Added new garbage collection for the purge queue via cron scheduler.
* Added UID column and UID index to the purge queue tables so that searching for existing queue items could be significantly speed up and also stop repeat adding of an existing
* Added ```wp servebolt check-cdn-setup``` to the WP CLI to check the CDN setup for AcelerateDomains or ServeboltCDN.
* Added ```wp servebolt cache purge queue trash``` to the WP CLI to purge old items from the queue
* Changed Database Migrations to work with own version admin, unlinking from the plugin version number.
* Added LIMIT to garbage collection query.
* Slight change to the logic for cache purging to improve payload checking.
* Moved action_scheduler filters to only be implemented if action_scheduler is installed.
* Fixed bug in WP Rocket compatibility.
* Fixed a few typo's.
* Fixed PHP deprecated messages.
* Added existence checking of API error messages.
* Fixed cache headers errors on RSS feeds.


= 3.5.10 =
* Added LIMIT to garbage collection query.
* Increased batch capibilities action_scheduler, 8x more processing possible.

= 3.5.9 =
* New Feature - Added Error Log link to admin menu bar
* Lots of updated [https://wpplugin.dev.servebolt.com/](filters documentation)
* Bug fix - added check for REQUEST_METHOD to see if it exists before using it, stopping cron errors

= 3.5.8 =
* Adapted clearing of menu cache transients to include 404 page reference transients

= 3.5.7 =
* bump release, previous verion did not correctly deploy to wordpress.org

= 3.5.6 =
* Added the transformation of SRC's for images implemented via blocks for Accelerated Domains/Servebolt CDN
* Added unit tests for new functionality

= 3.5.5 =
* Tested against WordPress 6.0
* Added Andrew Killen as developer
* Updated how Unit Tests work

= 3.5.4 =
* Bugfix - Removed menu manifest file option from Prefetch-feature. Due to some difficulties with making the menu manifest file work in the Prefetch-feature it was decided to remove it until further notice. The script and style file manifest files will persist as before.
* Bugfix - Resolved issue with the cache purge features in row actions for taxonomies/post types. The plugin adds purge cache-link in the row actions for posts and terms. We previously targeted all registered post types and taxonomies, but this is now changed to only target public post types and terms. The targeted post types and terms can also be controlled through filters (sb_optimizer_cache_purge_row_action_post_types, sb_optimizer_cache_purge_row_action_post_types_query, sb_optimizer_cache_purge_row_action_taxonomies, sb_optimizer_cache_purge_row_action_taxonomies_query).
* Bugfix - Automatic cache purge of products during WooCommerce checkout. In some cases there was an error during the WooCommerce checkout. The feature in question purged cache for the product during checkout so that stock amount and status would be kept up to date. This error should now be resolved.
* Bugfix - Automatic setup of WP Cron on multisite failed. The feature that sets the WP Cron up with the UNIX cron failed when ran on a multisite. This should now be fixed. The cause of the error was that the lockfiles we’re not generated with a valid filename. These lockfiles (originating from “flock”) keeps the system from running concurrent cron tasks, so that we force the system to wait until the previous job is done. Note that this is a Servebolt hosted only feature.
* Bugfix - Error during plugin uninstallation. There was an error during plugin uninstallation due to a missing PHP constant. This is now fixed.
* Bugfix - Errors when environment file is not present. There was some error related to the environment file not being found, either because there is a custom WordPress folder structure or because the file is removed (either by deletion on disk or by disabling the file in the admin panel). The plugin now handles the absence of this file in a better way - the error handling was improved and there is an admin notice telling the user that the file is missing + instructions on how to fix this.

= 3.5.3 =
* Fixed incompatibility issue with plugin Lightweight Sidebar Manager
* Fixed issue with automatic cron setup (Servebolt-clients only) not working due to bug in the Servebolt API
* Added migration to clean up legacy transients (orphaned transients without expiry)
* Fixed bug in settings form for the Prefetch-feature
* Fixed bug in feature access check for the Accelerated Domains Image Resize-feature
* Fixed bug in database migration runner

= 3.5.2 =
* Fixed issue with cache headers and authentication-check (user role determination)

= 3.5.1 =
* Fixed issue with transient rows not expiring for the menu optimizer feature.

= 3.5 =
* Added support for cache provider "Servebolt CDN"
* Bugfix - WP Admin markup error. The styling for the information panel used in for example the cache settings page was broken in WordPress v5.9, but this is now fixed.
* New feature - Clear site data on login. In v3.5 a new feature was added - every time a user logs in then we return a header telling the browser to clear local storage and browser cache. This is useful to ensure that cached content gets cleared for logged in users.
* New feature - Support for Servebolt CDN. The plugin now supports Servebolt CDN.
* Beta feature - Accelerated Domains Prefetching. We’ve added a new feature for users of Accelerated Domains - Prefetching of assets and menu items. This feature allows for our infrastructure to preliminary fetch the assets of a webpage and cache them in our infrastructure which results in reduction of load time. Another feature is that menu items gets prefetched as well, meaning that when you navigate to a subpage it has already been cached and is ready to be served in no time!
* New feature - Cache purging when Accelerated Domains is disabled
We have added a feature to purge all cache even when Accelerated Domains is disabled. This is useful when deactivating Accelerated Domains and doing a proper “cleaning up” by clearing all cache.
* New feature - Automatic WP Cron setup (including Action Scheduler) (Servebolt-clients only). We have added a feature to automatically set up the WP Cron so that it runs using the UNIX cron. This offloads WordPress from having to trigger scheduled tasks as well as making the process of setting it up a lot easier. Note that this feature also sets up the Action Scheduler (used by WooCommerce and other plugins) to be run using the UNIX cron.
* Bugfix - Accelerated Domains Image Resize can only be enabled when site has access
Previously a Servebolt-client was able to enable Accelerated Domains Image Resizing even when the client did not have access to it (based on their subscription). We’ve not added a check so only eligible clients can enable the feature. Note that enabling the feature while not having access to it will result in the feature not being active. The subscription needs to be in place for the feature to work. This “bugfix” only fixes the GUI so that we communicate better to the client whether they have access or not.
* Bugfix - Improved cache purge queue feature
We’ve improved the cleanup of the cache purge queue to prevent the queu from growing too big. This is done by removing all completed queue items as well as removing failed queue items that are older than 1 month.
* New feature - Purge all network feature
We’ve now added a feature to purge all cache for all sites in a multisite-network. You can find it in the dropdown in the top bar in WP Admin.
* Bugfix - WooCommerce product simple cache purge on checkout
Whenever a user checks out in WooCommerce then the cache for the products in the cart will be purged. Due to how we purge cache a whole range of URLs might be included in the cache purging. This is because a post/page/product might be visible on the front page, in archives etc. and thus we include the front page URL, archive URL in the cache purge actions. But in the context of WooCommerce checkout and WooCommerce product we decided that a simple cache purge will suffice - this meaning that we only purge cache for the product URL, not the front page URL or any other related URL.
* Bugfix - WooCommerce product immediate cache purge on checkout. Whenever a user checks out in WooCommerce then the cache for the products in the cart will be purged. For many users this means using the queue to purge the cache of these products, but in the case of WooCommerce checkouts we now purge cache immediately regardless of whether they have the queue based cache purge active or not.
* Bugfix in Menu optimizer – We saw that the menu optimizer feature was incompatible with some WordPress-themes. The feature was therefore refactored and should now be better suited to work with most WordPress-themes.
