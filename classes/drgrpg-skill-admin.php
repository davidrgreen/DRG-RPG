<?php
/**
 * Holds the DRGRPG_Skill_Admin class.
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
 * Class holds all the code needed to make WordPress aware of the drgrpg_guild CPT
 * and customize its appearance on the admin side.
 *
 * @since 0.1.0
 */
class DRGRPG_Skill_Admin {

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

		add_filter( 'manage_edit-drgrpg_skill_columns', array( $self, 'alter_admin_columns' ) );
		add_filter( 'manage_drgrpg_skill_posts_custom_column', array( $self, 'alter_admin_columns_content' ), 10, 2 );
	}

	/**
	 * Register the drgrpg_skill CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_cpt() {
		$labels = array(
			'name' => _x( 'Skills', 'Post Type General Name', 'drgrpg' ),
			'singular_name' => _x( 'Skill', 'Post Type Singular Name', 'drgrpg' ),
			'add_new' => __( 'Add New Skill', 'drgrpg' ),
			'add_new_item' => __( 'Add New Skill', 'drgrpg' ),
			'edit_item' => __( 'Edit Skill', 'drgrpg' ),
			'new_item' => __( 'New Skill', 'drgrpg' ),
			'view_item' => __( 'View Skill', 'drgrpg' ),
			'search_items' => __( 'Search Skills', 'drgrpg' ),
			'not_found' => __( 'No Skills found', 'drgrpg' ),
			'not_found_in_trash' => __( 'No Skills found in Trash', 'drgrpg' ),
			'parent_item_colon' => __( 'Parent Skill:', 'drgrpg' ),
			'menu_name' => __( 'Skills', 'drgrpg' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __( 'Skills a player can learn and use.', 'drgrpg' ),
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
			'supports' => array( 'title' ),
		);

		register_post_type( 'drgrpg_skill', $args );
	}

	/**
	 * Create the custom meta boxes for the CPT using CMB2.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_metaboxes() {
		$prefix = '_drgrpg_skill_';

		$skill_cmb = new_cmb2_box( array(
			'id' => $prefix . 'metabox',
			'title' => __( 'Skill Info', 'drgrpg' ),
			'object_types' => array( 'drgrpg_skill' ),
		) );

		$skill_cmb->add_field( array(
			'name' => __( 'Skill Description', 'drgrpg' ),
			'desc' => __( 'Describe the skill.', 'drgrpg' ),
			'id' => $prefix . 'description',
			'type' => 'wysiwyg',
			'options' => array(),
		) );

		$skill_cmb->add_field( array(
			'name' => __( '# of Targets', 'drgrpg' ),
			'desc' => __( 'How many enemies does this hit?', 'drgrpg' ),
			'id' => $prefix . 'targets',
			'type' => 'select',
			'show_option_none'	 => false,
			'default' => 'one',
			'options' => array(
				'one' => __( 'One Enemy', 'drgrpg' ),
				'all' => __( 'All Enemies', 'drgrpg' ),
			),
		) );

		$skill_cmb->add_field( array(
			'name' => __( 'Effect', 'drgrpg' ),
			'desc' => __( 'What effect will be caused by the skill?', 'drgrpg' ),
			'id' => $prefix . 'effect',
			'type' => 'select',
			'show_option_none'	 => false,
			'default' => 'damageMonster',
			'options' => array(
				'damageMonster' => __( 'Damage Monster', 'drgrpg' ),
				'none' => __( 'None - Used by Quests', 'drgrpg' ),
			),
		) );

		$skill_cmb->add_field( array(
			'name' => __( 'Strength of Skill', 'drgrpg' ),
			'desc' => __( 'How strong of an effect does this skill have?', 'drgrpg' ),
			'id' => $prefix . 'strength',
			'type' => 'text_small',
			'default' => 20,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '0',
				'required' => 'required',
			),
		) );

		$skill_cmb->add_field( array(
			'name' => __( 'Variability ', 'drgrpg' ),
			'desc' => __( 'What percentage up/down can the strength be? Set to 0 to use do exactly the specific strength every time. Setting to 5 would allow the skill to randomly be 1-5% stronger or weaker.', 'drgrpg' ),
			'id' => $prefix . 'variability',
			'type' => 'text_small',
			'default' => 0,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '0',
				'required' => 'required',
			),
		) );

		$skill_cmb->add_field( array(
			'name' => __( 'MP Cost', 'drgrpg' ),
			'desc' => __( 'Number of MP required to use this skill.', 'drgrpg' ),
			'id' => $prefix . 'cost',
			'type' => 'text_small',
			'default' => 5,
			'attributes' => array(
				'type' => 'number',
				'pattern' => '\d*',
				'min' => '0',
				'required' => 'required',
			),
		) );
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
		if ( 'drgrpg_skill' === get_current_screen()->post_type ) {
			return __( 'Enter name of skill', 'drgrpg' );
		} else {
			return $title;
		}
	}

	/**
	 * Change the columns shown on the main Skill CPT page of WP Admin.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $columns The columns shown by default, passed in by filter.
	 * @return array The new array of column titles to use.
	 */
	public function alter_admin_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Name',
			'id' => 'ID',
			'effect' => 'Effect',
			'strength' => 'Strength',
			'variability' => 'Variability',
		);

		return $columns;
	}

	/**
	 * Populate the custom columns added by alter_admin_columns for
	 * the Skill CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $column Column name, provided by filter.
	 * @param integer $postId The ID of the current post.
	 * @return  void
	 */
	public function alter_admin_columns_content( $column, $postId ) {
		global $post;

		switch ( $column ) {
			case 'id':
				echo get_the_ID();
				break;
			case 'effect':
				$effect = get_post_meta( $postId, '_drgrpg_skill_effect', true );
				if ( ! empty( $effect ) ) {
					echo esc_html( $effect );
				}
				break;
			case 'strength':
				$strength = get_post_meta( $postId, '_drgrpg_skill_strength', true );
				if ( ! empty( $strength ) ) {
					echo esc_html( number_format( $strength ) );
				} else {
					echo '0';
				}
				break;
			case 'variability':
				$variability = get_post_meta( $postId, '_drgrpg_skill_variability', true );
				if ( ! empty( $variability ) ) {
					echo esc_html( $variability ) . '%';
				} else {
					echo '0%';
				}
				break;
			default :
				break;
		}
	}
}
