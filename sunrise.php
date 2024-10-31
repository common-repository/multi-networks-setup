<?php

/**
 * Setups current site information and blog id.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb The database connection.
 * @return void
 */
function wpmnet_setup_current_site() {
	global $wpdb;

	// prevent double setup
	if ( defined( 'SITE_ID_CURRENT_SITE' ) ) {
		return;
	}

	// setup information
	$site = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->site} WHERE domain = %s", $_SERVER['HTTP_HOST'] ) );
	if ( $site ) {
		define( 'SITE_ID_CURRENT_SITE', $site->id );
		define( 'DOMAIN_CURRENT_SITE', $site->domain );
		define( 'PATH_CURRENT_SITE', $site->path );

		// FIXME: need to improve this part, looks like this stuff is redundant
		$blog = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} WHERE site_id = %d ORDER BY blog_id ASC LIMIT 1", $site->id ) );
		if ( $blog ) {
			define( 'BLOG_ID_CURRENT_SITE', $blog->blog_id );
		} else {
			define( 'BLOG_ID_CURRENT_SITE', 1 );
		}
	} else {
		define( 'DOMAIN_CURRENT_SITE', 'market.proj' );
		define( 'PATH_CURRENT_SITE', '/' );
		define( 'SITE_ID_CURRENT_SITE', 1);
		define( 'BLOG_ID_CURRENT_SITE', 1 );
	}
}

// run site setup
wpmnet_setup_current_site();