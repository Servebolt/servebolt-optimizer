<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

return <<<EOF

#!/bin/bash
# Copyright © 2015 Bjørn Johansen
# Modified by Robert Sæther (Servebolt) to work with WooCommerce' Action Scheduler.
#
# This work is free. You can redistribute it and/or modify it under the
# terms of the Do What The Fuck You Want To Public License, Version 2,
# as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.

WP_PATH="/path/to/wp"

# Check if WP-CLI is available
if ! hash wp 2>/dev/null; then
	echo "WP-CLI is not available"
	exit
fi

# If WordPress isn’t installed here, we bail
if ! $(wp core is-installed --path="\$WP_PATH" --quiet); then
	echo "WordPress is not installed here: \${WP_PATH}"
	exit
fi

# Get a list of site URLs
if $(wp core is-installed --path="\$WP_PATH" --quiet --network);
then
	SITE_URLS=`wp site list --fields=url --archived=0 --deleted=0 --format=csv --path="\$WP_PATH" | sed 1d`
else
	SITE_URLS=(`wp option get siteurl --path="\$WP_PATH"`)
fi

# Loop through all the sites
for SITE_URL in \$SITE_URLS
do
	# Run all event hooks that are due
	for EVENT_HOOK in $(wp cron event list --format=csv --fields=hook,next_run_relative --url="\$SITE_URL" --path="\$WP_PATH" | grep now$ | awk -F ',' '{print $1}')
	do
		wp cron event run "\$EVENT_HOOK" --url="\$SITE_URL" --path="\$WP_PATH" --quiet
	done
done

EOF;
