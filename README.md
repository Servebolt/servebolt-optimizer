# Servebolt Optimizer

Note: this document is WIP!

This repository contains the WordPress plugin Servebolt Optimizer - a plugin that:
- optimizes your WordPress site
- gives you neat features related to Cloudflare's services (cache purge, image resize)
- gives you additional benefits when hosting your site at Servebolt

## Development

### Testing
TODO: Robert needs to add instructions on setting up the test environment

### Build assets
1. Run `yarn install`
2. Run `yarn build` for local or `yarn production` for production

### Phan - static analyzer for PHP
Phan helps identifying errors in your code.

Let's start by setting it up:

1. Run `composer install`
2. Run `composer test`

But Phan also needs to parse the code that is not part of the plugin to get things right.
Add a folder called `wp-sources` to the repo, this file will be ignored by Git. Then add the following folders to it:
- WordPress (https://github.com/WordPress/WordPress)
- WP CLI (https://github.com/wp-cli/wp-cli)

You can download them or just check them out using Git. I also like to symlink the folder in to the plugin folder, so that you can reuse it for other WP plugins using Phan.
Now the static analyzer will analyze the plugin while still having access to the source code. If not the analyzer will complain a lot about functions, methods and classes that does not exist.


