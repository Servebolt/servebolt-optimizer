<?php

namespace Servebolt\Optimizer\WpCron\Tasks;

class DeleteExpiredTransients {

    static public function esc_like( $text ) {
        return addcslashes( $text, '_%\\' );
    }

    static public function remove() {
        if ( ! is_multisite() ) {
            error_log("starting remove transients, single site");
            self::clean_options_table();
        } else {
            error_log("starting remove transients, multi site");
            // Clear  
            $start_id = get_current_blog_id();
            // get sites
            $sites = get_sites();
            // loop sites
            foreach($sites as $site) {
                switch_to_blog($site->blog_id);
                self::clean_options_table();
                restore_current_blog();
            }
            switch_to_blog($start_id);
            // Now clear the sitemeta table
            error_log("now removing transients, from sitemeta table");
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
                        WHERE a.meta_key LIKE %s
                        AND a.meta_key NOT LIKE %s
                        AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
                        AND b.meta_value < %d",
                    self::esc_like( '_site_transient_' ) . '%',
                    self::esc_like( '_site_transient_timeout_' ) . '%',
                    time()
                )
            );
        }
        // }
    }

    static function clean_options_table()
    {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
                    WHERE a.option_name LIKE %s
                    AND a.option_name NOT LIKE %s
                    AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
                    AND b.option_value < %d",
                self::esc_like( '_site_transient_' ) . '%',
                self::esc_like( '_site_transient_timeout_' ) . '%',
                time()
            )
        );
    }
    
}
