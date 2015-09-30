<?php
/**
 * Holds the DRGRPG_Item class.
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
 * Handles loading the post meta for a drgrpg_item post and putting it into
 * a object for the game to interact with.
 *
 * @since 0.1.0
 */
class DRGRPG_Item {

	/**
	 * Post ID of the drgrpg_item post being used for this item object.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $id;

	/**
	 * Name of the item.
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * Description of the item.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $description;

	/**
	 * Type of item - helmet, weapon, etc.
	 *
	 * The type of item primarily determines what equipment slot is used.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $type;

	/**
	 * Number of points this item boosts the player's attack (if any)
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $attack;

	/**
	 * Number of points this item boosts the player's defense (if any)
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $defense;

	/**
	 * The type of effect this item causes.
	 *
	 * Applicable for items being used.
	 * TODO: Make this work. Items don't yet support effects.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $effect;

	/**
	 * Setup the item object so it's ready to interact with.
	 *
	 * Get the meta data for the item and populate a DRGRPG_Item object
	 * if $which_item is found to refer to a valid item in the database. Or
	 * create a DRGRPG_Item object based on the array of data passed to
	 * the constructor.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer|string|array $which_item The post ID or title of the
	 *              room to load. If an array is passed in then that will be used to
	 *              populate the item's properties instead of consulting the DB.
	 * @return void
	 */
	public function __construct( $which_item ) {

		// No argument passed to constructor, so fail.
		if ( empty( $which_item ) ) {
			return false;
		}

		// If an array is passed then populate the item object with
		// that data instead of making a DB lookup. Validate the data of course.
		if ( is_array( $which_item ) ) {
			$this->id = ! empty( $which_item['id'] ) ?
				$which_item['id'] : 0;
			$this->name = ! empty( $which_item['name'] ) ?
				$which_item['name'] : 'Unknown Item';
			$this->type = ! empty( $which_item['type'] ) ?
				$which_item['type'] : 'Junk';

			// Use the initiate_stat method to confirm the data contained
			// within the array meets the minimum stat value requirements
			// outlined in the $min_stat_levels class property.
			$this->attack = $this->initiate_stat( $which_item['attack'], 0, 0 );
			$this->defense = $this->initiate_stat( $which_item['defense'], 0, 0 );
		}

		// Constructor was passed a string that isn't numeric, so check to see if
		// a post in the drgrpg_item CPT exists with a title matching the string.
		// If so, set $which_item to its post id.
		if ( is_string( $which_item ) && ! is_numeric( $which_item ) ) {
			$which_item = get_page_by_title( $which_item, OBJECT, 'drgrpg_item' )->ID;
		}

		// $which_item is is a number, or can be type cast to a number, so
		// see if meta data exists for a monster post with an id of $which_item
		if ( is_numeric( $which_item ) ) {

			// Type cast $which_item with (int) to make sure it's an integer.
			$which_item = (int) $which_item;

			// Set name to the title of the specified drgrpg_item post.
			$this->name = get_the_title( $which_item );

			// If name is empty then get_the_title didn't find a post
			// with an ID of $which_item. That must mean this item
			// does not exist. Return null.
			if ( empty( $this->name ) ) {
				return;
			}

			// Grab the post meta from the DB related to this item.
			$item_data = get_post_meta( $which_item );

			// Post meta was found, so use it to populate the item's stats.
			if ( ! empty( $item_data ) ) {
				$this->id = $which_item;
				$this->type = $item_data['_drgrpg_item_type'][0];

				// The item's stats are randomized using initiate_stat,
				// so each instance of the item can have different stats.
				// NOTE: Currently passing a variability argument of 0 so
				// the stats of each item instance are the same.
				$this->attack = $this->initiate_stat(
					$item_data['_drgrpg_item_attack'][0], 0, 0
				);
				$this->defense = $this->initiate_stat(
					$item_data['_drgrpg_item_defense'][0], 0, 0
				);
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
	 * @param  string $property The name of the property you want the value of.
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
	 * For now I've set this to always fail. May be removed in the future.
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
		$this->$property = $value;
	}

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
	 * @param integer $amt_to_vary Represents the percentage up and down the stat
	 *                           value can be when randomized. A $variance of 5 would mean 5%.
	 * @param integer $min_allowed The mininum acceptable value.
	 * @return int The randomized number
	 */
	private function randomize_stat( $value, $amt_to_vary, $min_allowed ) {
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
	 * Populates and returns an associative array with item's data.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array  The item's data in an associative array.
	 */
	public function get_item_data_as_array() {
		return array(
			'name' => $this->name,
			'id' => $this->id,
			'type' => $this->type,
			'attack' => $this->attack,
			'defense' => $this->defense,
		);
	}
} // end DRGRPG_Item class
