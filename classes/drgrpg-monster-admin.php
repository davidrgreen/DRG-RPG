<?php
/**
 * Holds the DRGRPG_Monster_Admin class.
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
 * Holds all the code needed to make WordPress aware of the drgrpg_monster CPT
 * and customize its appearance on the admin side.
 *
 * @since 0.1.0
 */
class DRGRPG_Monster_Admin {

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

		// Customize the columns shown in the admin.
		add_filter( 'manage_edit-drgrpg_monster_columns', array( $self, 'alter_admin_columns' ) );
		add_filter( 'manage_drgrpg_monster_posts_custom_column', array( $self, 'alter_admin_columns_content' ), 10, 2 );
		add_filter( 'manage_edit-drgrpg_monster_sortable_columns', array( $self, 'make_columns_sortable' ), 10, 2 );
	}

	/**
	 * Register the drgrpg_monster CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_cpt() {
		$labels = array(
			'name' => _x( 'Monsters', 'Post Type General Name', 'drgrpg' ),
			'singular_name' => _x( 'Monster', 'Post Type Singular Name', 'drgrpg' ),
			'add_new' => __( 'Add New Monster', 'drgrpg' ),
			'add_new_item' => __( 'Add New Monster', 'drgrpg' ),
			'edit_item' => __( 'Edit Monster', 'drgrpg' ),
			'new_item' => __( 'New Monster', 'drgrpg' ),
			'view_item' => __( 'View Monster', 'drgrpg' ),
			'search_items' => __( 'Search Monsters', 'drgrpg' ),
			'not_found' => __( 'No Monsters found', 'drgrpg' ),
			'not_found_in_trash' => __( 'No Monsters found in Trash', 'drgrpg' ),
			'parent_item_colon'  => __( 'Parent Monster:', 'drgrpg' ),
			'menu_name' => __( 'Monsters', 'drgrpg' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __( 'Monsters you might encounter', 'drgrpg' ),
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
			'supports' => array( 'title', 'thumbnail' ),
		);

		register_post_type( 'drgrpg_monster', $args );
	} // end establish_cpt

	/**
	 * Create the custom meta boxes for the CPT using CMB2.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_metaboxes() {
		$prefix = '_drgrpg_monster_';

		$monster_cmb = new_cmb2_box( array(
			'id' => $prefix . 'metabox',
			'title' => __( 'Monster Stats', 'drgrpg' ),
			'object_types' => array( 'drgrpg_monster' ),
		) );

		$monster_cmb->add_field( array(
			'name' => __( 'Define Monster\'s Base Stats', 'drgrpg' ),
			'desc' => __( 'Each instance of a monster will have its stats slightly randomized, using the numbers you enter below as the base. If you enter 10 for STR then the monster\'s STR might be 9, 10, or 11.', 'drgrpg' ),
			'type' => 'title',
			'id' => $prefix . 'stats_title',
		) );

		$monster_cmb->add_field( array(
			'name' => __( 'Hit Points (HP)', 'drgrpg' ),
			'desc' => __( 'How much damage a monster can take before being defeated.', 'drgrpg' ),
			'id' => $prefix . 'hp',
			'type' => 'text_small',
			'default' => 10,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '1',
				'required' => 'required',
			),
		) );

		$monster_cmb->add_field( array(
			'name' => __( 'Attack', 'drgrpg' ),
			'desc' => __( 'Affects physical combat damage.', 'drgrpg' ),
			'id' => $prefix . 'attack',
			'type' => 'text_small',
			'default' => 1,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '1',
				'required' => 'required',
			),
		) );

		$monster_cmb->add_field( array(
			'name' => __( 'Defense', 'drgrpg' ),
			'desc' => __( 'Lowers the physical damage the monster takes in combat.', 'drgrpg' ),
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

		$monster_cmb->add_field( array(
			'name' => __( 'Exp Reward', 'drgrpg' ),
			'desc' => __( 'Amount of experience given when defeated', 'drgrpg' ),
			'id' => $prefix . 'reward_exp',
			'type' => 'text_small',
			'default' => 0,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '0',
				'required' => 'required',
			),
		) );

		$monster_cmb->add_field( array(
			'name' => __( 'Gold Reward', 'drgrpg' ),
			'desc' => __( 'Amount of gold given when defeated', 'drgrpg' ),
			'id' => $prefix . 'reward_gold',
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
		if ( 'drgrpg_monster' === get_current_screen()->post_type ) {
			return __( 'Enter name of monster', 'drgrpg' );
		} else {
			return $title;
		}
	}


	/**
	 * Change the columns shown on the main Monster CPT page of WP Admin.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param  array $columns  The columns shown by default, passed in by filter.
	 * @return array  The new array of column titles to use.
	 */
	public function alter_admin_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'image' => __( 'Image', 'drgrpg' ),
			'id' => __( 'ID', 'drgrpg' ),
			'title' => __( 'Name', 'drgrpg' ),
			'hp' => __( 'HP', 'drgrpg' ),
			'attack' => __( 'Attack', 'drgrpg' ),
			'defense' => __( 'Defense', 'drgrpg' ),
			'xp' => __( 'Exp', 'drgrpg' ),
			'gold' => __( 'Gold', 'drgrpg' ),
		);

		return $columns;
	}

	/**
	 * Populate the custom columns added by alter_admin_columns for
	 * the Monster CPT.
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
			case 'image' :
				echo get_the_post_thumbnail( $postId, array( 50, 50 ) );
				break;
			case 'hp':
				$hp = get_post_meta( $postId, '_drgrpg_monster_hp', true );
				if ( ! empty( $hp ) ) {
					echo esc_html( number_format( $hp ) );
				} else {
					echo '0';
				}
				break;
			case 'attack':
				$attack = get_post_meta( $postId, '_drgrpg_monster_attack', true );
				if ( ! empty( $attack ) ) {
					echo esc_html( number_format( $attack ) );
				} else {
					echo '0';
				}
				break;
			case 'defense':
				$defense = get_post_meta( $postId, '_drgrpg_monster_defense', true );
				if ( ! empty( $defense ) ) {
					echo esc_html( number_format( $defense ) );
				} else {
					echo '0';
				}
				break;
			case 'xp':
				$xp = get_post_meta( $postId, '_drgrpg_monster_reward_exp', true );
				if ( ! empty( $xp ) ) {
					echo esc_html( number_format( $xp ) );
				} else {
					echo '0';
				}
				break;
			case 'gold':
				$gold = get_post_meta( $postId, '_drgrpg_monster_reward_gold', true );
				if ( ! empty( $gold ) ) {
					echo esc_html( number_format( $gold ) );
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
} // end DRGRPG_Monster_Admin class
