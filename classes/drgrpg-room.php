<?php
/**
 * Holds the DRGRPG_Room class.
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
 * Handle displaying and interacting with rooms (map areas) in the game.
 *
 * Handles loading a room's data from the database and populating a
 * DRGRPG_Room object with the data. Provides methods for interacting
 * with the room and its objects, exits, and monsters.
 *
 * @since 0.1.0
 */
class DRGRPG_Room {

	/**
	 * The text the player sees upon entering a room.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $description;

	/**
	 * The post ID of the drgrpg_room CPT this room is loading from.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $id;

	/**
	 * The type of environment the room represents. It's used to apply a
	 * CSS class to the browser's body tag, allowing a game admin to style
	 * the game interface uniquely for each environment type if they desire.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $environment;

	/**
	 * An list of the objects within the room that the player can interact with.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $objects;

	/**
	 * The maximum number of monsters a player can encounter in one battle.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $max_monsters;

	/**
	 * An list of the monsters that can be found by hunting in the room.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $monsters;

	/**
	 * An list of all possible exits from the room.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $exits;

	/**
	 * The data to send back to the browser at the end of the turn.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $json_to_return = array();

	/**
	 * Any notifications to be sent back to the browser and displayed
	 * in notification boxes.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $notifications = array();


	/**
	 * Reference to a player object.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var DRGRPG_Player
	 */
	protected $player;

	/**
	 * Setup the room so it's ready to interact with.
	 *
	 * Get the meta data for the room and populate a DRGRPG_Room object
	 * if $which_room is found to refer to a valid room in the database.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer|string $which_room The post ID or title of the room to load.
	 * @param DRGRPG_Player|null $player The player object so the room can
	 *                                   access it by reference.
	 * @return void
	 */
	public function __construct( $which_room, DRGRPG_Player $player = null ) {

		// No valid argument passed to constructor, so fail.
		if ( empty( $which_room ) ) {
			return;
		}

		// Constructor was passed a string that isn't numeric, so check to see if
		// a post in the room CPT exists with a title matching the string.
		// If so, set $which_room to its post id.
		if ( is_string( $which_room ) && ! is_numeric( $which_room ) ) {
			$which_room = get_page_by_title( $which_room, OBJECT, 'drgrpg_room' )->ID;
		}

		// $which_room is is a number, or can be type cast to a number, so
		// see if meta data exists for a room post with an id of $which_room.
		if ( is_numeric( $which_room ) ) {
			$which_room = (int) $which_room;
			$room_data = get_post_meta( $which_room );
			// Found post meta (room_data) for this room, so populate the class
			// properties, performing validation in the process.
			if ( ! empty( $room_data ) ) {
				$this->id = $which_room;

				// Run wpautop on the description to preserve formatting.
				$this->description = wpautop( $room_data['_drgrpg_room_description'][0] );
				$this->environment = $room_data['_drgrpg_room_environment'][0];

				if ( ! empty( $room_data['_drgrpg_room_objects'][0] ) ) {
					$this->objects = unserialize( $room_data['_drgrpg_room_objects'][0] );
				}

				if ( ! empty( $room_data['_drgrpg_room_max_monsters'][0] ) ) {
					$this->max_monsters = (int) $room_data['_drgrpg_room_max_monsters'][0];
				} else {
					$this->max_monsters = 0;
				}

				if ( ! empty( $room_data['_drgrpg_room_monsters'][0] ) ) {
					$this->monsters = unserialize( $room_data['_drgrpg_room_monsters'][0] );
				}

				if ( ! empty( $room_data['_drgrpg_room_exits'][0] ) ) {
					$this->exits = unserialize( $room_data['_drgrpg_room_exits'][0] );
				}

				// Should have been passed in a player object so the room can
				// interact with it. Doesn't fail without it, but it will cause problems.
				if ( ! empty( $player ) ) {
					$this->player = $player;
				}
			} // end if for non-empty $room_data
		} else {
			// Were not given a valid room title or post ID, so return null.
			return;
		}
	} // end __construct

	/**
	 * __get magic method. Retrieve the specified property.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $property The name of the property you want the value of.
	 * @return mixed The value of the property.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
	}

	/**
	 * __set magic method. Set a property with the given value.
	 *
	 * I probably need to clamp down on this and only allow specific
	 * setter functions, but right now there are no circumstances a
	 * room property will be set outside of the room object.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $property The property name you want to set.
	 * @param mixed $value The new value to set the property to.
	 * @return void
	 */
	public function __set( $property, $value ) {
		if ( ! property_exists( $this, $property ) ) {
			return;
		}
		$this->property = $value;
	}

	/**
	 * Return ID of room linked to if this is a valid exit.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $exit The link text player clicked on.
	 * @return int Return the post ID of the room the exit leads to if
	 *                    this is a valid exit the player meets the requirements to see.
	 *                    Otherwise return false.
	 */
	public function has_exit( $exit ) {
		// Loop over all this room's exits to see if the exit specified can be found.
		for ( $i = 0, $len = count( $this->exits ); $i < $len; $i++ ) {
			// Found the exit. Now to see if it's valid.
			if ( $exit === $this->exits[ $i ]['link'] ) {
				// Return the connecting room's id if there is no requirement or
				// the player meets the requirements.
				if ( empty( $this->exits[ $i ]['requirement_type'] ) ||
					$this->check_requirements( $this->exits[ $i ]['requirement_type'],
					$this->exits[ $i ]['requirement_value'], $this->exits[ $i ]['requirement_match_type']  ) ) {
					return $this->exits[ $i ]['room_id'];
				}
			}
		}
		return false;
	}

	/**
	 * Check whether player passes the specified requirement.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string $requirement_type  The type of requirement check.
	 * @param string $requirement_value The value to be used when checking
	 *                                   whether player meets the requirement. Format
	 *                                   expected varies based on requirement type.
	 * @param string $match_type Do an 'exact' match or check for minimum value.
	 * @return boolean True if player passed the requirement. False if failed.
	 */
	private function check_requirements( $requirement_type, $requirement_value,
		$match_type = 'exact' ) {
		// Fail if you don't specify both a requirement type and value.
		if ( empty( $requirement_type ) || empty( $requirement_value ) ) {
			return false;
		}

		if ( 'has_item' === $requirement_type ) {
			// Check whether player has a specific item in their inventory or equipped.
			if ( true === $this->player->has_item( $requirement_value ) ) {
				return true;
			}
		} else if ( 'has_quest_flag' === $requirement_type ) {
			// Check whether player has a quest flag at a specific or minimum value.
			list( $quest_flag, $flag_value ) = explode( '=', $requirement_value );

			if ( empty( $quest_flag ) || ! isset( $flag_value ) ) {
				return false;
			}

			if ( $this->player->has_quest_flag( $quest_flag, $flag_value,
					( 'exact' === $match_type ? true : false ) ) ) {
				return true;
			}
		} else if ( 'has_guild_level' === $requirement_type ) {
			// Check whether a player has ever reached a specific level in the
			// specified guild. Player does not have to currently be a member
			// of the guild to pass.
			list( $guild_name, $guild_level ) = explode( '=', $requirement_value );

			if ( empty( $guild_name ) || ! isset( $guild_level ) ) {
				return false;
			}

			if ( $this->player->has_guild_level( $guild_name, $guild_level,
					( 'exact' === $match_type ? true : false ) ) ) {
				return true;
			}
		} else if ( 'in_guild' === $requirement_type ) {
			// Check whether player is in a specific guild.
			list( $guild_name, $yes_no ) = explode( '=', $requirement_value );

			// Fail if arguments supplied were invalid, or $yes_no was given
			// a value of 0, meaning check to see the player is not in the guild.
			if ( empty( $guild_name ) || ! isset( $yes_no )  || ! is_numeric( $yes_no ) ) {
				return false;
			}

			$yes_no = (int) $yes_no;
			if ( $this->player->currently_in_guild( $guild_name,
					( 1 === $yes_no ? true : false ) ) ) {
				return true;
			}
			else {
				return false;
			}
		} else if ( 'has_skill' === $requirement_type ) {
			// Check whether a player has a skill and/or whether it is at a
			// specific level.
			list( $skill_name, $skill_level ) = explode( '=', $requirement_value );

			if ( empty( $skill_name ) ) {
				return false;
			}

			// No skill level specified, so just check for the existence of the skill.
			if ( ! isset( $skill_level ) ) {
				if ( ! empty( $this->player->skills[ $skill_name ] ) ) {
					return true;
				}
			} else if ( is_numeric( $skill_level ) ) {
				// Skill level specified, so compare $skill_level to the appropriate
				// skill's level.
				$skill_level = (int) $skill_level;
				// Checking for absence of the skill ($skill_level was set to 0).
				if ( 0 === $skill_level ) {
					// Player doesn't have the skill, so this passes.
					if ( empty( $this->player->skills[ $skill_name ] ) ) {
						return true;
					} else {
						// Player has the skill, so fail.
						return false;
					}
				} else {
					// Check for skill level, taking into account match type.
					// TODO: finish this.
					if ( empty( $this->player->skills[ $skill_name ] ) ||
							$this->player->skills[ $skill_name ] < $skill_level ) {
						return false;
					} else {
						return true;
					}
				}
			}
			return false;
		} // end if for has_skill

		// If it hasn't returned true already it hasn't passed any requirement
		// checks. Return false to be on the safe side.
		return false;
	} // end check_requirements

	/**
	 * Get a list of the objects the player meets the requirements for viewing.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return array An array of arrays containing each object's data.
	 */
	private function get_objects() {
		$valid_objects = array();

		// Loop over all the objects to check their requirements.
		for ( $i = 0, $len = count( $this->objects ); $i < $len; $i++ ) {

			// Not a fully populated object.
			if ( empty( $this->objects[ $i ]['name'] ) || empty( $this->objects[ $i ]['description'] ) ) {
				continue;
			}

			$maybe_insert = false;

			// If the object has no requirements then maybe insert it.
			if ( empty( $this->objects[ $i ]['requirement_type'] ) ) {
				$maybe_insert = true;
			} else {
				if ( empty( $this->objects[ $i ]['requirement_value'] ) ) {
					// If an object has a requirement but no value specified for
					// the requirement then don't chance showing the player
					// something not intended.
					continue;
				}

				// Player meets requirements, so mark object as maybe being
				// included in the final list to display.
				if ( $this->check_requirements( $this->objects[ $i ]['requirement_type'],
						$this->objects[ $i ]['requirement_value'], $this->objects[ $i ]['requirement_match_type']  ) ) {
					$maybe_insert = true;
				}
			} // end else (there is a requirement )

			// If the player meets the requirements (if there are any) then make sure
			// no object with this name already exists in $valid_objects.
			if ( true === $maybe_insert ) {
				$yes_insert = true;

				// Check to see if we've already met the requirements for an
				// object with this name. If so, don't include a second version.
				for ( $j = 0, $jlen = count( $valid_objects ); $j < $jlen; $j++ ) {
					if ( $valid_objects[ $j ]['name'] === $this->objects[ $i ]['name'] ) {
						$yes_insert = false;
						break;
					}
				}

				// Yes, include this object in the data sent to the browser.
				if ( true === $yes_insert ) {
					$valid_objects[] = [
						'name' => $this->objects[ $i ]['name'],
						'description' => wpautop( $this->objects[ $i ]['description'] ),
						'action' => (int) ( ! empty( $this->objects[ $i ]['action_type'] ) &&
								! empty( $this->objects[ $i ]['action_value'] ) ),
						'group' => $this->objects[ $i ]['object_group'],
					];
				}
			} // end if that confirms object is not already in $valid_objects
		} // end for loop

		return $valid_objects;
	} // end get_objects

	/**
	 * See if an object action submitted by the browser is valid and execute it
	 * if it is.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $object_name The name of the object player is interacting with.
	 * @return void
	 */
	public function process_object_action( $object_name ) {
		// Loop over all the objects in the room to find the object we're looking for.
		for ( $i = 0, $len = count( $this->objects ); $i < $len; $i++ ) {
			// This isn't the object we're looking for. Skip to next object.
			if ( $object_name !== $this->objects[ $i ]['name'] ) {
				continue;
			} else {
				// Found the object.  does player meet the requirements.
				if ( empty( $this->objects[ $i ]['requirement_type'] ) ||
						$this->check_requirements( $this->objects[ $i ]['requirement_type'],
						$this->objects[ $i ]['requirement_value'], $this->objects[ $i ]['requirement_match_type']  ) ) {

					// Not a valid action.
					if ( empty( $this->objects[ $i ]['action_type'] ) || empty( $this->objects[ $i ]['action_value'] ) ) {
						break;
					}

					if ( 'multiple' === $this->objects[ $i ]['action_type'] ) {
						// Multiple actions found in the format
						// action-argument;action-argument;action-argument
						// Split the string into an array and pass it to
						// execute_object_actions.
						$this->execute_object_actions(
							explode( ';', $this->objects[ $i ]['action_value'] )
						);
					} else {
						// Single action, pass it to execute_object_actions.
						$this->execute_object_actions(
							array(
								$this->objects[ $i ]['action_type'] . '-' .
								$this->objects[ $i ]['action_value']
							)
						);
					}
				} // end if block for when player does meet requirements
			} // end else
		} // end for loop
	} // end process_object_action

	/**
	 * Loop over the list of actions for an object.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param array $action_list Expects an array with each value
	 *                            in the format actionType-value.
	 * @return void
	 */
	private function execute_object_actions( $action_list ) {
		// Loop over the array of actions passed in.
		for ( $i = 0, $len = count( $action_list ); $i < $len; $i++ ) {
			list( $action, $arg ) = explode( '-', $action_list[ $i ] );
			// Lower the player's HP.
			if ( 'damage_player' === $action ) {
				if ( is_numeric( $arg ) ) {
					$arg = (int) $arg;
					$this->player->augment_stat( 'hp', $arg * -1 );
				} else {
					continue;
				}
			} else if ( 'set_quest_flag' === $action ) {
				// Set a quest flag to the designated value.
				list( $flag, $value ) = explode( '=', $arg );
				if ( empty( $flag ) || empty( $value ) || ! is_numeric( $value ) ) {
					continue;
				}
				$this->player->set_quest_flag( $flag, $value );
			} else if ( 'give_item' === $action ) {
				// Add an item to the player's inventory.
				// TODO: Possibly support accepting the item name later.
				$item = new DRGRPG_Item( $arg );
				$item_array = $item->get_item_data_as_array();
				// Fail if this item does not exist in the database.
				if ( empty( $item_array['type'] ) ) {
					continue;
				}
				$this->player->add_item_to_inventory( $item );
			} else if ( 'sell_to_player' === $action ) {
				// If a player has enough gold, take their gold and add an item
				// to their inventory.
				list( $item, $price ) = explode( 'for', $arg );

				if ( is_numeric( $item ) && is_numeric( $price ) ) {
					$item = (int) $item;
					$price = (int) $price;

					// Send an error notification and bail if the player
					// doesn't have enough money.
					if ( $this->player->gold < $price ) {
						$failed_purchase_message = sprintf(
							__( 'Sorry. You need %s gold to buy that.', 'drgrpg' ),
							number_format( $price )
						);

						/**
						 * Filter the notification shown when player tries to buy
						 * an item without having enough gold.
						 *
						 * @since 0.1.0
						 * @param string $message The message to be shown.
						 * @param string $price The price of the item player
						 *                      attempted to buy.
						 */
						$failed_purchase_message = apply_filters( 'drgrpg_not_enough_gold_message',
							$failed_purchase_message, number_format( $price )
						);

						$this->add_notification( 'error', $failed_purchase_message );
						continue;
					}

					// Has enough gold. Let's see if the item is real.
					$item_obj = new DRGRPG_Item( $item );
					$item_array = $item_obj->get_item_data_as_array();

					// Item doesn't exist in the database. Show the player an
					// error notification so they can report it.
					if ( empty( $item_array['type'] ) ) {
						$this->add_notification( 'error', 'Sorry. That is not a valid item. Please report this to the administrator, noting you are in room ' . $this->id . ', what you clicked to buy, and that the item id was ' . $item . '.'
						);
						continue;
					}

					// Item exists. Take the gold, add the item, tell the player.
					$this->player->augment_stat( 'gold', $price * -1 );
					$this->player->add_item_to_inventory( $item_obj );

					// Prepare notification message for browser.
					$drgrpg_bought_item_message = sprintf(
						__( 'You\'ve purchased a %s for %s gold.', 'drgrpg' ),
						$item_array['name'],
						number_format( $price )
					);

					/**
					 * Filter the notification shown when player successfully
					 * purchases a new item.
					 *
					 * @since 0.1.0
					 * @param string $message The message to be shown.
					 * @param string $item Name of the item bought.
					 * @param string $price The price of the item player bought.
					 */
					$drgrpg_bought_item_message = apply_filters( 'drgrpg_bought_item_message',
						$drgrpg_bought_item_message,
						$item_array['name'],
						number_format( $price )
					);

					$this->add_notification( 'goodNews', $drgrpg_bought_item_message );
				}
			} else if ( 'award_achievement' === $action ) {
				// Award a player an achievement.
				// Requires $arg to be a number in order to proceed.
				if ( is_numeric( $arg ) ) {
					$arg = (int) $arg;
					$this->player->award_achievement( $arg );
				} else {
					continue;
				}
			} else if ( 'join_guild' === $action ) {
				// Allow player to join a guild, or rejoin at their previous guild
				// level if they were once a member and left.
				$this->player->join_guild( $arg );
			} else if ( 'leave_guild' === $action ) {
				// Remove player from a guild, but they will maintain a record of
				// their level in the guild if they want to rejoin.
				$this->player->leave_guild();
			} else if ( 'increase_guild_level' === $action ) {
				// Increase a player's guild level to a specific level.
				list( $guild_name, $new_guild_level ) = explode( '=', $arg );

				if ( empty( $guild_name ) || empty( $new_guild_level ) ) {
					continue;
				}

				$this->player->increase_guild_level( $guild_name, $new_guild_level );
			} else if ( 'teach_skill' === $action ) {
				// Give a player a new skill.
				$this->player->add_skill( $arg );
			}
		} // end for loop looping over all object actions

	} // end execute_object_actions

	/**
	 * Get the list of exits a player meets the requirements to see.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return array The list of exits to show the player.
	 */
	private function get_exits() {
		$valid_exits = array();

		// Loop over all the exits in room. First check to see if an player meets
		// the requirements to see the exit. Then check to make sure only
		// one version of each exit is shown (there can be multiple exits
		// with the same name when dealing with multiple possible
		// requirements being met).
		for ( $i = 0, $len = count( $this->exits ); $i < $len; $i++ ) {

			// Not a fully populated exit so don't include it.
			if ( empty( $this->exits[ $i ]['link'] ) || empty( $this->exits[ $i ]['room_id'] ) ) {
				continue;
			}

			$maybe_insert = false;
			// If the exit has no requirements then maybe insert it.
			if ( empty( $this->exits[ $i ]['requirement_type'] ) ) {
				$maybe_insert = true;
			} else {
				// If an object has a required variable but no value then
				// don't chance showing the player something not intended.
				if ( empty( $this->exits[ $i ]['requirement_value'] ) ) {
					continue;
				}

				// Player passes the requirement, so maybe show this exit.
				if ( $this->check_requirements( $this->exits[ $i ]['requirement_type'],
						$this->exits[ $i ]['requirement_value'], $this->exits[ $i ]['requirement_match_type']  ) ) {
					$maybe_insert = true;
				}
			}

			// If the player meets the requirements (if there are any) then make sure
			// no object with this name already exists in $valid_exits.
			if ( true === $maybe_insert ) {
				$yes_insert = true;
				for ( $j = 0, $jlen = count( $valid_exits ); $j < $jlen; $j++ ) {
					if ( $valid_exits[ $j ]['link'] === $this->exits[ $i ]['link'] ) {
						$yes_insert = false;
						break;
					}
				}
				if ( true === $yes_insert ) {
					$valid_exits[] = [
						'link' => $this->exits[ $i ]['link'],
						'room_id' => $this->exits[ $i ]['room_id'],
					];
				}
			}
		} // end for loop over all exits

		return $valid_exits;
	} // end get_exits

	/**
	 * Generate an array of monster objects randomly selected from this room.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer $how_many How many monsters to generate.
	 * @return array An array of monster objects.
	 */
	public function get_random_monsters( $how_many = 1 ) {
		$mons_to_return = [];
		$count = count( $this->monsters ) - 1;

		for ( $i = 0; $i < $how_many; $i++ ) {
			$mons_to_return[] = new DRGRPG_Monster(
					$this->monsters[ mt_rand( 0, $count ) ]
			);
		}

		return $mons_to_return;
	}

	/**
	 * Grab the room data in a single array.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array All the room data.
	 */
	public function get_room_data_as_array() {
		return array(
			'id' => $this->id,
			'description' => $this->description,
			'environment' => $this->environment,
			'monsters' => count( $this->monsters ),
			'objects' => $this->get_objects(),
			'exits' => $this->get_exits(),
		);
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

	/**
	 * Add notification to be displayed in browser.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string $type Used in specifying a CSS class for the notification.
	 * @param string $message The message that will be displayed.
	 * @return void
	 */
	private function add_notification( $type, $message ) {
		$this->notifications[] = array( $type, $message );
	}

	/**
	 * Get the room notifications.
	 *
	 * Get the room notifications array with thiss getter method instead of
	 * grabbing the property directly in case this becomes more complex later on.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array An array of the room notifications to be shown for this turn.
	 */
	public function get_notifications() {
		return $this->notifications;
	}
}
