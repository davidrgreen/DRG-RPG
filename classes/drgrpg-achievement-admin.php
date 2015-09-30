<?php
/**
 * Holds the DRGRPG_Achievement_Admin class
 *
 * @package DRGRPG
 * @author David Green <david@davidrg.com>
 * @license GPL2
 * @link https://github.com/davidrgreen/DRGRPG
 * @copyright 2015 David Green
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Holds all the code needed to make WordPress aware of the
 * drgrpg_achievement CPT and customize its appearance on the admin side.
 *
 * @since 0.1.0
 */
class DRGRPG_Achievement_Admin {

	/**
	 * Run all the add_actions and add_filters needed to hook into WordPress
	 * and make it aware of the CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @static
	 * @return void
	 */
	public static function setup_in_wp() {

		// Since this is a static method we need to instantiate an instance of
		// the class so its methods can be used by the WordPress hooks below.
		$self = new self();

		add_action( 'init', array( $self, 'establish_cpt' ) );
		add_filter( 'enter_title_here', array( $self, 'change_title_placeholder' ) );

		add_filter( 'manage_edit-drgrpg_achievement_columns', array( $self, 'alter_admin_columns' ) );
		add_filter( 'manage_drgrpg_achievement_posts_custom_column', array( $self, 'alter_admin_columns_content' ), 10, 2 );
		add_filter( 'manage_edit-drgrpg_achievement_sortable_columns', array( $self, 'make_columns_sortable' ), 10, 2 );
	}

	/**
	 * Register the drgrpg_achievement CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_cpt() {
		$labels = array(
			'name' => _x( 'Achievements', 'Post Type General Name', 'drgrpg' ),
			'singular_name' => _x( 'Achievement', 'Post Type Singular Name', 'drgrpg' ),
			'add_new' => __( 'Add New Achievement', 'drgrpg' ),
			'add_new_item' => __( 'Add New Achievement', 'drgrpg' ),
			'edit_item' => __( 'Edit Achievement', 'drgrpg' ),
			'new_item' => __( 'New Achievement', 'drgrpg' ),
			'view_item' => __( 'View Achievement', 'drgrpg' ),
			'search_items' => __( 'Search Achievements', 'drgrpg' ),
			'not_found' => __( 'No Achievements found', 'drgrpg' ),
			'not_found_in_trash' => __( 'No Achievements found in Trash', 'drgrpg' ),
			'parent_item_colon' => __( 'Parent Achievement:', 'drgrpg' ),
			'menu_name' => __( 'Achievements', 'drgrpg' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __( 'Achievements a player can earn', 'drgrpg' ),
			'taxonomies' => array(),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_admin_bar' => true,
			'menu_position' => null,
			'menu_icon' => null,
			'show_in_nav_menus' => true,
			'publicly_queryable'	 => true,
			'exclude_from_search' => true,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'supports' => array( 'title', 'editor' ),
		);

		register_post_type( 'drgrpg_achievement', $args );
	}

	/**
	 * Change the placeholder text shown in the Title text field
	 * for this CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $title Default title, passed in by filter.
	 * @return string The new title.
	 */
	public function change_title_placeholder( $title ) {
		if ( 'drgrpg_achievement' === get_current_screen()->post_type ) {
			return __( 'Enter name of achievement', 'drgrpg' );
		} else {
			return $title;
		}
	}

	/**
	 * Change the columns shown on the main Achievement CPT page of WP Admin.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param  array $columns  The columns shown by default, passed in by filter.
	 * @return  array  The new array of column titles to use.
	 */
	public function alter_admin_columns( $columns ) {
		$columns = array(
			'cb'	=> '<input type="checkbox" />',
			'title' => __( 'Name', 'drgrpg' ),
			'id' => 'ID',
		);

		return $columns;
	}

	/**
	 * Populate the custom columns added by alter_admin_columns for
	 * the Achievement CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $column Column name, provided by filter.
	 * @param integer $postId The ID of the current post.
	 * @return void
	 */
	public function alter_admin_columns_content( $column, $postId ) {
		global $post;

		switch ( $column ) {
			case 'id':
				echo get_the_ID();
				break;
			default :
				break;
		}
	}

	/**
	 * Make specific columns sortable.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $columns  Array of columns that can be sorted.
	 * @return array Altered array of columns to be sorted.
	 */
	public function make_columns_sortable( $columns ) {
		$columns['id'] = 'id';

		return $columns;
	}
}
