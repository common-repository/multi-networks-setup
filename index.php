<?php
/*
Plugin Name: Multi Networks Setup
Plugin URI: http://wordpress.org/plugins/multi-networks-setup/
Description: The plugin allows you to create multiple networks based on your WordPress 3.0 network installation.
Version: 1.0.0
Network: true
Author: MadPixels
Author URI: http://madpixels.net/
License: GPL v2.0 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// +----------------------------------------------------------------------+
// | Copyright 2013  MadPixels  (email : contact@madpixels.net)           |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+
// | Author: Eugene Manuilov <eugene.manuilov@gmail.com>                  |
// +----------------------------------------------------------------------+

// register action hooks
add_action( 'admin_bar_menu', 'wpmnet_add_admin_bar_menu', 11 );
add_action( 'network_admin_menu', 'wpmnet_add_network_menu' );
add_action( 'admin_action_create_network', 'wpmnet_process_create_network_submission' );
add_action( 'admin_action_delete_network', 'wpmnet_process_delete_network_submission' );

// add filter hooks
add_filter( 'get_blogs_of_user', 'wpmnet_filter_user_blogs' );
add_filter( 'populate_network_meta', 'wpmnet_populate_network_meta' );

/**
 * Filters user blogs to prevent different sites mixing.
 *
 * @since 1.0.0
 * @filter get_blogs_of_user
 *
 * @param array $blogs The initial array of blogs.
 * @return array The array of blogs related to the current site only.
 */
function wpmnet_filter_user_blogs( $blogs ) {
	return wp_list_filter( $blogs, array( 'site_id' => SITE_ID_CURRENT_SITE ) );
}

/**
 * Fixes network meta information.
 *
 * @since 1.0.0
 * @filter populate_network_meta
 *
 * @global object $current_site The new network object.
 * @param array $meta The array of network meta information.
 * @return array The array with fixed meta information.
 */
function wpmnet_populate_network_meta( $meta ) {
	global $current_site;
	$user = wp_get_current_user();

	$meta['site_admins'] = array( $user->user_login );
	$meta['siteurl'] = 'http://' . $current_site->domain . '/';

	return $meta;
}

/**
 * Registers networks menu in the admin bar menu.
 *
 * @since 1.0.0
 * @action admin_bar_menu 11
 *
 * @global wpdb $wpdb The database connection.
 * @param WP_Admin_Bar $admin_bar The admin bar object.
 * @return void
 */
function wpmnet_add_admin_bar_menu( WP_Admin_Bar $admin_bar ) {
	global $wpdb;

	if ( current_user_can( 'manage_network' ) ) {
		echo '<style type="text/css">#wp-admin-bar-networks > .ab-item:before { content: "\f319"; padding-top: 6px; }</style>';

		$admin_bar->add_menu( array(
			'id'    => 'networks',
			'meta'  => array(),
			'title' => __( 'Networks', 'wpmnet' ),
			'href'  => network_admin_url( 'admin.php?page=networks' ),
		) );

		$sites = $wpdb->get_results( "
			SELECT s.*, sm.meta_value AS 'name'
			FROM {$wpdb->site} AS s
			LEFT JOIN {$wpdb->sitemeta} AS sm ON sm.site_id = s.id AND sm.meta_key = 'site_name'
		", ARRAY_A );

		foreach ( $sites as $site ) {
			$admin_bar->add_menu( array(
				'parent' => 'networks',
				'id'     => sanitize_title( "{$site['id']} {$site['name']}" ),
				'title'  => $site['name'],
				'href'   => set_url_scheme( 'http://' . $site['domain'] . $site['path'] . 'wp-admin/network/', 'admin' ),
			) );
		}
	}
}

/**
 * Registers network menu item.
 *
 * @since 1.0.0
 * @action network_admin_menu
 *
 * @return void
 */
function wpmnet_add_network_menu() {
	$parent_slug = 'networks';
	$parent_page_title = __( 'All Networks', 'wpmnet' );
	add_menu_page( $parent_page_title, __( 'Networks', 'wpmnet' ), 'manage_network', $parent_slug, 'wpmnet_render_networks_page', 'dashicons-admin-site', '4.99' );
	add_submenu_page( $parent_slug, $parent_page_title, $parent_page_title, 'manage_network', $parent_slug, 'wpmnet_render_networks_page' );
	add_submenu_page( $parent_slug, __( 'Create new network', 'wpmnet' ), __( 'Add New' ), 'manage_network', 'create-network', 'wpmnet_render_create_network_page' );
}

/**
 * Renders all networks page.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpmnet_render_networks_page() {
	require_once 'class-wpmnet-networks-list-table.php';

	$table = new WPMNET_Networks_List_Table();
	$table->prepare_items();

	echo '<div class="wrap">';
		echo '<h2>';
			esc_html_e( 'Networks', 'wpmnet' );
			if ( current_user_can( 'create_sites' ) ) :
				echo '<a href="?page=create-network" class="add-new-h2">', esc_html__( 'Add New', 'wpmnet' ), '</a>';
			endif;
		echo '</h2>';

		echo '<form id="form-network-list" action="', network_admin_url( 'admin.php' ), '" method="post">';
			$table->display();
		echo '</form>';
	echo '</div>';
}

/**
 * Renders create network page.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpmnet_render_create_network_page() {
	$messages = array();
	if ( isset( $_GET['update'] ) ) {
		if ( 'added' == $_GET['update'] ) {
			$messages[] = __( 'Network added successfully.', 'wpmnet' );
		}
	}

	wp_enqueue_script( 'user-suggest' );

	echo '<div class="wrap">';
		echo '<h2>';
			esc_html_e( 'Add New Network', 'wpmnet' );
		echo '</h2>';

		foreach ( $messages as $msg ) {
			echo '<div id="message" class="updated"><p>', $msg, '</p></div>';
		}

		echo '<form id="form-network-list" action="', network_admin_url( 'admin.php' ), '" method="post">';
			echo '<input type="hidden" name="action" value="create_network">';
			wp_nonce_field( 'create_network' . get_current_user_id() );

			echo '<table class="form-table">';
				echo '<tr class="form-field form-required">';
					echo '<th scope="row">', __( 'Network Address', 'wpment' ), '</th>';
					echo '<td>';
						echo '<input name="network[domain]" class="regular-text" type="text" required title="', esc_attr__( 'Domain' ), '" placeholder="http://example.com/" style="line-height: 1.4em">';
						echo '<p>', __( 'Only valid domain names are allowed.', 'wpmnet' ), '</p>';
					echo '</td>';
				echo '</tr>';

				echo '<tr class="form-field form-required">';
					echo '<th scope="row">', __( 'Network Title', 'wpmnet' ), '</th>';
					echo '<td><input name="network[title]" type="text" class="regular-text" required title="', esc_attr__( 'Title' ), '"></td>';
				echo '</tr>';

				echo '<tr class="form-field form-required">';
					echo '<th scope="row">', __( 'Admin Email' ), '</th>';
					echo '<td><input name="network[email]" type="text" required class="regular-text wp-suggest-user" data-autocomplete-type="search" data-autocomplete-field="user_email" title="', esc_attr__( 'Email' ), '"></td>';
				echo '</tr>';

				echo '<tr class="form-field">';
					echo '<td colspan="2">', __( 'A new user will be created if the above email address is not in the database.' ), '<br>', __( 'The username and password will be mailed to this email address.' ), '</td>';
				echo '</tr>';
			echo '</table>';

			submit_button( __( 'Add Network', 'wpmnet' ), 'primary', 'add-network' );
		echo '</form>';
	echo '</div>';
}

/**
 * Processes create network form submission.
 *
 * @since 1.0.0
 * @action admin_action_create_network
 *
 * @global wpdb $wpdb The database connection.
 * @global object $current_site The current network object.
 * @return void
 */
function wpmnet_process_create_network_submission() {
	global $wpdb, $current_site;

	if ( $_SERVER['REQUEST_METHOD'] != 'POST' || !check_admin_referer( 'create_network' . get_current_user_id() ) ) {
		wp_redirect( add_query_arg( 'page', 'create-network', network_admin_url( 'admin.php' ) ) );
		exit;
	}

	$domain = $_POST['network']['domain'];
	if ( strpos( $domain, '://' ) === false ) {
		$domain = 'http://' . $domain;
	}

	$domain = explode( '/', $domain );
	$domain = array_slice( $domain, 0, 3 );
	$domain = trailingslashit( implode( '/', $domain ) );

	if ( !filter_var( $domain, FILTER_VALIDATE_URL ) ) {
		wp_die( __( 'Missing or invalid network address.', 'wpmnet' ) );
	}

	$domain = parse_url( $domain, PHP_URL_HOST );

	$email = $_POST['network']['email'];
	if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
		wp_die( __( 'Missing or invalid email address.', 'wpmnet' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/schema.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$network_id = $wpdb->get_var( "SELECT id FROM {$wpdb->site} ORDER BY id DESC LIMIT 1" ) + 1;

	$current_site = new stdClass();
	$current_site->domain = $domain;
	$current_site->path = '/';
	$current_site->site_name = $_POST['network']['title'];
	$current_site->id = $network_id;
	$wpdb->siteid = $network_id;

	populate_network( $network_id, $domain, $email, $_POST['network']['title'], '/', defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL );

	$password = 'N/A';
	$user_id = email_exists($email);
	if ( !$user_id ) { // Create a new user with a random password
		$password = wp_generate_password( 12, false );
		$user_id = wpmu_create_user( $domain, $password, $email );
		if ( false == $user_id ) {
			wp_die( __( 'There was an error creating the user.' ) );
		} else {
			wp_new_user_notification( $user_id, $password );
		}
	}

	add_action( 'switch_blog', 'wpmnet_setup_current_site_blog' );

	$wpdb->hide_errors();
	$id = wpmu_create_blog( $domain, '/', $_POST['network']['title'], $user_id , array( 'public' => 1 ), $network_id );
	$wpdb->show_errors();
	if ( !is_wp_error( $id ) ) {
		if ( !is_super_admin( $user_id ) && !get_user_option( 'primary_blog', $user_id ) ) {
			update_user_option( $user_id, 'primary_blog', $id, true );
		}

		wpmu_welcome_notification( $id, $user_id, $password, $_POST['network']['title'], array( 'public' => 1 ) );

		switch_to_blog( $id );
		activate_plugin( __FILE__, false, true, true );

		wp_redirect( add_query_arg( array( 'page' => 'create-network', 'update' => 'added', 'id' => $id ), 'admin.php' ) );
		exit;
	} else {
		wp_die( $id->get_error_message() );
	}
}

/**
 * Setups blog id for new network.
 *
 * @since 1.0.0
 * @action switch_blog
 *
 * @global object $current_site The new network object.
 * @global wpdb $wpdb The database connection.
 * @param int $blog_id The new network root blog id.
 */
function wpmnet_setup_current_site_blog( $blog_id ) {
	global $current_site, $wpdb;

	$current_site->blog_id = $blog_id;
	$wpdb->blogid = $blog_id;

	if ( !defined( 'UPLOADBLOGSDIR' ) ) {
		define( 'UPLOADBLOGSDIR', 'wp-content/blogs.dir' );
	}

	if ( !defined( 'UPLOADS' ) ) {
		define( 'UPLOADS', UPLOADBLOGSDIR . "/{$wpdb->blogid}/files/" );
	}
}

/**
 * Processes network deletion.
 *
 * @since 1.0.0
 * @action admin_action_delete_network
 *
 * @global wpdb $wpdb The database connection.
 * @return void
 */
function wpmnet_process_delete_network_submission() {
	global $wpdb;

	$network_id = filter_input( INPUT_GET, 'network', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'default' => false ) ) );
	if ( !$network_id || $network_id == SITE_ID_CURRENT_SITE || !check_admin_referer( 'delete_network' . $network_id ) ) {
		wp_redirect( add_query_arg( 'page', 'networks', network_admin_url( 'admin.php' ) ) );
		exit;
	}

	$blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = {$network_id}" );
	foreach ( $blogs as $blog ) {
		wpmu_delete_blog( $blog, true );
	}

	$wpdb->delete( $wpdb->sitemeta, array( 'site_id' => $network_id ), array( '%d' ) );
	$wpdb->delete( $wpdb->site, array( 'id' => $network_id ), array( '%d' ) );

	wp_redirect( wp_get_referer() );
	exit;
}
