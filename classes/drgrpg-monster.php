<?php
/**
 * Holds the DRGRPG_Monster class.
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
 * Holds all functions needed for displaying and interacting with monsters
 * within the game.
 *
 * @since 0.1.0
 */
class DRGRPG_Monster {

	/**
	 * The name of the monster
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * URL of the featured image for the monster
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $image = '';

	/**
	 * Number of hit points the monster has currently
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $hp;

	/**
	 * Maximum number of hit points the monster can have (full health)
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $max_hp;

	/**
	 * Strength of the monster's physical attacks
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $attack;

	/**
	 * How much physical damage caused to the monster is reduced
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $defense;

	/**
	 * Amount of gold player receives upon defeating the monster
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $reward_gold;

	/**
	 * Amount of experience player receives upon defeating the monster
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $reward_exp;

	/**
	 * The minimum values the monster's stats can be set to.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $min_stat_levels = array(
		'hp' => 0,
		'max_hp' => 10,
		'attack' => 1,
		'defense' => 0,
		'reward_gold' => 0,
		'reward_exp' => 0,
	);

	/**
	 * The data to send back to the browser at the end of the turn.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $json_to_return = array();

	/**
	 * A list of rewards the player earned by defeating the monster.
	 * Sent to the browser for displaying to the player
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $rewards_to_give = array();

	/**
	 * Setup the monster so it's ready to interact with.
	 *
	 * Get the meta data for the monster and populate a DRGRPG_Monster object
	 * if $which_monster is found to refer to a valid monster in the database. Or
	 * create a DRGRPG_Monster object based on the array of data passed to
	 * the constructor.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer|string|array $which_monster The post ID or title of the
	 *              room to load. If an array is passed in then that will be used to
	 *              populate the monster's properties instead of consulting the DB.
	 * @return void
	 */
	public function __construct( $which_monster ) {

		// No argument passed to constructor, so fail.
		if ( empty( $which_monster ) ) {
			return false;
		}

		// If an array is passed, likely from a saved battle,
		// then populate the monster object with that instead of making
		// a DB lookup. Validate the data of course.
		if ( is_array( $which_monster ) ) {
			$this->name = ! empty( $which_monster['name'] ) ?
				$which_monster['name'] : 'Unknown Beast';
			$this->image = ! empty( $which_monster['image'] ) ?
				$which_monster['image'] : '';

			// Use the initiate_stat method to confirm the data contained
			// within the array meets the minimum stat value requirements
			// outlined in the $min_stat_levels class property.
			$this->hp = $this->initiate_stat( $which_monster['hp'], 0, $this->min_stat_levels['hp'] );
			$this->max_hp = $this->initiate_stat( $which_monster['max_hp'], 0, $this->min_stat_levels['max_hp'] );
			$this->attack = $this->initiate_stat( $which_monster['attack'], 0, $this->min_stat_levels['attack'] );
			$this->defense = $this->initiate_stat( $which_monster['defense'], 0, $this->min_stat_levels['defense'] );
			$this->reward_gold = $this->initiate_stat( $which_monster['reward_gold'], 0, $this->min_stat_levels['reward_gold'] );
			$this->reward_exp = $this->initiate_stat( $which_monster['reward_exp'], 0, $this->min_stat_levels['reward_exp'] );
		}

		// Constructor was passed a string that isn't numeric, so check to see if
		// a post in the Monster CPT exists with a title matching the string.
		// If so, set $which_monster to its post id.
		if ( is_string( $which_monster ) && ! is_numeric( $which_monster ) ) {
			$which_monster = get_page_by_title( $which_monster, OBJECT, 'drgrpg_monster' )->ID;
		}

		// $which_monster is is a number, or can be type cast to a number, so
		// see if meta data exists for a monster post with an id of $which_monster
		if ( is_numeric( $which_monster ) ) {

			// Type cast $which_monster with (int) to make sure it's an integer.
			$which_monster = (int) $which_monster;

			// Set name to the title of the specified drgrpg_monster post.
			$this->name = get_the_title( $which_monster );

			// If name is empty then get_the_title didn't find a post
			// with an ID of $which_monster. That must mean this monster
			// does not exist. Return null.
			if ( empty( $this->name ) ) {
				return;
			}

			// Grab the URL for the medium size version of the monster's
			// featured image.
			$this->image = wp_get_attachment_image_src(
				get_post_thumbnail_id( $which_monster ), 'medium'
			)[0];

			// Grab the post meta from the DB related to this monster.
			$monster_data = get_post_meta( $which_monster );

			// Post meta was found, so use it to populate the monster's stats.
			if ( ! empty( $monster_data ) ) {

				// The monster's stats are randomized using initiate_stat,
				// so each instance of the monster can have different stats.
				$this->hp = $this->initiate_stat( $monster_data['_drgrpg_monster_hp'][0], 5, $this->min_stat_levels['hp'] );

				// Want HP and Max HP to be the same to begin with (max health).
				// Fighting sick monsters is just not nice.
				$this->max_hp = $this->hp;
				$this->attack = $this->initiate_stat( $monster_data['_drgrpg_monster_attack'][0], 5, $this->min_stat_levels['attack'] );
				$this->defense = $this->initiate_stat( $monster_data['_drgrpg_monster_defense'][0], 5, $this->min_stat_levels['defense'] );
				$this->reward_gold = $this->initiate_stat( $monster_data['_drgrpg_monster_reward_gold'][0], 5, $this->min_stat_levels['reward_gold'] );
				$this->reward_exp = $this->initiate_stat( $monster_data['_drgrpg_monster_reward_exp'][0], 5, $this->min_stat_levels['reward_exp'] );
			}
		} else {
			// Constructor wasn't given valid data, so return null.
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
	 * For now I've set this to always fail. May be removed
	 * in the future.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $property The property name you want to set.
	 * @param mixed $value The new value to set the property to.
	 * @return void
	 */
	public function __set( $property, $value ) {
		return;
	}

	/**
	 * Increase/Decrease a monster's stats.
	 *
	 * This is preferred over using __set because there are sanity checks
	 * included to make sure stats are never set to an invalid value.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $stat Name of the stat to be augmented.
	 * @param integer $amt A number representing the amount to change the
	 *                   		stat. May be positive or negative.
	 * @return bool | void Returns false if passed improper arguments.
	 */
	public function augment_stat( $stat, $amt ) {
		if ( empty( $stat ) || empty( $amt ) ||
				 ! property_exists( $this, $stat ) || ! is_numeric( $amt ) ) {
			return false;
		}

		$amt = (int) $amt;

		if ( 'hp' === $stat ) {
			// Ensure HP doesn't go below 0 and doesn't go over max HP.
			$new_hp = $this->hp;
			$new_hp += $amt;
			$new_hp = $new_hp >= 0 ? $new_hp : 0;
			$new_hp = $new_hp <= $this->max_hp ? $new_hp : $this->max_hp;
			$this->hp = $new_hp;

			// The monster's HP has hit 0. It should be defeated.
			if ( 0 === $new_hp ) {
				$this->be_defeated();
			}
		} else {
			// Use the $min_stat_levels array at the top if available.
			if ( isset( $this->min_stat_levels[ $stat ] ) ) {
				if ( $this->$stat + (int) $amt > $this->min_stat_levels[ $stat ] ) {
					$this->$stat += (int) $amt;
				} else {
					// Attempted to set the stat to a value lower than its allowed
					// minimum, so set it to the minimum instead.
					$this->$stat = $this->min_stat_levels[ $stat ];
				}
			} else {
				// This stat wasnt found in the $min_stat_levels array, so
				// just make sure it's set to a value greater than 0.
				if ( $this->$stat + (int) $amt > 0 ) {
					$this->$stat += (int) $amt;
				} else {
					// When all else fails, set it to a value of 1.
					$this->$stat = 1;
				}
			}
		}
	} // end augment_stat

	/**
	 * Ensure the stat is set to an acceptable value and randomize if required.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param integer $value The base value used to set the stat.
	 * @param integer $variance Represents the percentage up and down the stat
	 *                        value can be when randomized. A $variance of 5 would mean 5%.
	 * @param integer $min The mininum acceptable value the stat can be set to.
	 * @return int The value to use to set the stat.
	 */
	private function initiate_stat( $value, $variance, $min ) {

		// Return false if any arguments are empty.
		// arguments could be 0 and be valid, so use isset instead of empty.
		if ( ! isset( $value ) || ! isset( $variance ) || ! isset( $min ) ) {
			return false;
		}

		// Use $value and $variance if they were given acceptable values
		// and $value is greater than or equal to $min.
		if ( isset( $value ) && is_numeric( $value ) && (int) $value >= (int) $min ) {

			// Randomize the stat if $variance is greater than 0.
			if ( $variance >= 1 ) {
				return $this->randomize_stat( $value, $variance, $min );
			} else {
				return (int) $value;
			}
		} else {
			// Return $min as an integer if it's set and is numeric.
			if ( isset( $min ) && is_numeric( $min ) ) {
				return (int) $min;
			} else {
				// Set stat to 1 when all else fails.
				return 1;
			}
		}
	} // end initiate_stat

	/**
	 * Return a randomized stat value.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param integer $value The base stat value.
	 * @param integer $amt_to_vary Represents the percentage up and down the stat.
	 *                           value can be when randomized. A $variance of 5 would mean 5%.
	 * @param integer $min_allowed The mininum acceptable value the stat can
	 *                             be set to.
	 * @return int The randomized number.
	 */
	private function randomize_stat( $value, $amt_to_vary, $min_allowed ) {

		// Fail if arguments are empty.
		if ( ! isset( $value ) || ! isset( $amt_to_vary ) || ! isset( $min_allowed ) ) {
			return false;
		}

		$value = (int) $value;

		// If $amt_to_vary is 0 then don't randomize anything. Return $value.
		if ( 0 === $amt_to_vary ) {
			return $value;
		}

		// Convert $amt_to_vary to a decimal to represent the percentage.
		$amt_to_vary = (int) $amt_to_vary / 100;

		// Make sure value is set to at least the minimum allowed value.
		$min_allowed = (int) $min_allowed;
		$value = $value >= $min_allowed ? $value : $min_allowed;

		// Calculate the lower threshhold for the randomized number.
		$min = $value - ( $value * $amt_to_vary );
		$min = $min >= $min_allowed ? $min : $min_allowed;

		// Calculate the upper threshold for the randomized number.
		$max = $value + ( $value * $amt_to_vary );
		$max = $max >= $min_allowed ? $max : $min_allowed;

		// Return a random number between $min and $max.
		return mt_rand( $min, $max );
	} // end randomize_stat

	/**
	 * Calculate and apply damage to the monster.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer $base_damage The base value to use for damage.
	 * @return array Array of any rewards earned by causing this damage.
	 */
	public function take_damage( $base_damage ) {

		// Fail if argument is empty.
		if ( empty( $base_damage ) ) {
			return false;
		}

		// If $base_damage is a positive number then get its negative value
		// for use with augment_stat.
		if ( $base_damage >= 1 ) {
			$base_damage = $base_damage * -1;
		}

		// Do the actual damage.
		$this->augment_stat( 'hp', $base_damage );

		// Return any rewards the player earned in the event the damage
		// caused to the monster caused it to be defeated.
		return $this->rewards_to_give;
	}

	/**
	 * Monster was defeated, so send out notifications and give rewards.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	private function be_defeated() {
		// Send the reward detail to the browser.
		$this->add_to_json( 'monsterDefeated',
			array( $this->name, $this->reward_exp, $this->reward_gold )
		);

		// Designate the rewards to be given to the player, but
		// do not award them yet. That is handled in the Combat class.
		$this->rewards_to_give['xp'] = $this->reward_exp;
		$this->rewards_to_give['gold'] = $this->reward_gold;
	}

	/**
	 * Populates an associative array with the monster's data.
	 *
	 * Returns an associative array of the monster's data so it can
	 * easily be saved to the DB and re-instantiated for the next turn.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array The monster data needed for saving to the DB.
	 */
	public function get_monster_data_for_saving() {
		return array(
			'name' => $this->name,
			'image' => $this->image,
			'hp' => $this->hp,
			'max_hp' => $this->max_hp,
			'attack' => $this->attack,
			'defense' => $this->defense,
			'reward_gold' => $this->reward_gold,
			'reward_exp' => $this->reward_exp,
		);
	}

	/**
	 * Populates and returns an associative array with only the monster data
	 * needed by the browser.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array The monster data needed by the browser.
	 */
	public function get_monster_data_for_browser() {
		return array(
			'name' => $this->name,
			'image' => $this->image,
			'hp' => $this->hp,
			'max_hp' => $this->max_hp,
		);
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
}
