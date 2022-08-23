"use strict";(self.webpackChunkwpplugin_docs_servebolt_com=self.webpackChunkwpplugin_docs_servebolt_com||[]).push([[432],{1473:function(e,t,a){a.r(t),a.d(t,{frontMatter:function(){return l},contentTitle:function(){return s},metadata:function(){return c},toc:function(){return h},default:function(){return p}});var n=a(7462),i=a(3366),o=(a(7294),a(3905)),r=["components"],l={sidebar_position:4,title:"Testing"},s=void 0,c={unversionedId:"testing",id:"testing",isDocsHomePage:!1,title:"Testing",description:"You need the following plugins to test:",source:"@site/docs/testing.md",sourceDirName:".",slug:"/testing",permalink:"/docs/testing",version:"current",sidebarPosition:4,frontMatter:{sidebar_position:4,title:"Testing"},sidebar:"tutorialSidebar",previous:{title:"Actions, Filters and PHP constants",permalink:"/docs/actions-filters-and-php-constants"},next:{title:"Changelog",permalink:"/docs/changelog"}},h=[{value:"1. EDD cache compatibility",id:"1-edd-cache-compatibility",children:[]},{value:"2. Clear site data on login",id:"2-clear-site-data-on-login",children:[]},{value:"3. Prefetching",id:"3-prefetching",children:[]},{value:"4. Purging cache when Accelerated Domains is inactive",id:"4-purging-cache-when-accelerated-domains-is-inactive",children:[]},{value:"5. Cron setup",id:"5-cron-setup",children:[]},{value:"6. Accelerated Domains Image resize feature - access check",id:"6-accelerated-domains-image-resize-feature---access-check",children:[]},{value:"7. Bugfix - Accumulation of rows in queue table",id:"7-bugfix---accumulation-of-rows-in-queue-table",children:[]},{value:"8. Purge all network feature",id:"8-purge-all-network-feature",children:[]},{value:"9. Bugfix - Undefined property in SDK",id:"9-bugfix---undefined-property-in-sdk",children:[]},{value:"10. Cache purging during WooCommerce checkout - simplified cache purging",id:"10-cache-purging-during-woocommerce-checkout---simplified-cache-purging",children:[]},{value:"11. Immediate cache purge during WooCommerce checkout",id:"11-immediate-cache-purge-during-woocommerce-checkout",children:[]},{value:"12. Bugfix - Menu optimizer not working on certain sites",id:"12-bugfix---menu-optimizer-not-working-on-certain-sites",children:[]}],u={toc:h};function p(e){var t=e.components,a=(0,i.Z)(e,r);return(0,o.kt)("wrapper",(0,n.Z)({},u,a,{components:t,mdxType:"MDXLayout"}),(0,o.kt)("p",null,"You need the following plugins to test:"),(0,o.kt)("ul",null,(0,o.kt)("li",{parentName:"ul"},(0,o.kt)("a",{parentName:"li",href:"https://wordpress.org/plugins/easy-digital-downloads/"},"Easy Digital Downloads")),(0,o.kt)("li",{parentName:"ul"},(0,o.kt)("a",{parentName:"li",href:"https://wordpress.org/plugins/woocommerce/"},"WooCommerce"))),(0,o.kt)("p",null,"Note: the cache feature referenced in this article is not related to the Full Page Cache-setting in the Servebolt Control Panel. Please disable the Full Page Cache (only static file caching) in the Servebolt Control Panel before testing."),(0,o.kt)("h3",{id:"1-edd-cache-compatibility"},"1. EDD cache compatibility"),(0,o.kt)("p",null,"Jira-issue: WPSO-190",(0,o.kt)("br",{parentName:"p"}),"\n","Easy Digital Downloads needs to be cached the same way as WooCommerce does. Ideally this should be tested with both ACD and Cloudflare, but one of them is good enough as the cache compatiblity should work the same for both."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Activate plugin"),(0,o.kt)("li",{parentName:"ol"},'Go to WP Admin -> Downloads -> Settings -> Payment Gateways and check "Test mode" and "Test Payment"'),(0,o.kt)("li",{parentName:"ol"},"Add new product/download"),(0,o.kt)("li",{parentName:"ol"},"Open the code inspector so that you can inspect the headers")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Go to the product URL and see that the page gets cached according to the headers."),(0,o.kt)("li",{parentName:"ol"},"Add the product to cart and go to the checkout page. Inspect the page gets cached according to the headers."),(0,o.kt)("li",{parentName:"ol"},"Execute checkout and when you get to the success-page then see that the page gets cached according to the headers.")),(0,o.kt)("p",null,"Expected result:\nThe various EDD-pages should be conditionally cached."),(0,o.kt)("p",null,"Cleanup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Deactivate plugin after test.")),(0,o.kt)("h3",{id:"2-clear-site-data-on-login"},"2. Clear site data on login"),(0,o.kt)("p",null,"Jira-issue: WPSO-213",(0,o.kt)("br",{parentName:"p"}),"\n","We added the clearing of site data when you log into WP Admin. This is to reduce the risk of any issues with content being cached even while logged in to WP Admin."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},'Open the code inspector, go to the network tab and click "Preserve logs"')),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Log out of WP Admin and return to the login form. Make sure to have your code inspector open."),(0,o.kt)("li",{parentName:"ol"},"Log in"),(0,o.kt)("li",{parentName:"ol"},"Go to the code inspector and find the login POST-request.")),(0,o.kt)("p",null,"Expected result:"),(0,o.kt)("p",null,"You should see the following header present in the response:"),(0,o.kt)("p",null,(0,o.kt)("inlineCode",{parentName:"p"},'Clear-Site-Data: "cache", "storage"')),(0,o.kt)("p",null,"This should clear browsing data as described ",(0,o.kt)("a",{parentName:"p",href:"https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Clear-Site-Data"},"here"),"."),(0,o.kt)("h3",{id:"3-prefetching"},"3. Prefetching"),(0,o.kt)("p",null,"Jira-issue: WPSO-160",(0,o.kt)("br",{parentName:"p"}),"\n",'We added a new feature that will "scan" the Wordpress site for JavaScript/CSS-files + all the URLs in the menus and then generate a prefetch manifest file. This file will be picked up by the CF-infrastructure and CF will then preload all these URLs. This means "warming" the cache so that visitors will get very low latency and a very quick page load. The feature only works for users of Accelerated Domains.'),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"You need a WordPress-page that is using Accelerated Domains"),(0,o.kt)("li",{parentName:"ol"},"You need a page that has the cron set up properly")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Create a new page and don't visit it (so that we don't cache it yet)"),(0,o.kt)("li",{parentName:"ol"},"Make sure that the site has a visible main menu, and add the newly created page to it."),(0,o.kt)("li",{parentName:"ol"},"Go to WP Admin -> Servebolt -> Accelerated Domains -> Prefetching and check the checkbox, then save."),(0,o.kt)("li",{parentName:"ol"},"Wait for the cron to execute (might be smart to use WP Crontrol to inspect the cron tasks)."),(0,o.kt)("li",{parentName:"ol"},"Inspect that the manifest files are present at ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-content/uploads/acd/prefetching"),". Open the file ",(0,o.kt)("inlineCode",{parentName:"li"},"manifest-menu.txt")," and confirm that the URL of the newly created page is there."),(0,o.kt)("li",{parentName:"ol"},"Open a new incognito tab, open the network tab in the code inspector, and navigate to the front page using the internal Servebolt URL (not the domain you added for Accelerated Domains). This is so that we can see the raw headers that would be present between our servers and the CF-infrastructure."),(0,o.kt)("li",{parentName:"ol"},"Ensure that you see the following header present (example.com is used only for example ofc):")),(0,o.kt)("pre",null,(0,o.kt)("code",{parentName:"pre"},'link: <https://example.com/wp-content/uploads/acd/prefetch/manifest-style.txt>; rel="prefetch"\nlink: <https://example.com/wp-content/uploads/acd/prefetch/manifest-script.txt>; rel="prefetch"\nlink: <https://example.com/wp-content/uploads/acd/prefetch/manifest-menu.txt>; rel="prefetch"\n')),(0,o.kt)("ol",{start:8},(0,o.kt)("li",{parentName:"ol"},"Navigate to the newly created page while having the network tab in the code inspector open. Use the Accelerated Domains-domain this time, not the internal Servebolt URL."),(0,o.kt)("li",{parentName:"ol"},"Ensure that you get a cache hit by inspecting the headers.")),(0,o.kt)("p",null,"Expected result:\nThe newly created page should be cached even before we visit it. This is due to CF loading the prefetch manifest file during our first visit, and then it will warm the cache for us for the newly created page."),(0,o.kt)("h3",{id:"4-purging-cache-when-accelerated-domains-is-inactive"},"4. Purging cache when Accelerated Domains is inactive"),(0,o.kt)("p",null,"Jira-issue: WPSO-219",(0,o.kt)("br",{parentName:"p"}),"\n","One can now purge all cache even when Accelerated Domains is inactive. This is useful for someone who disables Accelerated Domains and want all cache to be purged instantly."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a page that uses Accelerated Domains"),(0,o.kt)("li",{parentName:"ol"},"Ensure some content is cached, like a sub page or article etc.")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Go to WP Admin -> Servebolt -> Accelerated Domains and disable the feature"),(0,o.kt)("li",{parentName:"ol"},"Ensure that the cached content is still cached"),(0,o.kt)("li",{parentName:"ol"},'Go to WP Admin -> Servebolt -> Accelerated Domains and click "Purge all cache"'),(0,o.kt)("li",{parentName:"ol"},"Inspect that the previously cached content is no longer cached")),(0,o.kt)("p",null,"Expected result:\nWe should be able to purge all cache for the Accelerated Domains-feature even when the feature is disabled."),(0,o.kt)("h3",{id:"5-cron-setup"},"5. Cron setup"),(0,o.kt)("p",null,"Jira-issue: WPSO-262/288/289",(0,o.kt)("br",{parentName:"p"}),"\n","The cron setup allows us to easily enable the WP Cron from being run by the UNIX cron in our hosting environment."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a WordPress site in our infrastructure"),(0,o.kt)("li",{parentName:"ol"},"Ensure no cron jobs is present for the site/environment in the control panel")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},'Go to WP Admin -> Servebolt -> Performance Optimizer -> Advanced and check the "Run WP Cron from UNIX cron" checkbox, then save'),(0,o.kt)("li",{parentName:"ol"},"Confirm that the cron job was added by inspecting the cron jobs for the site/environment in the control panel."),(0,o.kt)("li",{parentName:"ol"},"Confirm that ",(0,o.kt)("inlineCode",{parentName:"li"},"defined('DISABLE_WP_CRON', true);")," is defined in ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-config.php"),"."),(0,o.kt)("li",{parentName:"ol"},"Ensure that the cron job is actually running by for example purging cache using the queue based cache purging. If the queue based cache purging is working that means that the current cron setup is working."),(0,o.kt)("li",{parentName:"ol"},'Go to WP Admin -> Servebolt -> Performance Optimizer -> Advanced and uncheck the "Run WP Cron from UNIX cron" checkbox, then save'),(0,o.kt)("li",{parentName:"ol"},"Confirm that the cron job is no longer present by inspecting the cron jobs for the site/environment in the control panel."),(0,o.kt)("li",{parentName:"ol"},"Confirm that ",(0,o.kt)("inlineCode",{parentName:"li"},"defined('DISABLE_WP_CRON', true);")," is no longer defined in ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-config.php"),"."),(0,o.kt)("li",{parentName:"ol"},"Let's use the CLI and test this again. Run the command ",(0,o.kt)("inlineCode",{parentName:"li"},"wp servebolt cron status")," and confirm that the cron is not set up."),(0,o.kt)("li",{parentName:"ol"},"Run ",(0,o.kt)("inlineCode",{parentName:"li"},"wp servebolt cron enable"),", then run ",(0,o.kt)("inlineCode",{parentName:"li"},"wp servebolt cron status")," and confirm that the cron is set up."),(0,o.kt)("li",{parentName:"ol"},"Confirm that the cron job was added by inspecting the cron jobs for the site/environment in the control panel."),(0,o.kt)("li",{parentName:"ol"},"Confirm that ",(0,o.kt)("inlineCode",{parentName:"li"},"defined('DISABLE_WP_CRON', true);")," is defined in ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-config.php"),"."),(0,o.kt)("li",{parentName:"ol"},"Run ",(0,o.kt)("inlineCode",{parentName:"li"},"wp servebolt cron disable"),", then run ",(0,o.kt)("inlineCode",{parentName:"li"},"wp servebolt cron status")," and confirm that the cron is no longer set up."),(0,o.kt)("li",{parentName:"ol"},"Confirm that the cron job is no longer present by inspecting the cron jobs for the site/environment in the control panel."),(0,o.kt)("li",{parentName:"ol"},"Confirm that ",(0,o.kt)("inlineCode",{parentName:"li"},"defined('DISABLE_WP_CRON', true);")," is no longer defined in ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-config.php"),".")),(0,o.kt)("p",null,"Expected result:\nWe should be able to set up and remove the cron to be run using the UNIX cron, either by using the GUI in WP Admin or by using WP CLI."),(0,o.kt)("h3",{id:"6-accelerated-domains-image-resize-feature---access-check"},"6. Accelerated Domains Image resize feature - access check"),(0,o.kt)("p",null,"Jira-issue: WPSO-328",(0,o.kt)("br",{parentName:"p"}),"\n","We have now added a simple access check so that users cannot enable the image resize feature unless they have access to it. Note that this is a WP Admin GUI access check, so one can still activate this feature using the CLI."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a WordPress page that is using Accelerated Domains, but make sure the domain does not have access to the image resize feature.")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Go to WP Admin -> Accelerated Domains -> Image Resizing (beta)"),(0,o.kt)("li",{parentName:"ol"},"Ensure that you cannot check the checkbox"),(0,o.kt)("li",{parentName:"ol"},"Add the feature for the domain (using ",(0,o.kt)("a",{parentName:"li",href:"https://acd-admin.r99.no/"},"https://acd-admin.r99.no/"),")"),(0,o.kt)("li",{parentName:"ol"},"Ensure that you now ",(0,o.kt)("em",{parentName:"li"},"can")," check the checkbox")),(0,o.kt)("p",null,'Expected result:\nOne should only be able to activate the feature (check the checkbox) whenever the "Image Resizing"-feature is active.'),(0,o.kt)("h3",{id:"7-bugfix---accumulation-of-rows-in-queue-table"},"7. Bugfix - Accumulation of rows in queue table"),(0,o.kt)("p",null,"Jira-issue: WPSO-341",(0,o.kt)("br",{parentName:"p"}),"\n","Previously we saw a very high number of rows in the cache purge queue table. This was due to multiple reasons - one of them was that we kept rows until they either failed 3 times or was older than a certain threshold. We now also delete successfully purged rows. Due to this issue being hard to test I think we can skip it."),(0,o.kt)("h3",{id:"8-purge-all-network-feature"},"8. Purge all network feature"),(0,o.kt)("p",null,"Jira-issue: WPSO-356",(0,o.kt)("br",{parentName:"p"}),"\n","The purge all-feature with multisite-support was previously disabled since it was not working properly. It should now work."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a multisite"),(0,o.kt)("li",{parentName:"ol"},"Ensure that it has a working cache setup. Go to WP Admin -> Servebolt -> Cache and check the checkbox."),(0,o.kt)("li",{parentName:"ol"},"Ensure that it has a working cache purging setup. Go to WP Admin -> Servebolt -> Cache -> Cache purging and check the checkbox. Use either Accelerated Domains or Cloudflare as cache provider."),(0,o.kt)("li",{parentName:"ol"},"Ensure that you have cached content on 2 or more of the sites, like a sug page or article etc. This is so that we can confirm that we could clear cache for both/all sites."),(0,o.kt)("li",{parentName:"ol"},"We should test with and without using queue based cache purging (can be controlled from WP Admin -> Servebolt -> Cache -> Cache purging)")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Go to WP Admin -> My sites -> Network admin -> Dashboard"),(0,o.kt)("li",{parentName:"ol"},'Click Servebolt Optimizer (in the admin top bar) -> "Purge Cache for all sites"'),(0,o.kt)("li",{parentName:"ol"},"If using queue based cache purging then you need to wait for it to execute."),(0,o.kt)("li",{parentName:"ol"},"Ensure that the cache was purged on both/all sites.")),(0,o.kt)("p",null,"Expected result:\nWe should be able to purge all cache for all sites in multisite-network."),(0,o.kt)("h3",{id:"9-bugfix---undefined-property-in-sdk"},"9. Bugfix - Undefined property in SDK"),(0,o.kt)("p",null,"Jira-issue: WPSO-372",(0,o.kt)("br",{parentName:"p"}),"\n","This was an SDK-related issue that affected the plugin. Our API was updated, and this caused the structure of the error response from the API to change. The SDK was updated so that it could handle the new structure. No testing needed for this bugfix."),(0,o.kt)("h3",{id:"10-cache-purging-during-woocommerce-checkout---simplified-cache-purging"},"10. Cache purging during WooCommerce checkout - simplified cache purging"),(0,o.kt)("p",null,"Jira-issue: WPSO-380",(0,o.kt)("br",{parentName:"p"}),"\n","During WooCommerce checkout we previously purged cache for everything related to that product, including archives where the product was present etc. In some cases this caused a large amount of URLs to be purged cache for, and if one was not using queue based cache purging then it caused the checkout to be very slow, since the checkout needed to wait for all the URLs to be purged. The solution? Do a simplified cache purge on WooCommerce checkout, meaning we only purge the cache for the product URL, not the full URL hierarchy."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a site that uses WooCommerce."),(0,o.kt)("li",{parentName:"ol"},'Go to WP Admin -> WooCommerce -> Settings -> Payments and enable "Cash on delivery" so that we can easily do checkout without having to set up a payment gateway.'),(0,o.kt)("li",{parentName:"ol"},'Add a product and give it a price (so that we can buy it) + go to "Inventory" and check "Manage stock?" and add some stock quantity.'),(0,o.kt)("li",{parentName:"ol"},"Ensure the site has caching and cache purging activated. Use the queue based cache purge so that we can inspect the queue and confirm that the feature is working as expected."),(0,o.kt)("li",{parentName:"ol"},"Truncate the queue table before starting, so that we can confirm the test more easily."),(0,o.kt)("li",{parentName:"ol"},"Add ",(0,o.kt)("inlineCode",{parentName:"li"},"add_filter('sb_optimizer_woocommerce_force_immediate_purge', '__return_false');")," to a PHP-file that you place in the mu-plugins directory. This is to disable the immediate purge, and lets us still use the queue.")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Add the product to the cart."),(0,o.kt)("li",{parentName:"ol"},"Execute the checkout."),(0,o.kt)("li",{parentName:"ol"},'Inspect that database table "wp_queue" only contains the URL of the product, and not all the other related URLs (front page, archive URLs etc.).')),(0,o.kt)("p",null,"Expected result:\nWe should be able to do a WooCommerce checkout without any problems or delays, and the cache should be purged only for the product URL, not the whole product URL hierarchy."),(0,o.kt)("p",null,"Cleanup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Remove the PHP-file you put in the mu-plugins directory.")),(0,o.kt)("h3",{id:"11-immediate-cache-purge-during-woocommerce-checkout"},"11. Immediate cache purge during WooCommerce checkout"),(0,o.kt)("p",null,"Jira-issue: WPSO-207\nOn sites running WooCommerce, especially high traffic ones, we purge cache on checkout. This is to update the current stock amount which might be cached. If not then the available stock might be misrepresented in the shop."),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a site that uses WooCommerce."),(0,o.kt)("li",{parentName:"ol"},'Go to WP Admin -> WooCommerce -> Settings -> Payments and enable "Cash on delivery" so that we can easily do checkout without having to set up a payment gateway.'),(0,o.kt)("li",{parentName:"ol"},"Add a product and give it a price (so that we can buy it)."),(0,o.kt)("li",{parentName:"ol"},"Ensure the site has caching and cache purging activated."),(0,o.kt)("li",{parentName:"ol"},"Enable queue based cache purging."),(0,o.kt)("li",{parentName:"ol"},"Truncate the queue table before starting, so that we can confirm the test more easily.")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Navigate to the product and ensure that it is cached (by inspecting the cache miss/hit header and/or the cache age header)."),(0,o.kt)("li",{parentName:"ol"},"Add the product to the cart and execute checkout."),(0,o.kt)("li",{parentName:"ol"},'Inspect that database table "wp_queue" is still empty (since we truncated it in the setup process).'),(0,o.kt)("li",{parentName:"ol"},"Inspect that you get a cache miss when you navigate to the product (or check the cache age).")),(0,o.kt)("p",null,"Expected result:\nWhen doing a WooCommerce-checkout then the product in the checkout should be purged cache for. This should happen almost immediately since we are bypassing the queue based cache purging. Note that there migth still be is some delay for immediate cache purging tho."),(0,o.kt)("h3",{id:"12-bugfix---menu-optimizer-not-working-on-certain-sites"},"12. Bugfix - Menu optimizer not working on certain sites"),(0,o.kt)("p",null,"Jira-issue: WPSO-464/471/474\nSome sites are using filters to override/manipulate the menu setup, and this affected our menu optimizer feature in some cases, and caused it to not work. This should now be improved through us handling our filters in a more self-contained way without being dependent on date from 3rd party developers. "),(0,o.kt)("p",null,"Setup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Set up a WordPress site that has a menu that is visible on the front page"),(0,o.kt)("li",{parentName:"ol"},"Enable the Menu Optimizer-feature by going to WP Admin -> Servebolt -> Menu Optimizer and checking the checkbox, then saving."),(0,o.kt)("li",{parentName:"ol"},"Create a mu-plugin by creating a file at ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-content/mu-plugins/testing.php"),", and then fill the file with ",(0,o.kt)("inlineCode",{parentName:"li"},"<?php add_filter('sb_optimizer_is_dev_debug', '__return_true');"),".")),(0,o.kt)("p",null,"Steps:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Navigate to the front page without being logged in and inspect that the menu is cached. This can be seen by a text appended to the menu indicated that it is cached."),(0,o.kt)("li",{parentName:"ol"},"Navigate to a subpage and inspect that the menu is cached, and that the current page is flagged as active in the menu."),(0,o.kt)("li",{parentName:"ol"},"Navigate to the front page while logged in and inspect that the menu is not cached.")),(0,o.kt)("p",null,"Expected result:\nWe should be able to cache the menu, and the active state of the current page should still be highlighted."),(0,o.kt)("p",null,"Cleanup:"),(0,o.kt)("ol",null,(0,o.kt)("li",{parentName:"ol"},"Remove the mu-plugin file at ",(0,o.kt)("inlineCode",{parentName:"li"},"wp-content/mu-plugins/testing.php"),".")))}p.isMDXComponent=!0}}]);