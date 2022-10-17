# Servebolt Optimizer

This repository contains the WordPress plugin Servebolt Optimizer - a plugin that:
- Optimizes your WordPress-site through markup cleanup, adding indexes and ensuring that your database is running with the best MySQL storage engine.
- Adds support and control features for Servebolt Accelerated Domains
- Gives you neat features related to Cloudflares services (cache purge, image resize)
- Gives you additional benefits when hosting your site at Servebolt (cache control, cache purging)

The plugin infrastructure is loosely based on: https://github.com/avillegasn/wp-beb

## Development

## Prerequisites5
Befor you start it is best to get the system ready for testing, for this SVN (Subversion) must be installed, and also set the PHP CLI php.ini to contain a log file. Failure to install SVN this first will result in a non-working install of the WordPress test install as it cannot download it from wordpress.org

installing subversion on *nix
```
# sudo apt-get install subversion
```

adapting the php cli php.ini
```
// find out what version of php cli you are running
# php -v
// change to the report version php cli, replacing 8.0 with your version
# cd /etc/php/8.0/cli
// edit the php.ini
# sudo nano php.ini
// find the error_log section and adapt it to be
error_log = php_errors.log 

```
Don't forget to save the php.ini file. This will put the php_errors.log into the place where it is being run.  You can also adapt it to be ``error_log =/var/log/php_errors.log``
### Composer
First we need to pull in all the dependencies: ``composer install``

### Build assets
1. Run `yarn install`
2. Run `yarn build` or `yarn watch` for development build, and `yarn production` for production build

### Testing
You can set up the test environment by running the command:
``composer install-wp-test db-name username password db-host wp-version skip-db-creation``

Example:
``composer install-wp-test sb_opt_db sb_opt_usr sb_opt_pass 127.0.0.1 latest true``

Create and .env file in the test directory by copying the .env.example file to .env and setting an ACD enabled testing domain.  Also run ``yarn production`` to make sure that the dist css files exist.

You should now be able to run ``composer phpunit`` WP single site or ``composer phpunit-mu`` for WP multi-site.

To run without composer to debug failure use, this method allows for CLI arguments where composer does not
``./vendor/phpunit/phpunit/phpunit -c phpunit.xml --verbose --stop-on-failure``

To work against a singluar test set use (where WPAssetTest is replace with the testing Class Name)
``./vendor/phpunit/phpunit/phpunit -c phpunit.xml --filter WpAssetTest``
### Phan, PHP CodeSniffer and PHPLint
Phan, PHPCS and PHPLint should be installed by composer.
You can run the tests with this command: `composer test`

### Deployment
If you want to deploy to WordPress.org then all you got to do is create a tag in Git. Please use semantic versioning according to semver.org. When you push the tag to Github we use Github Actions that will "forward" the tag to WordPress.org SVN repository. You can see the deployment instructions in `.github/workflows/wordpress-plugin-svn-deploy.yaml`.
Note that the version number in the file `readme.txt` is used by WordPress.org, while the version number in the file `servebolt-optimizer.php` is used when installed on a WordPress-site.

Credentials for the SVN repository is stored in the password manager. The credentials are already stored as secrets in the Github repository, but you might need them if you want to interact with the SVN repository from your local machine.

#### Local build
If you want to build a local production-ready version of the plugin you can run the command `composer local-build`. When the command has executed you should have a file in the project root path called `servebolt-optimizer.zip` which contains the plugin prepared the same way as when it is shipped to WordPress.org.

## Changelog
#### 3.5.8
* Adapted clearing of menu cache transients to include 404 page reference transients
* Updated installation and testing documentation
* Removed Featured unit tests from phpcs.sh as the directory nolonger exist
#### 3.5.7
* bump verion, did not deploy correctly to wordpress.org. 
#### 3.5.6
* Added the transformation of SRC's for images implemented via blocks for Accelerated Domains/Servebolt CDN
* Added unit tests for new functionality
#### 3.5.5
* Tested against WordPress 6.0
* Added Andrew Killen as developer
* Adapted unit testing methodolgy to be more robust
* Added PHPUnit pollyfills.
* Improved inhouse developer documentation
* Defined working install process for a new developer of this plugin
* adapted .gitignore to include php_error.log
#### 3.5.4
* Bugfix - Removed menu manifest file option from Prefetch-feature. Due to some difficulties with making the menu manifest file work in the Prefetch-feature it was decided to remove it until further notice. The script and style file manifest files will persist as before.
* Bugfix - Resolved issue with the cache purge features in row actions for taxonomies/post types. The plugin adds purge cache-link in the row actions for posts and terms. We previously targeted all registered post types and taxonomies, but this is now changed to only target public post types and terms. The targeted post types and terms can also be controlled through filters (sb_optimizer_cache_purge_row_action_post_types, sb_optimizer_cache_purge_row_action_post_types_query, sb_optimizer_cache_purge_row_action_taxonomies, sb_optimizer_cache_purge_row_action_taxonomies_query).
* Bugfix - Automatic cache purge of products during WooCommerce checkout. In some cases there was an error during the WooCommerce checkout. The feature in question purged cache for the product during checkout so that stock amount and status would be kept up to date. This error should now be resolved.
* Bugfix - Automatic setup of WP Cron on multisite failed. The feature that sets the WP Cron up with the UNIX cron failed when ran on a multisite. This should now be fixed. The cause of the error was that the lockfiles we’re not generated with a valid filename. These lockfiles (originating from “flock”) keeps the system from running concurrent cron tasks, so that we force the system to wait until the previous job is done. Note that this is a Servebolt hosted only feature.
* Bugfix - Error during plugin uninstallation. There was an error during plugin uninstallation due to a missing PHP constant. This is now fixed.
* Bugfix - Errors when environment file is not present. There was some error related to the environment file not being found, either because there is a custom WordPress folder structure or because the file is removed (either by deletion on disk or by disabling the file in the control panel). The plugin now handles the absence of this file in a better way - the error handling was improved and there is an admin notice telling the user that the file is missing + instructions on how to fix this.

#### 3.5.3
* Fixed incompatibility issue with plugin Lightweight Sidebar Manager
* Fixed issue with automatic cron setup (Servebolt-clients only) not working due to bug in the Servebolt API
* Added migration to clean up legacy transients (orphaned transients without expiry)
* Fixed bug in settings form for the Prefetch-feature 
* Fixed bug in feature access check for the Accelerated Domains Image Resize-feature
* Fixed bug in database migration runner

#### 3.5.2
* Fixed issue with cache headers and authentication-check (user role determination)

#### 3.5.1
* Fixed issue with transient rows not expiring for the menu optimizer feature.

#### 3.5
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

#### 3.3
* Bugfix - WP admin bar markup error - Fixed minor markup error in the WP admin bar dropdown menu. An obsolete “target”-attribute was added to the parent div element which is invalid.
* Bugfix - menu cache feature issue with filters - Whenever a 3rd party adds a menu using the filter wp_nav_menu_args, we could not cache the result due to how we interact using WordPress filters. This should now be fixed.
* Bugfix - menu cache feature producing excessive amount of transient rows - The menu cache feature produced too many transients due to the way the transient key (a.k.a. cache key) was generated. Solved by making the transient key less complicated and by adding a filter so that 3rd parties can modify the cache behaviour instead.
* Cache purge queue origin metadata - Whenever an item is added to the cache purge queue, we now also add the origin of this event. For example a manual cache purge, or an automatic cache purge on content update etc.
* Simplified cache purge - During cache purge we previously purged related URLs for a WP object (posts, terms etc.). Related URLs could be the front-page, archives etc. In some cases this caused large amounts of URLs to be purged cache for even when not needed. We have simplified cache purge in some cases – like for example during checkout in WooCommerce.
* Improved cache purging for the menu cache feature - Whenever a menu is assigned to a menu location we now purge cache for the previously assigned menu. This process prevents orphaned transient rows and help prevent the options table from getting bloated.
* Bugfix - Migration error on plugin activation/deactivation in CLI-context - Whenever the plugin was activated or deactivated there was, in some cases, an error due to the database migration not being ran correctly. This should now be solved.
* Bugfix - Fixed broken Cloudflare API credentials validation in form - Whenever Cloudflare was selected as cache provider in the cache purge configuration form the validation did not function. This is now fixed.
* Bugfix - Fixed unhandled exceptions - Due to missing namespace, some exceptions went unhandled which again caused fatal errors in some cases. Highly unfortunate! This is now fixed.
* Bugfix - Could not determine if in Servebolt hosting environment from Cron-trigged CLI context - Due to absence of server variables, the system could not determine whether the code was executing in a Servebolt server environment when it was ran in CLI-context trigged by Cron. This is now fixed.
* Changed name of menu cache feature - Due to confusion between Cloudflare/Accelerated Domains-cache and the menu cache feature we changed the name said feature to “Menu Optimizer”.

#### 3.2
* Improved automated cache purging - The automatic cache purge has been improved, primarily in 3 areas. Whenever a post/term gets deleted then the cache gets purged. Whenever an attachment gets updated (resized, cropped etc.) we purge cache for URLs, including all image sizes if the attachment is an image. Whenever a post gets excluded from the HTML Cache (formerly Full Page Cache) then we also purge cache.
* Custom cache TTL per post type - One can now control the cache TTL (time-to-live) per post type. This allows for more fine-grained cache control.
* More fine-grained access control to cache purge feature - Previously only administrators could purge cache. This is now changed using more fine-grained capability checks - administrators and editors can now purge cache, while authors can purge cache for their own posts. Contributors and subscribers cannot purge cache.
* Better Jetpack compatibility - Previously the Jetpack Site Accelerator was in conflict with Servebolt’s Accelerated Domains. This is now fixed with Site Accelerator being disabled whenever Accelerated Domains or Accelerated Domains Image Resize-feature is active.
* Menu cache performance feature - We’ve added a new performance enhancing feature - WordPress menu cache. This usually decreases TTFB with several milliseconds, even for menus with few items. The feature also includes automatic cache purge whenever a menu gets updated.
* Translation loader performance feature - We’ve added a new performance enhancing feature - improved WordPress translations file loader. Whenever WordPress loads the translations from MO-files this causes a lot disk I/O. This feature will cache the MO-file using transients which in return decreases the loading time.

#### 3.1.1
* Added index to column "parent_id" in the queue table to improve query performance.

#### 3.1
* Accelerated Domains Image Resizing - This version introduces a new feature - Accelerated Domains Image Resizing. This feature will resize, optimize metadata and cache your images on the fly, improving load time and enhancing the user experience.
* PHP version constraint - We have changed the required PHP version from 7 to 7.3. This means that whenever the plugin is activated in an environment running PHP version less than 7.3 then they will get a admin notice in WP Admin telling them to upgrade to be able to used the plugin.
* Yoast SEO Premium - automatic cache purge for redirects - Whenever you add or remove a redirect to Yoast SEO Premium the plugin will purge the cache for the given URLs. This is useful since otherwise one would potentially need to manually purge these URLs after adding/removing a redirect.
* Added CDN cache control header - We have now added a new header (CDN-Cache-Control) that allows for more fine-grained control over the cache feature in the CDN-nodes.
* Improved WP Rocket compatibility - We’ve improved the compatibility with WP Rocket’s cache feature so that it will not interfere with the cache feature of Servebolt Optimizer.

#### 3.0.2
* Fixed bug in compatibility code for older versions of WP Rocket
* Fixed bug that caused post cache not to be purged when scheduling posts
* Updated composer and NPM packages (affecting development environment only)

#### 3.0.1
* Corrected typo in string “Accelerated domains” to use uppercase in first character of each word.
* Fixed issue in cache headers - the feature to exclude posts from cache was broken due to wrong order in conditions in the cache header logic. This is now fixed.
* Removed priority-attribute from plugin static asset actions - due to cases of incompatibility between themes and other plugins we removed the priority-attribute from the actions that enqueued the plugins static assets. This means that the priority-attribute falls back to the default value of 10 which should be less likely to cause issue.
* Resolved issue with single file composer packages not being included in autoloader - certain packages were not included in the Composer autoloader due to an issue in Mozart (which was needed to resolve conflicts between composer packages used in WordPress plugins). The packages originated as dependencies of the Servebolt PHP SDK, and was solved by specifically including them in the plugins composer-file. The affected packages contained polyfills for the PHP functions “http_build_url” (from module pecl_http) and “getallheaders” which means that this was only an issue in environment where these functions were not available in PHP.
* Removed SASS-parser since there was no real need in the project - due to an issue with the npm package "node-sass" running on macOS Big Sur the SASS-parser was disabled, at least for now.

#### 3.0.0 
* Rewritten codebase - The whole plugin code base is rewritten. This was done since the previous structure did not allow for automated testing (using PHP Unit) nor was it up to par with modern PHP. To achieve this the code base was rewritten to use PSR-4 auto loading as well as making the existing code testable. The code standard was also changed to PSR-1. The new required PHP version is 7.3 or higher.
* PHP Unit tests - PHP Unit tests have been added as an attempt to prevent errors, speed up the development process, and ensure better overall code quality.
* Accelerated Domains by Servebolt - The plugin has support for activating Accelerated Domains by Servebolt. The addition of this feature affects the cache purge system which previously only worked with Cloudflare, but now also supports Accelerated Domains and its cache feature. This can be controlled by selecting the cache provider in the cache purge settings.
* CLI-commands - JSON-support - The CLI-commands now has an optional JSON return format. This can be done by adding “--format=json” to the command call. Note that the HTML Cache-related commands do not have this support yet, but all other commands has.
* CLI-commands - Cloudflare CLI commands removed - Since the cache purge feature now works with both Cloudflare and Accelerated Domains then the feature is no longer only specific to Cloudflare. This lead to most of the Cloudflare-related commands being migrated to the “wp servebolt cache settings“-namespace. The only remaining Cloudflare-related CLI command is “wp servebolt cf setup“ which works like before, but now also has JSON return format supported as mentioned earlier.
* Improved cache purging - The cache purge feature is improved and expanded. See below.
* Cache purging of old URLs - Whenever a post/term URL is changed then the system will purge the old URL. This is useful since otherwise you would possibly get conflicting URLs and/or duplicated content.
* WooCommerce compatibility - The cache will now be purged for a WooCommerce product whenever the stock amount/status changes, like during a checkout. This is necessary to keep the stock up to date for the visitors/customers, especially on high traffic sites. Cache purging will now also purge all URLs of a variable WooCommerce product.
* Improved exception handling - Whenever there is an error during a cache purge request the system now has an improved handling of exceptions being thrown.
* WP Rocket compatibility - WP Rocket’s cache feature was previously in conflict with Servebolts own cache feature. This should now be solved since WP Rocket’s cache feature is disabled as long as the HTML Cache-feature is active in Servebolt Optimizer. This allow users to still use the other features of WP Rocket without conflicts.
* Improved queue handling when purging cache - In the cache purge feature there is an option to use a queue that will send the list URLs (that should be cached) in delayed chunks instead of sending them all immediately. These requests would typically originate from a post update, or whenever someone does a WooCommerce checkout and the product stock changes. This update improves the handling where the amount of URLs previously would have exceeded the allowed amount according to Cloudflare.
* Third party cache purge functions - Third party developers can now call publicly available cache purge functions. This allows for purging by post, term, URL or purge all.
* Single site plugin activation constraint - The plugin can now only be activated site-wide when used in a multisite network.
* Cloudflare Image Resizing removed from the GUI - The beta-feature “Cloudflare Image Resizing” has now been removed due to it not being tested properly. It is still available through the CLI, but not in the GUI.
* Removed network cache purge action - The feature to purge all cache for all sites in a multi-site network was removed due to lack of time to integrate this with Accelerated Domains and the improved queue system.
* Removed the cache purge queue GUI - The queue GUI (list) in the cache purge settings was removed due to lack of time to integrate this with Accelerated Domains and the improved queue system.
* Cache purge links in post/term list - It is now possible to trigger cache purge actions from the row actions of posts and terms in WP Admin.
* Added purge actions for terms in the WordPress Admin bar - When viewing a term - either in WP Admin or front-end - you can now purge the cache via the Admin bar.
* Added custom HTTP User Agent string to API requests - The outgoing requests from the plugin now uses a custom user agent string that will allow for easier identification of requests originating from the plugin.
* Log viewer GUI update - The log viewer GUI has gotten overhauled with better styling.
* Fixed bug - The form containing the cache purge configuration had autocompletion on. This lead to problems with information wrongfully being submitted. This is now fixed.
* Fixed bug - Host determination function failed when in CLI context. The function “isHostedAtServebolt“ returned false regardless of hosting environment when running in CLI-context. This is fixed in this version.
* Fixed bug - Cache headers absent in archives. When using HTML caching and setting the post type to “all” then the archives got not cache headers. This version fixes that.

#### 2.1.5
* Hotfix in Cloudflare cache purge feature

#### 2.1.4
* Added basic Cloudflare APO-support
* Changed order of URLs when purging cache for a post in Cloudflare
* Fixed bug in HTML Cache-logic for archives
* Fixed bug in Cloudflare Image Resize

#### 2.1.3
* Fixed styling issue in Gutenberg-editor sidebar menu

#### 2.1.2
* Fixed styling issue in Gutenberg-editor sidebar menu

#### 2.1.2
* Fixed JavaScript error related to the Gutenberg-editor

#### 2.1.1
* Fixed issue with script inclusion causing errors
* Added missing CLI argument for HTML Cache deactivation

#### 2.1
* Added extended cache invalidation for Cloudflare cache - archives and other related URLs will also be purged.
* Added a SweetAlert-fallback so that native JavaScript alerts, prompts, confirmations will be used instead. This is due to SweetAlert being prone to conflicts with themes and other plugins.
* Made the Cloudflare Image Resize-feature available (through WP CLI and PHP constant).
* Completed the WP CLI with more configuration commands.
* Added more meta-data to the cache purge queue when using the Cloudflare cache purge feature + added a cleaner that will remove old elements if not automatically purged.
* Added full overview over available PHP constants and filters.
* Various bug fixes
* Various GUI improvements
* Bug fix for WooCommerce-implementations (corrected cache headers for WooCommerce-pages).

#### 2.0.9
* Hotfix related to the admin bar

#### 2.0.8
* Improved GUI - now possible to purge cache (via the admin bar menu) for single pages when viewing / editing
* Fixed minor CF settings page validation bug

#### 2.0.7
* Bugfix in comment cache purge

#### 2.0.6
* Improved feedback when purging cache
* Code cleanup and refactor
* Various bugfixes
* Added automatic cache purge on comment post/approval

#### 2.0.5
* Swapped Guzzle with WP HTTP API to prevent namespace conflicts with other plugins also using Guzzle.

#### 2.0.4
* Bugfix in function that checks whether current site is hosted at Servebolt.

#### 2.0.3
* Various bugfixes and improvements to WP CLI-commands

#### 2.0.2
* Various bugfixes

#### 2.0.1
* Various bugfixes

#### 2.0
* [Added Automatic Cloudflare cache purge feature](https://servebo.lt/5z7xw)
* Major code refactor

#### 1.6.4
* Minor bugfix

#### 1.6.3
* Minor bugfix

#### 1.6.2
* Minor bugfix

#### 1.6.1
* Removed security from dashboard

#### 1.6
* New: Control HTML cache settings with WP CLI (`wp servebolt fpc`)
* Improvement: Turn off vulnerable plugins check with `define('SERVEBOLT_VULN_ACTIVATE', false);`
* Removed: Scanning of plugins for security vulnerabilities. This will be released in a separate plugin.
* Removed: Transient cleaner
* Added a exit if installed on PHP versions lower than 7

#### 1.5.1
* Bugfix: Unable to add indexes on non-multisite installs

#### 1.5
* Added multi-site support
* Fixed a bug in the wpvulndb security checker
* Added a nice animation when optimizer runs
* Updated readme.txt

#### 1.4.2
* Important bugfix

#### 1.4.1
* Important bugfix

#### 1.4
* Github #8 Added a transients cleaner to wp-cron
* Added transients cleaner to WP-CLI
* Changes to WP CLI commands
* NEW: Added a view to see vulnerabilities in WordPress and plugins from WPVULNDB.COM
* NEW: Added email notifications when WP or plugins is vulnerable

#### 1.3.4
* Added on/off switch for Nginx cache
* Remove WP version number and generator tag
* Skip concatenation of admin scripts, we use http2
* Added WP-CLI support
* Issues on Github #8 added uninstall.php + bug fixes #7 #9
* Added changelog to Readme.txt



