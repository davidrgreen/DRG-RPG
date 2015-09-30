<?php
/**
 * Holds the DRGRPG_Assets class.
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
 * Handle registering and enqueue all the assets (CSS and JS) needed by the game.
 *
 * @since 0.1.0
 */
class DRGRPG_Assets {

	/**
	 * Hook in the register_assets function.
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

		add_action( 'wp_enqueue_scripts', array( $self, 'register_assets' ) );

		// Only going to enqueue the CSS if on a page where the main shortcode
		// is being used.
		add_action( 'wp_enqueue_scripts', array( $self, 'maybe_load_css' ) );
	}

	/**
	 * Register the plugin's assets.
	 *
	 * Register the plugin's assets, but don't shortcut the process by
	 * enqueuing and registering in one step. This makes it easier to
	 * unregister assets (in theory).
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function register_assets() {

		// Don't load minified versions when SCRIPT_DEBUG enabled.
		$min_or_not = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'drgrpg-script', plugins_url() . '/drgrpg/assets/js/drgrpg' .
			$min_or_not . '.js', array( 'jquery' ), '0.1.0', true
		);
		wp_register_style( 'drgrpg-style', plugins_url() . '/drgrpg/assets/css/drgrpg' .
			$min_or_not . '.css', array(), '0.1.0'
		);
	}

	/**
	 * Localize and enqueue the plugin's JS file.
	 *
	 * This is called from within DRGRPG_Shortcodes::game_window_shortcode().
	 * It isn't called along with the CSS because load_js() needs to accept an optional
	 * argument determining whether to automatically run the DRGRPG.init() function.
	 *
	 * @since 0.1.0
	 * @access public
	 * @static
	 * @param boolean $autostart Whether to automatically run DRGRPG.init() in the JS.
	 * @return void
	 */
	public static function load_js( $autostart = true ) {

		// Only do this on the front end of the site.
		if ( ! is_admin() ) {

			// An array of data to be sent to the browser for use by the JS file.
			$infoFromServer = array(
				// URL of the site so the script knows where to make its AJAX requests.
				'siteURL' => get_site_url(),

				// Create a nonce for the AJAX to send back during requests
				// in order to confirm the requests are valid.
				'nonce' => wp_create_nonce( 'drgrpg_ajax_turn' ),
				'autostart' => $autostart,
			);

			wp_localize_script( 'drgrpg-script', 'infoFromServer', $infoFromServer );
			wp_enqueue_script( 'drgrpg-script' );
		}
	}

	/**
	 * Load the CSS if on a page where the game shortcode is being used.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function maybe_load_css() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( has_shortcode( $post->post_content, 'drgrpg-game-window' ) ) {
			wp_enqueue_style( 'drgrpg-style' );
		}
	}
}
