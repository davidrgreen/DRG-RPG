<?php
/**
 * Holds the DRGRPG_Combat class.
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
 * Responsible for executing combat.
 *
 * @since 0.1.0
 */
class DRGRPG_Combat {

	/**
	 * Flag telling the engine whether a new battle was begun this turn.
	 *
	 * @since 0.1.0
	 * @access public
	 * @var boolean
	 */
	public $new_combat = false;

	/**
	 * Which round of combat is taking place.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $round = 1;

	/**
	 * The player object being used in combat.
	 *
	 * This is passed in by reference to the constructor.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var DRGRPG_Player
	 */
	protected $player;

	/**
	 * Array of enemy objects.
	 *
	 * Each value is a DRGRPG_Monster object.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $enemies = array();

	/**
	 * Data to return to the browser describing the results of this turn.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $json_to_return = array();

	/**
	 * The array index of the enemy in $enemies being attacked this turn.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $targeting = 0;

	/**
	 * Setup a new Combat object. If player is in the middle of a battle then
	 * load the data into the combat object.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param DRGRPG_Player $player_object The player participating in combat.
	 */
	public function __construct( DRGRPG_Player $player_object ) {
		$this->player = $player_object;

		// If a battle was saved in user meta, continue that battle.
		if ( ! empty( $this->player->saved_battle['info'] ) ) {
			$this->continue_from_saved_battle();
		}
	}

	/**
	 * Setup a new battle with monsters in the current room.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param  DRGRPG_Room $room The room the player is currently in.
	 * @return array  Combat data to send to the browser.
	 */
	public function initiate_new_battle( $room ) {

		// Room doesn't have any monsters assigned to it or the room is set
		// to have a maximum of 0 monsters in a battle, so fail.
		if ( 0 === count( $room->monsters ) || 0 === $room->max_monsters ) {
			return false;
		}

		// Generate a random monster objects and assign them to the
		// $enemies array. The maximum number is determined by the room.
		$this->add_enemies(
			$room->get_random_monsters( mt_rand( 1, $room->max_monsters ) )
		);
		$this->new_combat = true;
		$this->save_battle_to_user();

		// Store the data describing the new battle in $json_to_return.
		$this->store_battle_data_for_browser();

		return $this->json_to_return;
	} // end initiate_new_battle

	/**
	 * Get the data representing a saved battle and send it the browser.
	 *
	 * This is used on the first turn of the game if the engine discovers an
	 * existing battle when the player loaded the game.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array  Data describing the current battle.
	 */
	public function initiate_existing_battle() {
		$this->store_battle_data_for_browser();
		return $this->json_to_return;
	}

	/**
	 * Add an array of enemies to the existing $enemies array.
	 *
	 * This is typically used when setting up a new battle, but can be utilized
	 * to add enemies to the battle mid-battle such as in the case of a monster
	 * calling for reinforcements.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $new_enemies  An array of DRGRPG_Monster objects.
	 */
	public function add_enemies( $new_enemies ) {
		$this->enemies = array_merge( $this->enemies, $new_enemies );
	}

	/**
	 * Continue a saved battle
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function continue_from_saved_battle() {
		// Increase the combat round number or set it to 1 if an existing round number isn't found.
		$this->round = $this->player->saved_battle['info']['round'] ? (int) $this->player->saved_battle['info']['round'] + 1 : 1;

		// If the enemies array in saved_battle is not empty then loop over saved_battle['enemies']  which is made up of arrays itself. Feed those
		// to DRGRPG_Monster to load $this->enemies with monster objects.
		if ( ! empty( $this->player->saved_battle['enemies'] ) ) {
			for ( $i = 0, $clen = count( $this->player->saved_battle['enemies'] ); $i < $clen; $i++ ) {
				$this->enemies[] = new DRGRPG_Monster( $this->player->saved_battle['enemies'][ $i ] );
			}
		}
	} // end continue_from_saved_battle


	/**
	 * Store the battle data in json.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function store_battle_data_for_browser() {
		$this->add_to_json( 'round', $this->round );
		$this->add_to_json( 'enemies',
			$this->get_all_monsters_data_for_new_combat()
		);
	}

	/**
	 * Get an array of data representing all the monsters being fought by the player.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return array Monster data.
	 */
	private function get_all_monsters_data_for_new_combat() {
		$data_to_return = [];
		for ( $i = 0, $len = count( $this->enemies ); $i < $len; $i++ ) {
			$data_to_return[] = $this->enemies[ $i ]->get_monster_data_for_browser();
		}
		return $data_to_return;
	}

	/**
	 * Get an array of data representing all the monsters being fought by the player.
	 *
	 * TODO: Later this should be changed to use a different method that does not
	 * get the monster image, in order to reduce the data sent to the browser for
	 * combat updates. The JavaScript needs to be made to save the enemy images
	 * between turns first though, so there's no time for it at the moment.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return array Monster data.
	 */
	private function get_all_monsters_data_for_combat_update() {
		$data_to_return = [];
		for ( $i = 0, $len = count( $this->enemies ); $i < $len; $i++ ) {
			$data_to_return[] = $this->enemies[ $i ]->get_monster_data_for_browser();
		}
		return $data_to_return;
	}

	/**
	 * Loop over all the monsters and gather any combat results messages they hold.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return array  All combat results from the monsters.
	 */
	private function get_all_monsters_results() {
		$data_to_return = [];
		// Loop through all the enemies.
		for ( $i = 0, $len = count( $this->enemies ); $i < $len; $i++ ) {

			// Only add the data to $data_to_return is there is data
			// to return. Need this to avoid sending back empty array
			// entries in $data_to_return.
			if ( 0 !== count( $this->enemies[ $i ]->json_to_return ) ) {
				$data_to_return[] = $this->enemies[ $i ]->json_to_return;
			}
		}

		return $data_to_return;
	}

	/**
	 * Execute a battle turn.
	 *
	 * The player will attack an enemy, utilizing a skill if one has been used, Then
	 * the enemies left alive will attack the player. Finally the updated battle
	 * data is saved to the database and the results are returned for being
	 * sent to the browser.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array  Battle results data for the browser.
	 */
	public function execute_battle_turn() {

		// There are no enemies, so no combat turn to execute.
		if ( empty( $this->enemies ) ) {
			return;
		}

		// Find first enemy left alive and target it.
		for ( $i = 0, $len = count( $this->enemies ); $i < $len; $i++ ) {
			if ( $this->enemies[ $i ]->hp > 0 ) {
				$this->targeting = $i;
				break;
			}
		}

		// Player attack, using a skill if one is active and they have enough MP.
		if ( ! empty( $this->player->active_skill['name'] ) &&
				$this->player->mp >= $this->player->active_skill['cost'] ) {

			// Set base damage to monster equal to the strength of the skill.
			$dmg_to_monster = $this->player->active_skill['strength'];

			/**
			 * Augment player's attack power for attacks with skills.
			 *
			 * Alter the power of player's attacks using skills. This will still be lowered
			 * by enemy's defense.
			 *
			 * @since 0.1.0
			 * @param string $attack_power Player's skill attack power.
			 */
			$dmg_to_monster = round(
				(int) apply_filters( 'drgrpg_player_skill_attack_power',
					$dmg_to_monster
				)
			);

			// Player has a 90% chance of having their attack decreased by the
			// monster's defense, and a 10% chance of having a 'critical hit', with
			// attack lowered by only 1/4 of the defense value.
			if ( mt_rand( 1, 10 ) > 1 ) {
				$dmg_to_monster -= $this->enemies[ $this->targeting ]->defense;
			} else {
				$dmg_to_monster -= round( $this->enemies[ $this->targeting ]->defense / 4 );
			}

			// Store any rewards earned in $attack_result.
			$attack_result = $this->enemies[ $this->targeting ]->take_damage( $dmg_to_monster );

			// Send a message to the browser describing the damage done by the skill.
			$this->add_to_json( 'playerSkillUsed', 'You caused ' .
				number_format( $dmg_to_monster ) . ' damage to ' .
				$this->enemies[ $this->targeting ]->name .
				' with your ' . $this->player->active_skill['name'] . ' skill.'
			);

			// Reduce player's MP by the amount the skill required.
			$this->player->augment_stat( 'mp', ( $this->player->active_skill['cost'] * -1 ) );

			// Used the skill already, so no longer need it.
			$this->player->active_skill = null;
		} else { // Perform a normal physical attack instead of using a skill.

			// Set base damage to monster equal to the strength of the skill.
			$dmg_to_monster = $this->calculate_player_attack( $this->player );

			/**
			 * Augment player's attack power for normal attacks.
			 *
			 * Alter the power of player's normal attacks. This will still be lowered
			 * by enemy's defense.
			 *
			 * @since 0.1.0
			 * @param string $attack_power Player's attack power.
			 */
			$dmg_to_monster = round(
				(int) apply_filters( 'drgrpg_player_attack_power',
					$dmg_to_monster
				)
			);

			// Reduce damage by the monster's defense value.
			$dmg_to_monster -= $this->enemies[ $this->targeting ]->defense;

			// Store any rewards earned in $attack_result.
			$attack_result = $this->enemies[ $this->targeting ]->take_damage( $dmg_to_monster );
		}

		// Give the player any XP and gold earned this round.
		// Need to do it here before the enemy gets a chance to attack. Otherwise
		// the player could defeat one enemy, be defeated in the same turn by another enemy, and the battle would end before the player received their rewards.
		// TODO: This will need extended to support rewards from multiple
		// monsters once skills that hit multiple enemies is added.
		if ( ! empty( $attack_result['xp'] ) ) {
			$this->player->augment_stat( 'xp', $attack_result['xp'] );
		}
		if ( ! empty( $attack_result['gold'] ) ) {
			$this->player->augment_stat( 'gold', $attack_result['gold'] );
		}

		// Enemies' turn to attack.
		for ( $i = 0, $len = count( $this->enemies ); $i < $len; $i++ ) {
			if ( $this->enemies[ $i ]->hp > 0 ) {

				// Calculate the strength of the enemy's attack.
				$dmg_to_player = $this->calculate_monster_attack( $this->enemies[ $i ] );

				/**
				 * Augment enemy attack power.
				 *
				 * Alter the power of enemy's attack. This will still be lowered
				 * by player's defense.
				 *
				 * @since 0.1.0
				 * @param string $attack_power Enemy's attack power.
				 */
				$dmg_to_player = round(
					(int) apply_filters( 'drgrpg_enemy_attack_power',
						$dmg_to_player
					)
				);

				// Enemy has a 90% chance of having its attack lowered
				// by the player's defense value, and a 10% chance of having the
				// attack lowered by only 1/4 of the defense value.
				if ( mt_rand( 1, 10 ) > 1 ) {
					$dmg_to_player -= $this->player->defense;
				} else {
					$dmg_to_player -= round( $this->player->defense / 4 );
				}

				// Damage to player will be greater than 0, so proceed with reducing HP
				// and checking to see if the player's HP hit 0.
				if ( $dmg_to_player > 0 ) {
					$this->player->augment_stat( 'hp', ( $dmg_to_player * -1 ) );

					// Player out of HP. End battle.
					if ( 0 === $this->player->hp ) {
						$this->lose_combat( $this->enemies[ $i ]->name );
						return $this->json_to_return;
					}
				}
			}
		} // end for loop

		// Clean up after this turn, update the database, and gather results needed
		// to be sent back to the browser.
		$this->end_battle_turn();

		return $this->json_to_return;
	} // end execute_battle_turn

	/**
	 * Calculate the base damage caused by the monster's attack.
	 *
	 * This may become more complicated later. For now it calculates
	 * damage as being a random number between monster's attack power -15%
	 * and +15%; Player defense is factored in within execute_turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param DRGRPG_Monster $monster The monster object performing the attack.
	 * @return int The base number of points of damage to cause the player.
	 */
	private function calculate_monster_attack( DRGRPG_Monster $monster ) {

		// Make sure value is set to at least the minimum allowed value.
		$attack = $monster->attack >= 0 ? $monster->attack : 0;

		// Calculate the lower threshhold for the randomized number.
		$min = $monster->attack - ( $monster->attack * 0.15 );
		$min = $min >= 0 ? $min : 0;

		// Calculate the upper threshold for the randomized number.
		$max = $monster->attack + ( $monster->attack * 0.15 );
		$max = $max >= 0 ? $max : 0;

		// Return a random number between $min and $max.
		return mt_rand( $min, $max );
	}

	/**
	 * Calculate the base damage caused by the player's attack.
	 *
	 * This will factor in weapon skills and critical hits later on. For now it calculates
	 * damage as being a random number between player's attack power -10%
	 * and +10%; Monster defense is factored in within execute_turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param DRGRPG_Player $player The player object performing the attack.
	 * @return int  The base number of points of damage to cause the enemy.
	 */
	private function calculate_player_attack( DRGRPG_Player $player ) {

		// Make sure value is set to at least the minimum allowed value.
		$attack = $player->attack >= 0 ? $player->attack : 0;

		// Calculate the lower threshhold for the randomized number.
		$min = $player->attack - ( $player->attack * 0.10 );
		$min = $min >= 0 ? $min : 0;

		// Calculate the upper threshold for the randomized number.
		$max = $player->attack + ( $player->attack * 0.10 );
		$max = $max >= 0 ? $max : 0;

		// Return a random number between $min and $max.
		return mt_rand( $min, $max );
	}

	/**
	 * Player has hit 0 HP so end the battle.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string $monster_name The name of the monster that defeated player.
	 * @return void
	 */
	private function lose_combat( $monster_name ) {

		// Get all data/messages for the browser the monsters are holding.
		$this->add_to_json( 'results', $this->get_all_monsters_results() );

		$this->delete_saved_battle();

		// The message shown to the player upon defeat is defined within
		// the JS's DRGRPG.config.language.playerDefeated.
		$this->add_to_json( 'playerDefeated', $monster_name );
	}

	/**
	 * Flee the battle.
	 *
	 * End the current battle. Player keeps the rewards they have already earned
	 * and avoid any further damage.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array Any combat data and messages triggered before/while
	 *                    the player flees.
	 */
	public function flee_battle() {
		$this->delete_saved_battle();

		// The message shown to the player upon defeat is defined within
		// the JS's DRGRPG.config.language.playerFleeBattle.
		$this->add_to_json( 'flee', 1 );

		return $this->json_to_return;
	}

	/**
	 * Do the end of battle turn cleanup.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function end_battle_turn() {

		// Loop through the monsters and return their stat data and
		// any messages they have stored to return.
		$this->add_to_json( 'round', $this->round );

		// Send the entire enemies array.
		// Don't delete monsters from the enemies array until the end of
		// a battle. That way the array of enemies can maintain the same size
		// in order to simplify the JS's job of associating data sent to it with
		// the display of monsters on the screen.
		$this->add_to_json( 'enemies',
			$this->get_all_monsters_data_for_combat_update()
		);

		// Get all data/messages for the browser the monsters are holding.
		$this->add_to_json( 'results', $this->get_all_monsters_results() );

		// Loop through the $enemies array and see if any are left alive.
		$any_alive = false;
		for ( $i = 0, $len = count( $this->enemies ); $i < $len; $i++ ) {
			if ( $this->enemies[ $i ]->hp > 0 ) {
				$any_alive = true;
				break;
			}
		}

		// No enemies left alive, so end the battle.
		if ( false === $any_alive ) {
			$this->add_to_json( 'endCombat', 'y' );
			$this->delete_saved_battle();
		} else {
			// Still enemies to fight, so save the current battle state to the database.
			$this->save_battle_to_user();
		}
	} // end end_battle_turn

	/**
	 * Save the current battle state to the database.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function save_battle_to_user() {
		$compiled_battle_data = array();

		$compiled_battle_data['info'] = array(
			'round' => $this->round,
		);

		// Loop through each enemy and save the enemy's currenty state as
		// an array within the $compiled_battle_data['enemies'] array.
		for ( $i = 0, $elen = count( $this->enemies ); $i < $elen; $i++ ) {
			$compiled_battle_data['enemies'][] = $this->enemies[ $i ]->get_monster_data_for_saving();
		}

		// Saved the compiled batle data to user meta in the database.
		update_user_meta( $this->player->id, 'drgrpg_current_battle',
			$compiled_battle_data
		);
	} // end save_battle_to_user

	/**
	 * Delete player's saved battle.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function delete_saved_battle() {
		$this->player->saved_battle = '';
		update_user_meta( $this->player->id, 'drgrpg_current_battle', '' );
	}

	/**
	 * Add data to be sent back to the browser as JSON at the end of the turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string $key Used for grouping similar message types.
	 * @param string $data The data the browser will be using.
	 * @return void
	 */
	private function add_to_json( $key, $data ) {
		if ( empty( $key ) || empty( $data ) ) {
			return;
		}
		$this->json_to_return[ $key ][] = $data;
	}
}
