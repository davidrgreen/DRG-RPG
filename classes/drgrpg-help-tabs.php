<?php
/**
 * Holds the DRGRPG_Help_Tabs class.
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
 * Class holds all the code needed to add help tabs to the game's custom CPTs.
 *
 * @since  0.1.0
 */
class DRGRPG_Help_Tabs {

	/**
	 * Run all the add_actions and add_filters needed to hook into WordPress
	 * and make it aware of the help tabs being added to the game's CPTs.
	 *
	 * @since 0.1.0
	 * @access public
	 * @static
	 * @return void
	 */
	public static function setup_in_wp() {
		$self = new self();
		add_action( 'admin_head', array( $self, 'add_help_tabs' ) );
	}

	/**
	 * Add the help tabs to the game's CPTs.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function add_help_tabs() {
		global $post_ID;
		$screen = get_current_screen();

		if ( isset( $_GET['post_type'] ) ) {
			$post_type = wp_unslash( (string) $_GET['post_type'] );
		} else {
			$post_type = get_post_type( $post_ID );
		}

		// Add the help tabs for the drgrpg_room CPT.
		if ( 'drgrpg_room' === $post_type ) {
			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-overview',
				'title'    => __( 'Room Overview' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));

			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-environment',
				'title'    => __( 'Environment' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));

			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-objects',
				'title'    => __( 'Objects Overview' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));

			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-objects-actions',
				'title'    => __( 'Object Actions' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));

			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-objects-requirements',
				'title'    => __( 'Object Requirements' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));

			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-exits-overview',
				'title'    => __( 'Exits Overview' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));

			$screen->add_help_tab( array(
				'id'       => 'drgrpg-room-exits-requirements',
				'title'    => __( 'Exit Requirements' ),
				'content'  => 'This is where I would provide tabbed help to the user on how everything in my admin panel works. Formatted HTML works fine in here too',
			));
		} // end if for adding the help tabs for the drgrpg_room CPT
	} // end add_help
}
