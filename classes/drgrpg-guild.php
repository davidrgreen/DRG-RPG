<?php
/**
 * Holds the DRGRPG_Guild class.
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
 * Handles loading the post meta for a drgrpg_guild post and putting
 * it into a DRGRPG_Guild object for the game to interact with.
 *
 * @since 0.1.0
 */
class DRGRPG_Guild {

	/**
	 * Post ID of the drgrpg_guild post being used for this guild object
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $id;

	/**
	 * Name of the guild
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * Description of the guild. Not shown in game currently.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $description;

	/**
	 * Setup the guild so it's ready to interact with.
	 *
	 * Currently just loads in info about the guild. Joining is
	 * handled within the DRGRPG_Player class.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer|string $which_guild The post ID or title of the guild to load.
	 * @return void
	 */
	public function __construct( $which_guild ) {

		// No argument passed to constructor, so fail.
		if ( empty( $which_guild ) ) {
			return false;
		}

		// Constructor was passed a string that isn't numeric, so check to see if
		// a post in the guild CPT exists with a title matching the string.
		// If so, set $which_guild to its post id.
		if ( is_string( $which_guild ) && ! is_numeric( $which_guild ) ) {
			$which_guild = get_page_by_title( $which_guild, OBJECT, 'drgrpg_guild' )->ID;
		}

		// $which_guild is is a number, or can be type cast to a number, so
		// see if meta data exists for a guild post with an id of $which_guild.
		if ( is_numeric( $which_guild ) ) {
			$which_guild = (int) $which_guild;
			$this->name = get_the_title( $which_guild );

			// If name is empty then get_the_title didn't find a post
			// with an ID of $which_guild. That must mean this guild
			// does not exist. Return null.
			if ( empty( $this->name ) ) {
				return;
			}

			$guild_data = get_post_meta( $which_guild );

			// Found post meta (guild_data) for this guild, so populate the class
			// properties, performing validation in the process.
			if ( ! empty( $guild_data ) ) {
				$this->id = $which_guild;
				$this->description = $guild_data['_drgrpg_guild_description'][0];
			}
		} else {
			// Was not a valid guild post ID or title, so return null.
			return;
		}
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
	 * __set magic method. Set a property with the given value.
	 *
	 * I probably need to clamp down on this and only allow specific
	 * setter functions, but right now there are no circumstances a
	 * guild property will be set outside of the guild object.
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
	 * Grab the guild data in a single array
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array All the guild data
	 */
	public function get_guild_data_as_array() {
		return array(
			'name' => $this->name,
			'id' => $this->id,
		);
	}
}
