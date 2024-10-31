<?php

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

/**
 * The table class responsible for rendering networks list.
 *
 * @since 1.0.0
 */
class WPMNET_Networks_List_Table extends WP_List_Table {

	/**
	 * Returns array of bulk actions.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @return array The array of bulk actions.
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => esc_html__( 'Delete', 'wpmnet' ),
		);
	}

	/**
	 * Returns columns array.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox">',
			'network' => esc_html__( 'Network', 'wpmnet' ),
			'url'     => esc_html__( 'URL Address', 'wpmnet' ),
			'blogs'   => esc_html__( 'Blogs', 'wpmnet' ),
		);
	}

	/**
	 * Returns value for checkbox column.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $item The array of network information.
	 * @return string The checkbox column value.
	 */
	public function column_cb( $item ) {
		return $item['id'] != SITE_ID_CURRENT_SITE
			? sprintf( '<input type="checkbox" class="cb" name="network[]" value="%d">', $item['id'] )
			: '';
	}

	/**
	 * Returns value for blogs column.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $item The array of network information.
	 * @return string The blogs column value.
	 */
	public function column_blogs( $item ) {
		return absint( $item['blogs'] );
	}

	/**
	 * Returns network name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $item The array of network information.
	 * @return string The network name.
	 */
	public function column_network( $item ) {
		$dashboard = esc_url( $item['url'] . 'wp-admin/network/' );
		$actions = array(
			'view'      => sprintf( '<a href="%s">%s</a>', esc_url( $item['url'] ), __( 'View', 'wpmet' ) ),
			'dashboard' => sprintf( '<a href="%s">%s</a>', $dashboard, __( 'Dashboard', 'wpmnet' ) ),
			'settings'  => sprintf( '<a href="%s">%s</a>', esc_url( $item['url'] . 'wp-admin/network/settings.php' ), __( 'Settings', 'wpmnet' ) ),
		);

		if ( $item['id'] != SITE_ID_CURRENT_SITE ) {
			$delete_link = add_query_arg( array( 'action' => 'delete_network', 'network' => $item['id'] ), network_admin_url( 'admin.php' ) );
			$delete_link = wp_nonce_url( $delete_link, 'delete_network' . $item['id'] );

			$actions['delete'] = sprintf( '<a href="%s" onclick="return showNotice.warn();">%s</a>', $delete_link, __( 'Delete', 'wpmnet' ) );
		}

		return sprintf(
			'<a href="%s"><b>%s</b></a> %s',
			$dashboard,
			esc_html( $item['name'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Returns network home url.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array $item The array of network information.
	 * @return string The network home url.
	 */
	public function column_url( $item ) {
		return sprintf( '<a href="%1$s">%1$s</a>', esc_url( $item['url'] ) );
	}

	/**
	 * Prepares items.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @global wpdb $wpdb The database connection.
	 */
	public function prepare_items() {
		global $wpdb;

		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $this->get_columns(), array(), $sortable );

		$per_page = $this->get_items_per_page( 'networks_per_page' );
		$offset = ( $this->get_pagenum() - 1 ) * $per_page;
		$query = "
			SELECT SQL_CALC_FOUND_ROWS s.id, sm1.meta_value AS 'name', sm2.meta_value AS 'url', sm3.meta_value AS 'blogs'
			  FROM {$wpdb->site} AS s
			  LEFT JOIN {$wpdb->sitemeta} AS sm1 ON sm1.site_id = s.id AND sm1.meta_key = 'site_name'
			  LEFT JOIN {$wpdb->sitemeta} AS sm2 ON sm2.site_id = s.id AND sm2.meta_key = 'siteurl'
			  LEFT JOIN {$wpdb->sitemeta} AS sm3 ON sm3.site_id = s.id AND sm3.meta_key = 'blog_count'
			 LIMIT {$per_page}
			OFFSET {$offset}
		";

		$this->items = $wpdb->get_results( $query, ARRAY_A );
		$total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

}
