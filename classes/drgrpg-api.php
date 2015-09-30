<?php
/**
 * Holds the DRGRPG_API class.
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
 * Establishes rewrite rules and directs game actions if they the pass security checks.
 *
 * @since 0.1.0
 */
class DRGRPG_API {
	/**
	 * Holds the add_actions for interacting with the rewrite API.
	 *
	 * @since 0.1.0
	 * @access public
	 * @static
	 * @return void
	 */
	public static function establish_api() {
		$self = new self();
		add_action( 'init', array( $self, 'add_rewrites' ) );
		add_action( 'template_redirect', array( $self, 'do_api' ) );
	}

	/**
	 * Add new rewrite rules so the URL structure for the game API is recognized.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function add_rewrites() {
		add_rewrite_tag( '%game_action%', 'turn' );
		add_rewrite_rule( '^game/api/([^&]+)/?', 'index.php?game_action=$matches[1]', 'top' );
	}

	/**
	 * Add new rewrite rules so the URL structure for the game API is recognized.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function do_api() {
		global $wp_query;

		// Get the game_action from the URL,  site.com/game/turn/{game_action}
		// Works because of adding the rewrite_tag in add_rewrites.
		$game_action = $wp_query->get( 'game_action' );

		// Attempt to execute a turn.
		if ( 'turn' === $game_action ) {
			// Game turns should only occur for logged in users.
			if ( 0 === get_current_user_id() ) {
				die( 'This only works for logged in users.' );
			}

			// Fail if request doesn't hold the correct nonce.
			if ( ! check_ajax_referer( 'drgrpg_ajax_turn', 'security' ) ) {
				die( 'This is an invalid request. If in the game, please refresh the page.' );
			}

			// NOTE: Not sure this filter is actually working. Will investigate later
			// in phase 2 when I go analyze the sql queries being made. In theory
			// it's optimizing the sql queries ran during the course of this turn.
			add_filter( 'pre_get_posts', array( $this, 'optimized_get_posts' ), 100 );

			// Create an instance of the game engine.
			$game_engine = new DRGRPG_Engine( get_current_user_id() );

			// Just to be on the safe side, confirm the game engine was able to load
			// the user's data and populate a player object. If not then die.
			if ( ! $game_engine->player ) {
				die( 'That user does not exist.' );
			}
			// And execute the turn, saving the array of data for the browser it returns.
			$to_return = $game_engine->execute_turn();

			// Encode the results of the game turn as JSON and send it to the browser.
			wp_send_json( $to_return );
		} // end if game_action === 'turn'
	} // end do_api

	/**
	 * Eliminate unneeded queries in order to optimize performance
	 *
	 * @since 0.1.0
	 * @access public
	 * @return WP_Query Reference to the WP_Query object.
	 */
	public function optimized_get_posts() {
		global $wp_query;
		$wp_query->query_vars['no_found_rows'] = 1;
		$wp_query->query_vars['update_post_term_cache'] = 0;
		return $wp_query;
	}
} // end DRGRPG_API class
