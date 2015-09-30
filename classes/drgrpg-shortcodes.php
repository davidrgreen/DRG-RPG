<?php
/**
 * Holds the DRGRPG_Shortcodes class.
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
 * Create the shortcodes for the game.
 *
 * The drgrpg-game-window is the only required shortcode. If you use the
 * other shortcodes it will allow you to customize the layout of the interface,
 * placing the interface components where you position them on the page.
 * Otherwise when the game loads it will automatically insert the
 * missing parts of the interface.
 *
 * @since 0.1.0
 */
class DRGRPG_Shortcodes {

	/**
	 * Run all the add_shortcode methods needed.
	 *
	 * @since 0.1.0
	 * @static
	 * @return void
	 */
	public static function setup_in_wp() {
		$self = new self();
		add_shortcode( 'drgrpg-game-window', array( $self, 'game_window_shortcode' ) );
		add_shortcode( 'drgrpg-player-stats', array( $self, 'player_stats_shortcode' ) );
		add_shortcode( 'drgrpg-main-menu', array( $self, 'main_menu_shortcode' ) );
	}

	/**
	 * The main game window shortcode.
	 *
	 * This is the only required shortcode. It will insert the main interface,
	 * and will trigger the loading of the game's assets.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $atts Attributes passed to the shortcode.
	 * @return string The HTML to display on the page.
	 */
	public function game_window_shortcode( $atts ) {
		// Don't load the game if user is not logged in.
		if ( ! is_user_logged_in() ) {
			// Typically want to keep as much HTML out of the translation as possible,
			// but I'm tired and blanking on a non-convoluted way to do it here.
			// Translators will need to include the a tag, and wp_kses is used to
			// limit the HTML tags able to be used.
			$not_logged_in_message = sprintf(
				wp_kses(
					__( 'Hello. You must <a href="%s">login</a> before you\'re able to play the game.', 'drgrpg' ),
					array(
						'a' => array( 'href' => array() ),
						'strong' => array(),
						'em' => array(),
						'p' => array(),
					)
				),
				esc_url( wp_login_url( get_permalink() ) )
			);

			/**
			 * Filter the message shown to users when they visit a page using the
			 * drgrpg-game-window shortcode while they are not logged in.
			 *
			 * @since 0.1.0
			 * @param string $message The message to be shown.
			 */
			$not_logged_in_message = apply_filters( 'drgrpg_login_required',
				$not_logged_in_message
			);

			return $not_logged_in_message;
		}

		// If no 'autostart' shortcode attribute was used or it wasn't set to false
		// then set it to true so the plugin's JS will automatically run upon loading.
		if ( empty( $atts['autostart'] ) || 'false' !== strtolower( $atts['autostart'] ) ) {
			$atts['autostart'] = true;
		}

		// Load the JS assets. By doing it here the plugin's JS file will only
		// be loaded on pages using the game's shortcode.
		DRGRPG_Assets::load_js( $atts['autostart'] );

		// Output the HTML container elements needed for the main interface.
		return '<div id="drgrpg-notifications" class="drgrpg-notifications"></div><div id="drgrpg-lookingAt" class="drgrpg-lookingAt">Loading game...</div><div id="drgrpg-processingBar" class="drgrpg-processingBar"></div>';
	} // end game_window_shortcode

	/**
	 * Output the HTML container elements needed to display the player
	 * identity and stat info.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string The HTML to display on the page.
	 */
	public function player_stats_shortcode() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		return '<div id="drgrpg-playerInfo" class="drgrpg-playerInfo"><div id="drgrpg-playerIdentity" class="drgrpg-playerIdentity"></div><div id="drgrpg-playerStats" class="drgrpg-playerStats">Loading player stats...</div></div>';
	}

	/**
	 * Output the HTML container elements needed to main
	 * menu of the game.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string The HTML to display on the page.
	 */
	public function main_menu_shortcode() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		return '<div id="drgrpg-mainMenu" class="drgrpg-mainMenu"></div>';
	}
} // end DRGRPG_Shortcodes class
