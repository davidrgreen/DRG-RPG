<?php
/**
 * Holds the DRGRPG_Room_Admin class.
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
 * Class holds all the code needed to make WordPress aware of the drgrpg_room CPT
 * and customize its appearance on the admin side.
 *
 * @since 0.1.0
 */
class DRGRPG_Room_Admin {

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
		add_filter( 'manage_edit-drgrpg_room_columns', array( $self, 'alter_admin_columns' ) );
		add_filter( 'manage_drgrpg_room_posts_custom_column', array( $self, 'alter_admin_columns_content' ), 10, 2 );
	}

	/**
	 * Register the drgrpg_room CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_cpt() {
		$labels = array(
			'name' => _x( 'Rooms', 'Post Type General Name', 'drgrpg' ),
			'singular_name' => _x( 'Room', 'Post Type Singular Name', 'drgrpg' ),
			'add_new' => __( 'Add New Room', 'drgrpg' ),
			'add_new_item' => __( 'Add New Room', 'drgrpg' ),
			'edit_item' => __( 'Edit Room', 'drgrpg' ),
			'new_item' => __( 'New Room', 'drgrpg' ),
			'view_item' => __( 'View Room', 'drgrpg' ),
			'search_items' => __( 'Search Rooms', 'drgrpg' ),
			'not_found' => __( 'No Rooms found', 'drgrpg' ),
			'not_found_in_trash' => __( 'No Rooms found in Trash', 'drgrpg' ),
			'parent_item_colon' => __( 'Parent Room:', 'drgrpg' ),
			'menu_name' => __( 'Rooms', 'drgrpg' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => false,
			'description' => __( 'Locations a player can visit', 'drgrpg' ),
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

		register_post_type( 'drgrpg_room', $args );
	} // end establish_cpt

	/**
	 * Create the custom meta boxes for the CPT using CMB2.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function establish_metaboxes() {
		$prefix = '_drgrpg_room_';

		$room_cmb = new_cmb2_box( array(
			'id' => $prefix . 'metabox',
			'title' => __( 'Room Info', 'drgrpg' ),
			'object_types' => array( 'drgrpg_room' ),
		) );

		$room_cmb->add_field( array(
			'name' => __( 'Room Description', 'drgrpg' ),
			'desc' => __( 'This is what the player sees when first entering a room.', 'drgrpg' ),
			'id' => $prefix . 'description',
			'type' => 'wysiwyg',
			'options' => array(),
		) );

		$room_cmb->add_field( array(
			'name' => __( 'Environment Type', 'drgrpg' ),
			'desc' => __( 'Select what type of environment this room represents.', 'drgrpg' ),
			'id' => $prefix . 'environment',
			'type' => 'select',
			'show_option_none'	 => false,
			'default' => 'field',
			'options' => array(
				'cave' => __( 'Cave', 'drgrpg' ),
				'city' => __( 'City', 'drgrpg' ),
				'field' => __( 'Field', 'drgrpg' ),
				'forest' => __( 'Forest', 'drgrpg' ),
				'indoors' => __( 'Indoors', 'drgrpg' ),
				'sky' => __( 'Sky', 'drgrpg' ),
				'snow' => __( 'Snow', 'drgrpg' ),
				'space' => __( 'Space', 'drgrpg' ),
				'underwater' => __( 'Underwater', 'drgrpg' ),
			),
		) );

		// Begin repeatable group field for Room Objects.
		$objects_group_id = $room_cmb->add_field( array(
			'id' => $prefix . 'objects',
			'type' => 'group',
			'description' => __( 'Create objects within the room to be examined', 'drgrpg' ),
			'options' => array(
				'group_title' => __( 'Object #{#}', 'drgrpg' ),
				'add_button' => __( 'Add Another Object to the Room', 'drgrpg' ),
				'remove_button' => __( 'Remove Object from Room', 'drgrpg' ),
				'sortable' => true,
			),
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name'	=> __( 'Name', 'drgrpg' ),
			'id'		=> 'name',
			'type'	=> 'text',
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Object Group', 'drgrpg' ),
			'desc' => __( 'Select what part of the page this object will be grouped within when the room is displayed.', 'drgrpg' ),
			'id' => 'object_group',
			'type' => 'select',
			'show_option_none'	 => false,
			'options' => array(
				'examine' => __( 'Examine', 'drgrpg' ),
				'action' => __( 'Take Action', 'drgrpg' ),
				'npc' => __( 'Talk to NPC', 'drgrpg' ),
			),
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Description', 'drgrpg' ),
			'description' => __( 'What will be shown when this object is examined.', 'drgrpg' ),
			'id' => 'description',
			'type' => 'wysiwyg',
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Actions (optional)', 'drgrpg' ),
			'desc' => __( 'The below settings allow this object to perform an action that will affect the player in addition to the object description being shown.', 'drgrpg' ),
			'type' => 'title',
			'id' => 'objects_title',
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Type of Action', 'drgrpg' ),
			'desc' => __( 'Select what type of action this object can take.', 'drgrpg' ),
			'id' => 'action_type',
			'type' => 'select',
			'show_option_none'	 => true,
			'options' => array(
				'award_achievement' => __( 'Award Achievement', 'drgrpg' ),
				'set_quest_flag' => __( 'Set Quest Flag', 'drgrpg' ),
				'damage_player' => __( 'Damage Player', 'drgrpg' ),
				'sell_to_player' => __( 'Sell Item to Player', 'drgrpg' ),
				'give_item' => __( 'Give Item', 'drgrpg' ),
				'join_guild' => __( 'Join Guild', 'drgrpg' ),
				'leave_guild' => __( 'Leave Guild', 'drgrpg' ),
				'increase_guild_level' => __( 'Increase Guild Level', 'drgrpg' ),
				'teach_skill' => __( 'Teach Skill', 'drgrpg' ),
				'increaseSkill' => __( 'Increase Skill Level', 'drgrpg' ),
				'multiple' => __( 'Multiple', 'drgrpg' ),
			),
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Value of Action', 'drgrpg' ),
			'desc' => __( '<br>The value of the action. This is different depending on what type of action you select.<br>For "Damage Player" give this a number value.<br>For "Set Quest Flag" use the formula flagName=value, where value must be a number. So to set the apples flag to a value of 1 you would enter apples=1', 'drgrpg' ),
			'id' => 'action_value',
			'type' => 'text',
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Requirements', 'drgrpg' ),
			'desc' => __( 'The below settings are completely optional. However, if you set a requirement and the player does not meet it then the object will not be shown to them.', 'drgrpg' ),
			'type' => 'title',
			'id' => 'requirements_title',
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Type of Requirement', 'drgrpg' ),
			'desc' => __( 'If you want this exit to be hidden unless the player select meets a certain requirement then select the type of requirement here and provide a value for the requirement in the next field.', 'drgrpg' ),
			'id' => 'requirement_type',
			'type' => 'select',
			'show_option_none'	 => true,
			'options' => array(
				'has_quest_flag' => __( 'Has Quest Flag', 'drgrpg' ),
				'has_guild_level'	 => __( 'Has Guild Level', 'drgrpg' ),
				'in_guild' => __( 'Currently In Guild', 'drgrpg' ),
				'has_skill' => __( 'Has Skill Level', 'drgrpg' ),
				'has_item' => __( 'Has Item', 'drgrpg' ),
			),
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Required Value', 'drgrpg' ),
			'description' => __( 'The value the requirement chosen will use. Consult the Help tab at the top of the screen for more info.', 'drgrpg' ),
			'id' => 'requirement_value',
			'type' => 'text_medium',
		) );

		$room_cmb->add_group_field( $objects_group_id, array(
			'name' => __( 'Type of Matching', 'drgrpg' ),
			'desc' => __( 'When checking quest flags and levels, do you want to check for an exact value, or pass the requirement as long as the quest flag or level is equal to or greater than the value you specified?', 'drgrpg' ),
			'id' => 'requirement_match_type',
			'type' => 'select',
			'show_option_none'	=> false,
			'default' => 'field',
			'options' => array(
				'exact' => __( 'Exact Match', 'drgrpg' ),
				'minimum' => __( 'At Least', 'drgrpg' ),
			),
		) );
		// End repeatable group field for Room Objects.

		// Begin repeatable group field for exits.
		$exits_group_id = $room_cmb->add_field( array(
			'id' => $prefix . 'exits',
			'type' => 'group',
			'description' => __( 'Create exits to other rooms', 'drgrpg' ),
			'options' => array(
				'group_title' => __( 'Exit #{#}', 'drgrpg' ),
				'add_button' => __( 'Add Another Exit', 'drgrpg' ),
				'remove_button' => __( 'Remove Exit', 'drgrpg' ),
				'sortable' => true,
			),
		) );

		$room_cmb->add_group_field( $exits_group_id, array(
			'name' => __( 'Text Shown', 'drgrpg' ),
			'description' => __( 'The link text shown for the exit.', 'drgrpg' ),
			'id' => 'link',
			'type' => 'text',
		) );

		$room_cmb->add_group_field( $exits_group_id, array(
			'name' => __( 'Room', 'drgrpg' ),
			'description' => __( 'The room this exit leads to.', 'drgrpg' ),
			'id' => 'room_id',
			'type' => 'post_search_text',
			'post_type' => 'drgrpg_room',
			'select_type' => 'radio',
			'select_behavior' => 'replace',
		) );

		$room_cmb->add_group_field( $exits_group_id, array(
			'name' => __( 'Requirements (optional)', 'drgrpg' ),
			'desc' => __( 'The below settings are completely optional. However, if you set a requirement and the player does not meet it then the exit will not be shown to them.', 'drgrpg' ),
			'type' => 'title',
			'id' => 'requirements_title',
		) );

		$room_cmb->add_group_field( $exits_group_id, array(
			'name' => __( 'Type of Requirement', 'drgrpg' ),
			'desc' => __( 'If you want this exit to be hidden unless the player select meets a certain requirement then select the type of requirement here and provide a value for the requirement in the next field.', 'drgrpg' ),
			'id' => 'requirement_type',
			'type' => 'select',
			'show_option_none'	=> true,
			'options' => array(
				'has_quest_flag' => __( 'Has Quest Flag', 'drgrpg' ),
				'has_guild_level' => __( 'Has Guild Level', 'drgrpg' ),
				'in_guild' => __( 'Currently In Guild', 'drgrpg' ),
				'has_skill' => __( 'Has Skill Level', 'drgrpg' ),
				'has_item' => __( 'Has Item', 'drgrpg' ),
			),
		) );

		$room_cmb->add_group_field( $exits_group_id, array(
			'name' => __( 'Required Value', 'drgrpg' ),
			'description' => __( 'The value the requirement chosen will use. Consult the Help tab at the top of the screen for more info.', 'drgrpg' ),
			'id' => 'requirement_value',
			'type' => 'text_medium',
		) );

		$room_cmb->add_group_field( $exits_group_id, array(
			'name' => __( 'Type of Matching', 'drgrpg' ),
			'desc' => __( 'When checking quest flags and levels, do you want to check for an exact value, or pass the requirement as long as the quest flag or level is equal to or greater than the value you specified?', 'drgrpg' ),
			'id' => 'requirement_match_type',
			'type' => 'select',
			'show_option_none'	=> false,
			'default' => 'exact',
			'options' => array(
				'exact' => __( 'Exact Match', 'drgrpg' ),
				'minimum' => __( 'At Least', 'drgrpg' ),
			),
		) );
		// End repeatable group field for exits.

		$room_cmb->add_field( array(
			'name' => __( 'Max Number of Monsters Per Battle', 'drgrpg' ),
			'desc' => __( 'The maximum number of monsters a player can encounter at one time.', 'drgrpg' ),
			'id' => $prefix . 'max_monsters',
			'type' => 'select',
			'default' => '0',
			'show_option_none'	 => false,
			'options' => array(
				'0' => number_format_i18n( 0 ),
				'1' => number_format_i18n( 1 ),
				'2' => number_format_i18n( 2 ),
				'3' => number_format_i18n( 3 ),
				'4' => number_format_i18n( 4 ),
				'5' => number_format_i18n( 5 ),
				'6' => number_format_i18n( 6 ),
			),
		) );

		$room_cmb->add_field( array(
			'name' => __( 'Monsters', 'drgrpg' ),
			'desc' => __( 'Drag monsters from the left column to the right column to make them able to be encountered when hunting in this room.', 'drgrpg' ),
			'id' => $prefix . 'monsters',
			'type' => 'custom_attached_posts',
			'options' => array(
				'show_thumbnails' => false,
				'filter_boxes' => true,
				'query_args' => array(
					'post_type' => 'drgrpg_monster',
					// Optimize the query with the below options.
					'no_found_rows' => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				),
			),
		) );
	} // end establlish_metaboxes

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
		if ( 'drgrpg_room' === get_current_screen()->post_type ) {
			return 'Enter name of room';
		} else {
			return $title;
		}
	}

	/**
	 * Change the columns shown on the main Room CPT page of WP Admin.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $columns The columns shown by default, passed in by filter.
	 * @return array The new array of column titles to use.
	 */
	public function alter_admin_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Name', 'drgrpg' ),
			'id' => __( 'ID', 'drgrpg' ),
			'monsters' => __( 'Monsters', 'drgrpg' ),
			'exits' => __( '# of Exits', 'drgrpg' ),
			'objects' => __( '# of Object', 'drgrpg' ),
		);

		return $columns;
	}

	/**
	 * Populate the custom columns added by alter_admin_columns for
	 * the Room CPT.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $column Column name, provided by filter.
	 * @param integer $postId The ID of the current post
	 * @return void
	 */
	public function alter_admin_columns_content( $column, $postId ) {
		global $post;

		switch ( $column ) {
			case 'id':
				echo get_the_ID();
				break;
			case 'monsters':
				$monsters = get_post_meta( $postId, '_drgrpg_room_monsters', true );
				if ( ! empty( $monsters ) ) {
					echo esc_html( implode( ', ', $monsters ) );
				}
				break;
			case 'exits':
				$exits = get_post_meta( $postId, '_drgrpg_room_exits', true );
				if ( ! empty( $exits ) ) {
					echo count( $exits );
				}
				break;
			case 'objects':
				$objects = get_post_meta( $postId, '_drgrpg_room_objects', true );
				if ( ! empty( $objects ) ) {
					echo count( $objects );
				}
				break;
			default :
				break;
		}
	}
} // end DRGRPG_Room_Admin class
