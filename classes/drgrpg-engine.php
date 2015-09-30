<?php
/**
 * Holds the DRGRPG_Engine class.
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
 * Handle moving through each step of the turn and returning data to the API object.
 *
 * @since 0.1.0
 */
class DRGRPG_Engine {
	/**
	 * Accumulates all the data needed by the browser and is sent back as JSON.
	 * at the end of the turn
	 *
	 * @since 0.1.0
	 * @access private
	 * @var array
	 */
	private  $json_to_return = array();

	/**
	 * The player object for the user playing this turn of the game.
	 *
	 * @since 0.1.0
	 * @access private
	 * @var DRGRPG_Player
	 */
	private $player;

	/**
	 * The combat object used when a round of combat needs to be executed.
	 *
	 * @since 0.1.0
	 * @access private
	 * @var DRGRPG_Combat
	 */
	private $combat;

	/**
	 * The room object for the room the player is in/viewing.
	 *
	 * @since 0.1.0
	 * @access private
	 * @var DRGRPG_Room
	 */
	private $room;

	/**
	 * Flag indicating whether this is the first turn since the browser loaded the game.
	 *
	 * @since 0.1.0
	 * @access private
	 * @var boolean
	 */
	private $first_turn = false;

	/**
	 * Setup the game engine object.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer $player_id  User ID of the player playing this turn of the game.
	 */
	public function __construct( $player_id ) {

		if ( empty( $player_id ) ) {
			die( 'You cannot run the game engine without a valid player.' );
		}

		$this->player = new DRGRPG_Player( $player_id );
	}

	/**
	 * __get magic method. Retrieve the specified property.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param  string $property The name of the property you want the value of.
	 * @return mixed The value of the property.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
	}

	/**
	 * Execute a game turn.
	 *
	 * Step through each phase of the turn, accumulating data needed by the
	 * browser within $json_to_return, and then return $json_to_return at the
	 * end of the turn.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array  The data needed to be sent to the browser as JSON.
	 */
	public function execute_turn() {

		// Player contacted the server too frequently.
		if ( true === $this->is_accessing_too_frequently() ) {

			// Need to save the player's data to the database so it remembers the
			// access time of this request.
			$this->player->save_player();

			// Send the pause command to try and stop the player contacting the
			// server so many times.
			$this->add_to_json( 'pause', 'true' );

			return $this->json_to_return;
		}

		// Execute user actions that were submitted from the browser.
		$this->execute_user_actions();

		// Try to recover player's HP and MP naturally if not in a battle.
		if ( empty( $this->player->saved_battle['info'] ) ) {
			$this->player->heal_naturally();
		}

		 // Execute a turn of combat if one should happen this turn.
		 $this->try_fighting();

		// Save the user's data to the database. Anything that affects the user
		// after this point will be ignored.
		$this->player->save_player();

		// Check and populate $json_to_return with all the data updates
		// needed by the browser.
		$this->get_data_for_browser();

		// Uncomment when you want to save a log of the SQL queries to a log file
		/*$this->log_sql_queries();*/

		// Return all the data generated for the browser during this turn.
		return $this->json_to_return;
	} // end execute_turn


	/**
	 * Get all new achievements the player has earned this turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function get_achievements() {
		$new_achievements = $this->player->get_new_achievements();
		if ( ! empty( $new_achievements ) ) {
			$this->add_to_json( 'newAchievement', $new_achievements );
		}
	}

	/**
	 * Get all notifications generated for the browser this turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function get_all_notifications() {

		// Get all notifications the player object holds from this turn.
		$notifications = $this->player->get_notifications();
		for ( $i = 0, $len = count( $notifications ); $i < $len; $i++ ) {
			$this->add_to_json( 'notifications', $notifications[ $i ] );
		}

		// Get all notifications the room object holds from this turn.
		if ( ! empty( $this->room ) ) {
			$notifications = $this->room->get_notifications();
			for ( $i = 0, $len = count( $notifications ); $i < $len; $i++ ) {
				$this->add_to_json( 'notifications', $notifications[ $i ] );
			}
		}
	}

	/**
	 * Execute a turn of combat if appropriate.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function try_fighting() {

		// Currently in combat and not on the first turn, so execute a combat turn
		// and send the results through add_to_json for the browser.
		if ( false === $this->first_turn &&
		 		! empty( $this->player->saved_battle['enemies'] ) ) {

		 	// Only want one instance of the combat object.
			if ( empty( $this->combat ) ) {
				$this->combat = new DRGRPG_Combat( $this->player );
			}

			 $this->add_to_json( 'combat', $this->combat->execute_battle_turn() );
		}
	}

	/**
	 * Check to see whether the player is trying to access the server too frequently,
	 * such as by opening the game in two tabs.
	 *
	 * @return boolean Is the player accessing too frequently? True for yes, False for no.
	 */
	private function is_accessing_too_frequently() {

		// Player hasn't processed a turn and saved $last_access info before, so pass.
		if ( empty( (int) $this->player->last_access ) ) {
			return false;
		}

		$user_actions = array();

		// Array of actions sent via AJAX.
		if ( ! empty( $_POST['action'] ) ) {
			$user_actions = wp_unslash( $_POST['action'] );
		}

		// Loop through all the actions submitted by player, checking to see if
		// the first-turn command was sent by the browser.
		$is_first_turn = false;
		for ( $i = 0, $len = count( $user_actions ); $i < $len; $i ++ ) {
			if ( 'system' === $user_actions[ $i ][0] &&
					'first-turn' === $user_actions[ $i ][1] ) {
				$is_first_turn = true;
				break;
			}
		}

		// If it has been less than 3 seconds since player last contacted the server
		// and it is not the first turn since the page was loaded in the browser then
		// this turn shouldn't occur. Return true, it is accessing too frequently.
		if ( $this->player->this_access - $this->player->last_access < 3 &&
				false === $is_first_turn ) {
			return true;
		} else {
			return false;
		}
	} // end is_accessing_too_frequently

	/**
	 * Try executing all the actions player submitted this turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function execute_user_actions() {
		$user_actions = [];

		// Array of actions sent via AJAX.
		if ( ! empty( $_POST['action'] ) ) {
			$user_actions = wp_unslash( $_POST['action'] );
		}

		// Loop through all the actions submitted by player.
		for ( $i = 0, $len = count( $user_actions ); $i < $len; $i ++ ) {

			// Starting/stopping combat related actions.
			if ( 'combat' === $user_actions[ $i ][0] ) {
				// Only want one instance of the combat object.
				if ( empty( $this->combat ) ) {
					$this->combat = new DRGRPG_Combat( $this->player );
				}

				// Start a new battle.
				if ( 'hunt' === $user_actions[ $i ][1] ) {
					// Don't hunt after moving or if player is already in a battle.
					if ( $this->player->have_moved ||
						! empty( $this->player->saved_battle['enemies'] ) ) {
						continue;
					}

					if ( empty( $this->room ) ) {
						$this->room = new DRGRPG_Room( $this->player->current_room, $this->player );
					}

					// There is no saved battle. Add enemies and setup combat.
					$new_combat = $this->combat->initiate_new_battle( $this->room );
					if ( false !== $new_combat ) {
						$this->add_to_json( 'new_combat', $new_combat );
					}
				} else if ( 'flee' === $user_actions[ $i ][1] ) {
					$this->add_to_json( 'fleeCombat', $this->combat->flee_battle() );
				}
			} else if ( 'movePlayer' === $user_actions[ $i ][0] ) {
				// Try to move to a different room.
				// Don't move twice in one turn or during combat.
				if ( $this->player->have_moved ||
						! empty( $this->player->saved_battle['enemies'] ) ) {
					continue;
				}

				// Only want one instance of the room object.
				if ( empty( $this->room ) ) {
					$this->room = new DRGRPG_Room( $this->player->current_room, $this->player );
				}

				// Check if the exit player is trying to use exists within the room and
				// the player meets any requirements for using it. $has_exit will
				// either be the ID of the exit if it's a valid exit, or empty if player
				// should not be using the exit.
				$has_exit = $this->room->has_exit( $user_actions[ $i ][1] );

				// The player can enter the exit so move the player.
				if ( ! empty( $has_exit ) ) {
					$this->room = new DRGRPG_Room( $has_exit, $this->player );
					$this->player->have_moved = true;
					$this->player->current_room = $has_exit;
				}
			} else if ( 'roomObjectAction' === $user_actions[ $i ][0] ) {
				// Try to interact with an object in the current room.
				// Only want one room object.
				if ( empty( $this->room ) ) {
					$this->room = new DRGRPG_Room( $this->player->current_room, $this->player );
				}

				// Try processing the action.
				$this->room->process_object_action( $user_actions[ $i ][1] );
			} else if ( 'equip_item' === $user_actions[ $i ][0] ) {
				// Try equipping an item currently in the player's inventory.
				$this->player->equip_item( $user_actions[ $i ][1] );
			} else if ( 'unequip_item' === $user_actions[ $i ][0] ) {
				// Try unequipping an item player currently has equipped.
				$this->player->unequip_item( $user_actions[ $i ][1] );
			} else if ( 'drop_item' === $user_actions[ $i ][0] ) {
				// Try dropping / deleting an item from player's inventory.
				$this->player->drop_item( $user_actions[ $i ][1] );
			} else if ( 'use_skill' === $user_actions[ $i ][0] ) {
				// Try using a skill.
				$this->player->use_skill( $user_actions[ $i ][1] );
			} else if ( 'system' === $user_actions[ $i ][0] ) {
				// Commands the system itself chooses to send.

				// This is the first turn since the game page was loaded, so perform
				// certain actions only necessary on the first turn.
				if ( 'first-turn' === $user_actions[ $i ][1] ) {
					$this->setup_first_turn();
				}
			}
		}
	} // end execute_user_actions

	/**
	 * Get the data the browser needs to display the game interface.
	 *
	 * The game engine utilizes several flags to determine whether player data
	 * has been changed during the course of a turn. This allows it to send only
	 * the pieces of data needed to update the interface instead of performing a
	 * full refresh of the data.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function get_data_for_browser() {

		// Player stats have changed in some way, so send updated data.
		if ( $this->player->have_stats_changed ) {
			$this->add_to_json( 'playerStats', $this->player->get_player_stats() );
		}

		// Player has learned a new skill or increased a skill level, so send updated data.
		if ( $this->player->have_skills_changed ) {
			$this->add_to_json( 'playerSkills', $this->player->get_skills() );
		}

		// Player has gotten a new item, dropped an item, equipped an item, or
		// unequipped an item, so send updated data.
		if ( $this->player->have_items_changed ) {
			$inventory_for_browser = $this->player->get_inventory();

			// Player's inventory isn't empty, so send the inventory data.
			if ( ! empty( $inventory_for_browser ) ) {
				$this->add_to_json( 'playerInventory', $inventory_for_browser );
			} else if ( false === $this->first_turn ) {
				// Player's inventory is empty but previously was not, so send
				// a clearInventory command to the browser to clean up the
				// previous inventory data and avoid JavaScript errors.
				$this->add_to_json( 'clearInventory', 'x' );
			}

			// Send the player's equipment to the browser.
			$equipment_for_browser = $this->player->get_equipped_items();
			if ( ! empty( $equipment_for_browser ) ) {
				$this->add_to_json( 'playerEquipment', $equipment_for_browser );
			}
		}

		// Player moved or the game determined an action occurred that will likely
		// cause the displayed objects or exits in the room to change, so send updated
		// room data to be on the safe side.
		if ( $this->player->have_moved ) {
			if ( empty( $this->room ) ) {
				$this->room = new DRGRPG_Room( $this->player->current_room, $this->player );
			}
			$this->add_to_json( 'room', $this->room->get_room_data_as_array() );
		}

		// The player has not moved, but a quest flag was changed so send
		// updated data.
		// This is sent tagged as roomUpdate instead of room so the browser can avoid
		// leaving a NPC conversation or stop examining something merely because
		// a quest flag changed. Player needs the chance to read the message displayed
		// by whatever action caused the quest flag to change.
		if ( ! $this->player->have_moved && $this->player->have_quest_flags_changed ) {
			if ( empty( $this->room ) ) {
				$this->room = new DRGRPG_Room( $this->player->current_room, $this->player );
			}
			$this->add_to_json( 'roomUpdate', $this->room->get_room_data_as_array() );
		}

		// Player has changed their guild membership or gained a guild level, so
		// send updated data.
		if ( $this->player->have_guild_changed ) {
			$this->add_to_json( 'updatePlayerGuild', [
				'name' => $this->player->current_guild,
				'level' => ! empty( $this->player->guild_levels[ $this->player->current_guild ] ) ?
					$this->player->guild_levels[ $this->player->current_guild ][1] : null,
			] );
		}

		// Get all notifications to display in the browser that have been generated
		// this turn.
		$this->get_all_notifications();

		// Get all achievements the player has newly earned this turn.
		$this->get_achievements();
	} // end get_data_for_browser

	/**
	 * Perform actions needed on the first turn or the game.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function setup_first_turn() {
		$this->first_turn = true;

		// Send the player name and avatar image.
		// TODO: Perhaps store the gravatar in a transient.
		$this->add_to_json( 'playerIdentity', [
			'name' => $this->player->name,
			'avatar' => get_avatar( $this->player->id, 64, null,
				$this->player->name . '\'s avatar', [
					'class' => 'drgrpg-playerAvatar',
				] ),
			] );

		// This is the first turn so we'll need these flags set to send all the
		// pertinent info to the browser so all the screens are prepopulated.
		$this->player->have_guild_changed = true;
		$this->player->have_healed = true;
		$this->player->have_items_changed = true;
		$this->player->have_moved = true;
		$this->player->have_skills_changed = true;
		$this->player->have_stats_changed = true;

		// If the player has a saved battle, meaning they previously closed or refreshed
		// the game while in the middle of combat, send the combat data to the browser
		// but also send a pause command so the player is not launched into battle
		// before they have a chance to realize their situation.
		if ( ! empty( $this->player->saved_battle['enemies'] ) ) {

			// Only want one instance of the combat object.
			if ( empty( $this->combat ) ) {
				$this->combat = new DRGRPG_Combat( $this->player );
			}

			 // Send the combat data.
			$this->add_to_json( 'new_combat', $this->combat->initiate_existing_battle() );

			// Send the pause command.
			$this->add_to_json( 'pause', 'true' );
		}

		// Only want to send the full list of achievements on the first turn. After this,
		// when a player earns a new achievement that specific achievement's data
		// will be sent to avoid resending the full list.
		$this->add_to_json( 'achievements', $this->player->get_all_achievements() );
	}

	/**
	 * Add data to be sent back to the browser as JSON at the end of the turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string $key  Used for grouping similar message types.
	 * @param string $data The data the browser will be using.
	 * @return void
	 */
	private function add_to_json( $key, $data ) {
		if ( empty( $key ) || empty( $data ) ) {
			return;
		}
		$this->json_to_return[ $key ][] = $data;
	}

	/**
	 * Add data to be sent back to the browser as JSON at the end of the turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function log_sql_queries() {
		global $wpdb;
		file_put_contents( 'logs/queries.txt', serialize( $wpdb->queries ) );
	}
} // end of DRGRPG_Engine class
