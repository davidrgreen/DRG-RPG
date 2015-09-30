<?php
/**
 * Holds the DRGRPG_Skill class.
 *
 * @package DRGRPG
 * @author David Green <david@davidrg.com>
 * @license GPL-2.0
 * @link https://github.com/davidrgreen/DRGRPG
 * @copyright 2015 David Green
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Holds all the functions needed for loading a skill from the database
 * and calculating strength when used.
 *
 * @since 0.1.0
 */
class DRGRPG_Skill {

	/**
	 * The post ID of the drgrpg_skill post being used to construct this skill object.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $id;

	/**
	 * The name of the skill.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * A description of the skill.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $description;

	/**
	 * The type of effect the skill causes.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $effect;

	/**
	 * The number of MP using the skill requires.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $cost;

	/**
	 * The base strength of the skill.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $strength;

	/**
	 * What percentage up/down the strength may be each use.
	 *
	 * Variability of 5 would represent 5%.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $variability;

	/**
	 * Setup the skill so it's ready to use.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer|string $which_skill The post ID or title of the skill to load.
	 * @return void
	 */
	public function __construct( $which_skill ) {

		// No argument passed to constructor, so fail.
		if ( empty( $which_skill ) ) {
			return false;
		}

		// Constructor was passed a string that isn't numeric, so check to see if
		// a post in the skill CPT exists with a title matching the string.
		// If so, set $which_skill to its post id.
		if ( is_string( $which_skill ) && ! is_numeric( $which_skill ) ) {
			$which_skill = get_page_by_title( $which_skill, OBJECT, 'drgrpg_skill' )->ID;
		}

		// $which_skill is is a number, or can be type cast to a number, so
		// see if meta data exists for a room post with an id of $which_skill.
		if ( is_numeric( $which_skill ) ) {

			// Type cast $which_monster with (int) to make sure it's an integer.
			$which_skill = (int) $which_skill;

			// Set name to the title of the specified drgrpg_skill post.
			$this->name = get_the_title( $which_skill );

			// If name is empty then get_the_title didn't find a post
			// with an ID of $which_skill. That must mean this skill
			// does not exist. Return null.
			if ( empty( $this->name ) ) {
				return;
			}

			$skill_data = get_post_meta( $which_skill );

			// Post meta was found, so use it to populate the skill's properties.
			if ( ! empty( $skill_data ) ) {
				$this->id = $which_skill;
				$this->effect = (string) $skill_data['_drgrpg_skill_effect'][0];
				$this->strength = (int) $skill_data['_drgrpg_skill_strength'][0];
				$this->cost = (int) $skill_data['_drgrpg_skill_cost'][0];
				$this->variability = (int) $skill_data['_drgrpg_skill_variability'][0];
			}
		} else {
			// Were not given a valid skill title or post ID, so return null.
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
	 * Get the data for a use of this skill.
	 *
	 * Gets the data for a use of the skill, not the data entered into the
	 * CPT within the WordPress admin. This takes into account the
	 * proper factors for calculating strength.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array An array of data representing a use of the skill.
	 */
	public function get_skill_data_as_array() {
		return array(
			'name' => $this->name,
			'effect' => $this->effect,
			'cost' => $this->cost,
			'strength' => $this->get_calculated_strength(),
		);
	}

	/**
	 * Calculate the strength of a use of this skill.
	 *
	 * Take into account variability, so using the same skill
	 * two times will not necessarily have exactly the same strength.
	 * TODO: This should factor in a boost based on skill level.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return integer The calculated strength of this use of the skill.
	 */
	public function get_calculated_strength() {
		// If the skill's variability is 0 then it always has the same strength.
		// Return the skill's strength value and be done with it.
		if ( 0 === $this->variability ) {
			return $this->strength;
		}

		// Convert $amt_to_vary to a decimal to represent the percentage.
		$amt_to_vary = $this->variability / 100;

		// Calculate the lower threshhold for the randomized number.
		$min = $this->strength - ( $this->strength * $amt_to_vary );
		$min = $min >= 0 ? $min : 0;

		// Calculate the upper threshold for the randomized number.
		$max = $this->strength + ( $this->strength * $amt_to_vary );
		$max = $max >= 0 ? $max : 0;

		// Return a random number between $min and $max.
		return mt_rand( $min, $max );
	} // end get_calculated_strength
} // end DRGRPG_Skill class
