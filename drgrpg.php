<?php
/**
 * DRG RPG
 *
 * DRG RPG plugin for WordPress allows you to create a RPG
 * through the WordPress admin using CPTs and metaboxes,
 * and then embed it into your site to play with only a single
 * shortcode.
 *
 * NOTE: Take note this is largely a proof of concept currently and
 * will likely have large, non-backwards-compatible changes
 * made between these early versions.
 *
 * @package DRGRPG
 * @author David Green <david@davidrg.com>
 * @license GPL2
 * @link https://github.com/davidrgreen/DRGRPG
 * @copyright 2015 David Green
 * @since 0.1.0
 *
 * Plugin Name: DRG RPG
 * Description: Use WordPress to make a roleplaying game.
 * Version: 0.1.0
 * Author: David Green
 * Author URI:  http://davidrg.com
 * Text Domain: drgrpg
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Autoload the classes used by this plugin.
spl_autoload_register( function( $class ) {

	// Autoload only this plugin's classes.
	if ( 0 !== strpos( $class, 'DRGRPG' ) ) {
		return;
	}

	// Class names are DRGRPG_Monster but class files are drgrpg-monster.php,
	// so this converts the class name to the filename format.
	$class = str_replace( '_', '-', strtolower( $class ) );
	require_once( plugin_dir_path( __FILE__ ) . "classes/{$class}.php" );
} );

// Register the activation hook.
register_activation_hook( __FILE__, 'drgrpg_activation' );

// Simply flush the rewrite rules on deactivation.
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// Include the dependencies or deactivate the plugin and notify the user.
if ( ! drgrpg_maybe_include_dependencies() ) {
	return false;
}

// Setup all the CPTs and such needed by the plugin.
drgrpg_initiate_all();

/**
 * Activation method run by the activation hook.
 *
 * This confirms the plugin has the dependencies it requires,
 * and if it does then it proceeds with activating the CPTs and
 * flushing rewrite rules.
 *
 * @since 0.1.0
 * @return void
 */
function drgrpg_activation() {

	// Check if the plugin has the required dependencies, and include them
	// if it does. If it doesn't then fail.
	if ( ! drgrpg_maybe_include_dependencies() ) {
		return;
	}

	// Activate all the CPTs and such.
	drgrpg_initiate_all();

	// Flushing rewrites at this point doesn't work. Have to use the init hook
	// to make sure the rewrite happens after the CPTs are setup.
	add_action( 'init', 'flush_rewrite_rules' );
}

/**
 * Call all the methods needed to make WordPress aware of the
 * CPTs and such the plugin needs.
 *
 * @since 0.1.0
 * @return void
 */
function drgrpg_initiate_all() {
	DRGRPG_API::establish_api();
	DRGRPG_Achievement_Admin::setup_in_wp();
	DRGRPG_Assets::setup_in_wp();
	DRGRPG_Guild_Admin::setup_in_wp();
	// Content not yet written for help tabs so temporarily removed.
	/*DRGRPG_Help_Tabs::setup_in_wp();*/
	DRGRPG_Item_Admin::setup_in_wp();
	DRGRPG_Monster_Admin::setup_in_wp();
	DRGRPG_Room_Admin::setup_in_wp();
	DRGRPG_Shortcodes::setup_in_wp();
	DRGRPG_Skill_Admin::setup_in_wp();
}

/**
 * Try to include the plugin's dependencies. If any dependencies are
 * not found then make sure the plugin cannot be activated, it's
 * deactivated if it was already activated, and notify the user.
 *
 * @since 0.1.0
 * @return bool True if the everything went smoothly. False otherwise.
 */
function drgrpg_maybe_include_dependencies() {

	// True or False, based on whether it has the required dependencies.
	$has_dependencies = drgrpg_include_dependencies();

	// Cancel activation if the plugin doesn't have the required dependencies.
	if ( ! $has_dependencies ) {

		// Prevent the Plugin Activated message from appearing.
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		// Deactivate the plugin.
		add_action( 'admin_init', 'drgrpg_deactivate_plugin' );

		 // Send a notice to the user.
		 add_action( 'admin_notices', 'drgrpg_missing_cmb2' );

		return false;
	}

	// Return true if the dependencies were found.
	return true;
}

/**
 * Include the plugin's dependencies, or return false if the dependencies
 * are not found.
 *
 * @since 0.1.0
 * @return bool True if the dependencies were all found. False if not.
 */
function drgrpg_include_dependencies() {

	// Avoid running plugin_dir_path() repeatedly through this function.
	$drgrpg_plugin_path = plugin_dir_path( __FILE__ );

	// Try to include CMB2. Plugin should have included
	// https://github.com/WebDevStudios/CMB2 as a git submodule.
	if ( file_exists( $drgrpg_plugin_path . 'includes/CMB2/init.php' ) ) {
		require_once $drgrpg_plugin_path . 'includes/CMB2/init.php';
	} else {
		return false;
	}

	// Try to include CMB2 Attached Posts Field. Plugin should have included
	// https://github.com/WebDevStudios/cmb2-attached-posts as a git submodule.
	if ( file_exists( $drgrpg_plugin_path . 'includes/cmb2-attached-posts/cmb2-attached-posts-field.php' ) ) {
		require_once $drgrpg_plugin_path . 'includes/cmb2-attached-posts/cmb2-attached-posts-field.php';
	} else {
		return false;
	}

	// Try to include CMB2 Post Search Field. Plugin should have included
	// https://github.com/WebDevStudios/CMB2-Post-Search-field as a
	// git submodule.
	if ( file_exists( $drgrpg_plugin_path . 'includes/CMB2-post-search-field/cmb2_post_search_field.php' ) ) {
		require_once $drgrpg_plugin_path . 'includes/CMB2-post-search-field/cmb2_post_search_field.php';
	} else {
		return false;
	}

	// All dependencies were found by this point, so return true.
	return true;
}

/**
 * Deactivate the plugin.
 *
 * This is used when the plugin doesn't pass the check for dependencies.
 *
 * @since 0.1.0
 * @return void
 */
function drgrpg_deactivate_plugin() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

/**
 * Add an error notice to the dashboard if CMB2 is missing from the plugin.
 *
 * @since 0.1.0
 * @return void
 */
function drgrpg_missing_cmb2() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'DRGRPG cannot find its CMB2 dependencies! These are included as submodules, so please make sure you have run the following: git submodule init', 'drgrpg' ); ?></p>
	</div>
	<?php
}
