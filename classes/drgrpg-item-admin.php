<?php
/**
 * Holds the DRGRPG_Item_Admin class.
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
 * Holds all the code needed to make WordPress aware of the drgrpg_item CPT
 * and customize its appearance on the admin side.
 *
 * @since 0.1.0
 */
class DRGRPG_Item_Admin {

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
		add_action( 'cmb2_init', array( $self, 'establish_metaboxes' ) );

		add_filter( 'enter_title_here', array( $self, 'change_title_placeholder' ) );

		add_filter( 'manage_edit-drgrpg_item_columns', array( $self, 'alter_admin_columns' ) );
		add_filter( 'manage_drgrpg_item_posts_custom_column', array( $self, 'alter_admin_columns_content' ), 10, 2 );
		add_filter( 'manage_edit-drgrpg_item_sortable_columns', array( $self, 'make_columns_sortable' ), 10, 2 );
	}

	/**
	 * Register the drgrpg_item CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_cpt() {
		$labels = array(
			'name' => _x( 'Items', 'Post Type General Name', 'drgrpg' ),
			'singular_name' => _x( 'Item', 'Post Type Singular Name', 'drgrpg' ),
			'add_new' => __( 'Add New Item', 'drgrpg' ),
			'add_new_item' => __( 'Add New Item', 'drgrpg' ),
			'edit_item' => __( 'Edit Item', 'drgrpg' ),
			'new_item' => __( 'New Item', 'drgrpg' ),
			'view_item' => __( 'View Item', 'drgrpg' ),
			'search_items' => __( 'Search Items', 'drgrpg' ),
			'not_found' => __( 'No Items found', 'drgrpg' ),
			'not_found_in_trash' => __( 'No Items found in Trash', 'drgrpg' ),
			'parent_item_colon' => __( 'Parent Item:', 'drgrpg' ),
			'menu_name' => __( 'Items', 'drgrpg' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __( 'Items able to be used or worn within the game.', 'drgrpg' ),
			'taxonomies' => array(),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_admin_bar' => true,
			'menu_position' => null,
			'menu_icon' => null,
			'show_in_nav_menus' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'has_archive' => false,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'supports' => array( 'title' ),
		);

		register_post_type( 'drgrpg_item', $args );
	} // end establish_cpt

	/**
	 * Create the custom meta boxes for the CPT using CMB2.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_metaboxes() {
		$prefix = '_drgrpg_item_';

		$item_cmb = new_cmb2_box( array(
			'id' => $prefix . 'metabox',
			'title' => __( 'Item Info', 'drgrpg' ),
			'object_types' => array( 'drgrpg_item' ),
		) );

		$item_cmb->add_field( array(
			'name' => __( 'Item Description', 'drgrpg' ),
			'desc' => __( 'Describe the item.', 'drgrpg' ),
			'id' => $prefix . 'description',
			'type' => 'wysiwyg',
			'options' => array(),
		) );

		$item_cmb->add_field( array(
			'name' => __( 'Item Type', 'drgrpg' ),
			'desc' => __( 'Choose what type of item this will be', 'drgrpg' ),
			'id' => $prefix . 'type',
			'type' => 'select',
			'show_option_none' => false,
			'default' => 'weapon',
			'options' => array(
				'back' => __( 'Back', 'drgrpg' ),
				'bodyarmor' => __( 'BodyArmor', 'drgrpg' ),
				'boots' => __( 'Boots', 'drgrpg' ),
				'helmet' => __( 'Helmet', 'drgrpg' ),
				'leggings' => __( 'Leggings', 'drgrpg' ),
				'necklace' => __( 'Necklace', 'drgrpg' ),
				'shield' => __( 'Shield', 'drgrpg' ),
				'weapon' => __( 'Weapon', 'drgrpg' ),
			),
		) );

		$item_cmb->add_field( array(
			'name' => __( 'Attack', 'drgrpg' ),
			'desc' => __( 'How much this item boosts attack when equipped.', 'drgrpg' ),
			'id' => $prefix . 'attack',
			'type' => 'text_small',
			'default' => 0,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '0',
				'required' => 'required',
			),
		) );

		$item_cmb->add_field( array(
			'name' => __( 'Defense' ),
			'desc' => __( 'How much this item boosts defense when equipped.', 'drgrpg' ),
			'id' => $prefix . 'defense',
			'type' => 'text_small',
			'default' => 0,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '0',
				'required' => 'required',
			),
		) );
	} // end establish_metaboxes


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
		if ( 'drgrpg_item' === get_current_screen()->post_type ) {
			return __( 'Enter name of item', 'drgrpg' );
		} else {
			return $title;
		}
	}

	/**
	 * Change the columns shown on the main Item CPT page of WP Admin.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $columns  The columns shown by default, passed in by filter.
	 * @return array The new array of column titles to use.
	 */
	public function alter_admin_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'id' => __( 'ID', 'drgrpg' ),
			'title' => __( 'Name', 'drgrpg' ),
			'type' => __( 'Type', 'drgrpg' ),
			'attack' => __( 'Attack', 'drgrpg' ),
			'defense' => __( 'Defense', 'drgrpg' ),
		);

		return $columns;
	}

	/**
	 * Populate the custom columns added by alter_admin_columns for
	 * the Item CPT
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
			case 'type':
				$type = get_post_meta( $postId, '_drgrpg_item_type', true );
				if ( ! empty( $type ) ) {
					echo esc_html( $type );
				}
				break;
			case 'attack':
				$attack = get_post_meta( $postId, '_drgrpg_item_attack', true );
				if ( ! empty( $attack ) ) {
					echo esc_html( number_format( $attack ) );
				} else {
					echo '0';
				}
				break;
			case 'defense':
				$defense = get_post_meta( $postId, '_drgrpg_item_defense', true );
				if ( ! empty( $defense ) ) {
					echo esc_html( number_format( $defense ) );
				} else {
					echo '0';
				}
				break;
			default :
				break;
		}
	} // end alter_admin_columns_content

	/**
	 * Make specific columns sortable.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $columns Array of columns that can be sorted.
	 * @return array Altered array of columns to be sorted.
	 */
	public function make_columns_sortable( $columns ) {
		$columns['id'] = 'id';

		return $columns;
	}
} // end DRGRPG_Item_Admin class
