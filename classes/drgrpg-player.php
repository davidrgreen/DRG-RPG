<?php
/**
 * Holds the DRGRPG_Player class.
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
 * Handle loading user meta into a player object and then allowing the game engine to
 * interact with it.
 *
 * @since 0.1.0
 */
class DRGRPG_Player {

	/**
	 * Data describing the current state of combat.
	 *
	 * If player is not in a battle then this is empty. Otherwise it holds
	 * info such as the combat round # and the data for all the enemies.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $saved_battle;

	/**
	 * User ID of the player.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $id;

	/**
	 * Name of the player.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * Room the player is currently within.
	 *
	 * Typically this will be set to an int equal to the post ID of the room the
	 * player is currently in, but the property is initially set to a string holding
	 * the name of the expected room the player will begin within. This way
	 * as long as a game admin creates a room with the title 'New Player Arrival'
	 * then they do not need to come here to change the default value of
	 * $current_room to be equal to the post ID of the room player's should
	 * start in. Everywhere else in the code sets his to an int though.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer|string
	 */
	protected $current_room = 'New Player Arrival';

	/**
	 * The physical strength of the player.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $str;

	/**
	 * The dexterity of the player.
	 *
	 * This is intended to affect accuracy eventually.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $dex;

	/**
	 * The intelligence of the player.
	 *
	 * This is intended to affect magic attack and magic defense.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $int;

	/**
	 * Maximum hit points a player can have / Full Health
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $max_hp;

	/**
	 * Current of hit points a player has remaining.
	 *
	 * Full health is represented by $hp being equal to $max_hp.
	 * Defeat in battle is triggered when $hp hits 0.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $hp;

	/**
	 * Maximum magic points a player can have.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $max_mp;

	/**
	 * Current number of magic points the player has remaining.
	 *
	 * $mp decreases as skills are used.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $mp;

	/**
	 * Attack power.
	 *
	 * Main factor in how much physical damage a player
	 * causes in combat during a normal attack.
	 * This is primarily determined by the currently equipped weapon.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $attack = 0;

	/**
	 * Defense power.
	 *
	 * Lowers physical damage received in combat. This is determined
	 * by the player's currently worn equipment. It is stored here to avoid
	 * looking at the equipment to calculate the total defense each turn.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $defense = 0;

	/**
	 * Gold the player has to buy items.
	 *
	 * Gold can be gained by defeating monsters, selling items, find
	 * gold when examining objects in a room, and as a quest reward.
	 * New players begin with 100 gold.
	 *
	 * @since 0.1.0
	 * @access protected
	 *  @var integer
	 */
	protected $gold = 100;

	/**
	 * Experience Points player has earned.
	 *
	 * XP is gained through defeating enemies.
	 * TODO: It needs to either increase player level or allow you to
	 * purchase stat levels with XP earned.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var integer
	 */
	protected $xp = 0;

	/**
	 * The levels player has earned in all current and former guilds.
	 *
	 * Players can rejoin guilds at the same level they were at when they
	 * left the guild, so this info needed to be stored separately from the
	 * current guild they're in.
	 * Expected format:
	 * $guild_levels[ (string) guild_name ] = array ( (int) guild_id, (int) guild_level );
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $guild_levels = array();

	/**
	 * The name of the current guild a player is a member of.
	 *
	 * This stores the name of the guild so it can be used to easily access
	 * the $guild_levels associative array via $guild_levels[ $current_guild ]
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	 */
	protected $current_guild = '';

	/**
	 * List of player's skills.
	 *
	 * Associative array of all skill levels a player has learned.
	 * The expected format is $skills[ (string) skill_name ] = (int)  skill_level;
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $skills = array();

	/**
	 * Skill being used in this round of combat.
	 *
	 * When a skill is used its name, type of effect, and calculated strength
	 * are stored in $active_skill to be used when the combat turn is executed.
	 * The expected format:
	 * $active_skill = array(
	 * 	(string) skill_name, (string) effect_type, (int) calculated_strength
	 * 	);
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $active_skill = array();

	/**
	 * List of minimum acceptable values for each stat.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $min_stat_levels = array(
		'hp' => 0,
		'max_hp' => 50,
		'mp' => 0,
		'max_mp' => 25,
		'str' => 1,
		'dex' => 1,
		'int' => 1,
		'attack' => 0,
		'defense' => 0,
		'gold' => 0,
		'xp' => 0,
	);

	/**
	 * List of items currently equipped in each slot.
	 *
	 * Associative array of item slots and the items they hold.
	 * Adding a new slot must be done in this array and in the JS
	 * within the DRGRPG.config.equipmentSlots array.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $equipped_items = array(
		'back' => '',
		'bodyarmor' => '',
		'boots' => '',
		'helmet' => '',
		'leggings' => '',
		'necklace' => '',
		'shield' => '',
		'weapon' => '',
	);

	/**
	 * List of quest flags for the player.
	 *
	 * Quest flags are basically variables used to keep track of
	 * the progress of a quest or record of an interaction with
	 * the environment. Values are positive integers.
	 * Expected format:
	 * $quest_flags = array ( (string) flag => (int) value, etc  );
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $quest_flags = array();

	/**
	 * List of items player holds in their inventory.
	 *
	 * Inventory items (and equipped items) are stored as an array of arrays.
	 * Once a DRGRPG_Item object has been instantiated it has its properties
	 * stored as an array in a new index of the $inventory array. This allows
	 * the properties of an item in the inventory to always be accessible without
	 * further database queries. Also, each instance of an item can have its
	 * properties randomized so two of the same item can have differing stats,
	 * and a crafting and item modification system is possible.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $inventory = array();

	/**
	 * List of notifications to show the player.
	 *
	 * List of notifications to be displayed to the player at the
	 * end of the turn using the in-game notification system.
	 * Expected format:
	 * notifications[] = array( (string) type, (string) message );
	 * See add_notification method for more information.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $notifications = array();

	/**
	 * List of achievements the player has earned.
	 *
	 * This does not hold each achievement's data, only a list of
	 * array index corresponding to the drgrpg_achievement post IDs
	 * the player has earned achievements for, with the value set to 1.
	 * Expected format:
	 * $achievements[ (int) post_id ] = 1;
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $achievements = array();

	/**
	 * List containing data of any new achievements earned this turn.
	 *
	 * This array holds the actual data of the newly earned achievements.
	 * This allows the game to send only the new achievement data to
	 * the browser instead of sending the entire list of achievements over again.
	 * Expected format:
	 * $new_achievements[ post_id ] = array( title, content );
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	 */
	protected $new_achievements = array();

	/**
	 * Timestamp of the last time the player contacted the server.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $last_access;

	/**
	 * Timestamp of this request.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	 */
	protected $this_access;

	/*
	 * NOTE: Below are several flags/booleans indicating whether different aspects
	 * of the player has changed. This is used by the engine to only send updated
	 * pieces of player data to the browser, reducing the burden on the user's
	 * data plan and reducing the data processing performed by the JavaScript.
	 *
	 * TODO: Possibly consolidate these into an array, but that might actually
	 * cause a minor (incredibly small) negative impact on performance due
	 * to the array lookup. Need to run benchmarks for comparison.
	 */

	/**
	 * Flag indicating whether the player's current guild or guild level has changed.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_guild_changed = false;

	/**
	 * Flag indicating whether the player's stats have changed.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_stats_changed = false;

	/**
	 * Flag indicating whether the player's skill levels have changed.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_skills_changed = false;

	/**
	 * Flag indicating whether the player has changed rooms or the
	 * room they are in has changed due to a room object action.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_moved = false;

	/**
	 * Flag indicating whether the player has recovered their health naturally
	 * this turn.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_healed = false;

	/**
	 * Flag indicating whether the player's inventory or equipped items have
	 * changed.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_items_changed = false;

	/**
	 * Flag indicating whether the player has had a quest flag added, removed,
	 * or a quest flag value changed.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $have_quest_flags_changed = false;

	/**
	 * Flag indicating whether the player has taken damage this turn.
	 *
	 * Players do not heal naturally if they have taken damage.
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var boolean
	 */
	protected $was_damaged = false;

	/**
	 * Setup the player object
	 *
	 * Get the meta data for the player and populate a DRGRPG_Player object.
	 * If this is the first time this user has played the game then populate
	 * the player object with the base stats of a new player.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer $user_id The user ID of the player.
	 * @return void
	 */
	public function __construct( $user_id ) {

		// Fail if no $user_id.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Record time of this access.
		$this->this_access = time();

		// Grab all the user's meta data in one go.
		$user_meta = get_user_meta( $user_id );
		// No meta data was found for this user, which means a user
		// with this $user_id does not exist. Fail. Return null so the engine
		// knows this player doesn't exist.
		if ( empty( $user_meta ) ) {
			return;
		}

		$this->id = (int) $user_id;

		// Possibly switch this to use the user's Display Name.
		$this->name = $user_meta['nickname'][0];

		// Drill down to the serialized drgrpg_player_stats array.
		$user_info = unserialize( $user_meta['drgrpg_player_stats'][0] );

		// If the user meta has a record of the HP being set before then use that.
		if ( isset( $user_info['hp'] ) ) {
			$this->hp = $this->initiate_stat( $user_info['hp'], $this->min_stat_levels['hp'] );
		} else {
			// HP was never set before, so this is a new player. Set HP equal to the
			// minimum max HP value, so the new player will start with full health.
			$this->hp = $this->min_stat_levels['max_hp'];
		}

		$this->max_hp = $this->initiate_stat( $user_info['max_hp'], $this->min_stat_levels['max_hp'] );

		if ( isset( $user_info['mp'] ) ) {
			$this->mp = $this->initiate_stat( $user_info['mp'], $this->min_stat_levels['mp'] );
		} else {
			// MP was never set before, so this is a new player. Set MP equal to the
			// minimum max MP value.
			$this->mp = $this->min_stat_levels['max_mp'];
		}

		$this->max_mp = $this->initiate_stat( $user_info['max_mp'], $this->min_stat_levels['max_mp'] );

		// Set all the other stats using the initiate_stat method and the values
		// designated in the $min_stat_levels array to ensure all stats
		// are set to valid values.
		$this->str = $this->initiate_stat( $user_info['str'], $this->min_stat_levels['str'] );
		$this->dex = $this->initiate_stat( $user_info['dex'], $this->min_stat_levels['dex'] );
		$this->int = $this->initiate_stat( $user_info['int'], $this->min_stat_levels['int'] );
		$this->attack = $this->initiate_stat( $user_info['attack'], $this->min_stat_levels['attack'] );
		$this->defense = $this->initiate_stat( $user_info['defense'], $this->min_stat_levels['defense'] );
		$this->xp = $this->initiate_stat( $user_info['xp'], $this->min_stat_levels['xp'] );
		$this->gold = $this->initiate_stat( $user_info['gold'], $this->min_stat_levels['gold'] );

		// The following are all set to empty arrays by default. If data for them
		// is found in the user meta then use that.
		if ( ! empty( $user_info['inventory'] ) ) {
			$this->inventory = $user_info['inventory'];
		}

		if ( ! empty( $user_info['equipped_items'] ) ) {
			$this->equipped_items = $user_info['equipped_items'];
		}

		if ( ! empty( $user_info['current_room'] ) ) {
			$this->current_room = $user_info['current_room'];
		}

		if ( ! empty( $user_info['quest_flags'] ) ) {
			$this->quest_flags = $user_info['quest_flags'];
		}

		if ( ! empty( $user_info['current_guild'] ) ) {
			$this->current_guild = $user_info['current_guild'];
		}

		if ( ! empty( $user_info['guild_levels'] ) ) {
			$this->guild_levels = $user_info['guild_levels'];
		}

		if ( ! empty( $user_info['skills'] ) ) {
			$this->skills = $user_info['skills'];
		}

		if ( ! empty( $user_info['achievements'] ) ) {
			$this->achievements = $user_info['achievements'];
		}

		if ( ! empty( $user_info['last_access'] ) ) {
			$this->last_access = (int) $user_info['last_access'];
		}

		// Need to pull the saved battle from user meta.
		$this->saved_battle = unserialize( $user_meta['drgrpg_current_battle'][0] );
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
	 * I need to clamp down on this and only allow specific setter functions.
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
		switch ( $property ) {
			// Prevent these properties from being set directly
			// if not done by this class.
			case 'id':
			case 'str':
			case 'dex':
			case 'int':
			case 'max_hp':
			case 'hp':
			case 'max_mp':
				break; // Don't touch it.

			// Need to make sure and keep current_room able
			// to be set even if the default case is removed later.
			case 'current_room':
			default:
				$this->$property = $value;
				break;
		}
	}

	/**
	 * Ensure the stat is set to an acceptable value.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param integer $value The base value used to set the stat.
	 * @param integer $min The mininum acceptable value the stat can be set to.
	 * @return integer The value to use to set the stat.
	 */
	private function initiate_stat( $value, $min ) {

		// Return false if arguments are empty. Arguments could be 0 and be valid,
		// so use isset instead of empty.
		if ( ! isset( $value ) && isset( $min ) ) {
			return (int) $min;
		}
		else if ( ! isset( $value ) && ! isset( $min ) ) {
			return 0;
		}

		// Use $value, type cast as an integer, if $value is both numeric
		// and greater than or equal to $min.
		if ( is_numeric( $value ) && (int) $value >= (int) $min ) {
				return (int) $value;
		} else {
			// Return $min as an integer if it's set and is numeric.
			if ( is_numeric( $min ) ) {
				return (int) $min;
			} else {
				// Set stat to 1 when all else fails.
				return 1;
			}
		}
	} // end initiate_stat

	/**
	 * Augment/change a stat's value.
	 *
	 * Use this instead of __set. __set blocks most attempts, plus
	 * augment_stat does sanity checks for things like minimum
	 * values and going unconscious when necessary.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $stat The stat being augmented.
	 * @param integer $amt How much to augment the stat. Can
	 *                      			be positive or negative..
	 * @return boolean Was this successful?
	 */
	public function augment_stat( $stat, $amt ) {

		// Fail if either argument is empty, $amt is not numeric, or
		// $stat is not already a property of the player object.
		if ( empty( $stat ) || empty( $amt ) ||
				! property_exists( $this, $stat ) || ! is_numeric( $amt ) ) {
			return false;
		}

		// Type cast $amt to an integer.
		$amt = (int) $amt;

		if ( 'hp' === $stat ) {

			// Keep track of the current HP value for comparison after
			// $amt is applied to the HP.
			$original_hp = $this->hp;

			$new_hp = $this->hp;
			$new_hp += $amt;

			// If adding $amt to the HP would result in a value less than 0,
			// such as in the event of being augmented for -20,
			// then set $new_hp to 0.
			$new_hp = $new_hp >= 0 ? $new_hp : 0;

			// Don't let HP be set to a value higher than the player's max HP.
			$new_hp = $new_hp <= $this->max_hp ? $new_hp : $this->max_hp;

			// Actually set player HP to the new value.
			$this->hp = $new_hp;

			// HP was lowered, so flag the player as being damaged.
			if ( $this->hp < $original_hp ) {
				$this->was_damaged = true;
			}

			/*
			TODO:
			if ( 0 === $new_hp ) {
				Probably need this later, but Combat currently
				handles it. When more consequences for hitting
				0 HP are added then this will be needed so you
				can go unconscious when a room object or status
				effect causes your HP to hit 0.
			}
			*/
		} else if ( 'mp' === $stat ) {

			// Keep track of the current MP value for comparison after
			// $amt is applied to MP.
			$original_mp = $this->mp;

			$new_mp = $this->mp;
			$new_mp += $amt;

			// If adding $amt to the MP would result in a value less than 0,
			// then set $new_mp to 0.
			$new_mp = $new_mp >= 0 ? $new_mp : 0;

			// Don't let MP be set to a value higher than the player's max MP.
			$new_mp = $new_mp <= $this->max_mp ? $new_mp : $this->max_mp;

			// Actually set player's MP to the new value.
			$this->mp = $new_mp;

			// MP was lowered, so flag the player as being damaged.
			if ( $this->mp < $original_mp ) {
				$this->was_damaged = true;
			}
		} else {
			// $min_stat_levels has a minimum value for this $stat.
			if ( isset( $this->min_stat_levels[ $stat ] ) ) {
				// Proceed if the resulting value is at least equal to the
				// minimum required value.
				if ( $this->$stat + $amt >= $this->min_stat_levels[ $stat ] ) {
					$this->$stat += $amt;
				} else {
					// Value would be too low, so set $stat to the minimum value.
					$this->$stat = $this->min_stat_levels[ $stat ];
				}
			} else {
				// If the augmentation will result in a value greater than 0
				// then use it.
				if ( $this->$stat + $amt > 0 ) {
					$this->$stat += $amt;
				} else {
					// When all else fails set it to 1.
					$this->$stat = 1;
				}
			}
		}

		// A stat has been changed (probably) so set this flag, causing
		// the browser to receive updated stat info.
		$this->have_stats_changed = true;

		return true;
	} // end augment_stat

	/**
	 * Potentially recover HP and MP each turn.
	 *
	 * If you've been damaged during the turn or already healed then
	 * you don't recover.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function heal_naturally() {
		if ( $this->hp < $this->max_hp
			&& false === $this->have_healed
			&& false === $this->was_damaged ) {
			$this->augment_stat( 'hp', 1 );
			$this->have_stats_changed = true;
		}

		if ( $this->mp < $this->max_mp
			&& false === $this->have_healed
			&& false === $this->was_damaged ) {
			$this->augment_stat( 'mp', 1 );
			$this->have_stats_changed = true;
		}
	}

	/**
	 * Get a list of player stats needed by the browser.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array Player data needed by the browser.
	 */
	public function get_player_stats() {
		return array(
			'max_hp' => $this->max_hp,
			'hp' => $this->hp,
			'max_mp' => $this->max_mp,
			'mp' => $this->mp,
			'str' => $this->str,
			'dex' => $this->dex,
			'int' => $this->int,
			'attack' => $this->attack,
			'defense' => $this->defense,
			'xp' => $this->xp,
			'gold' => $this->gold,
		);
	}

	/**
	 * Get an array of all the player's data needing to carry over to the next turn.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array Player data.
	 */
	public function get_player_data_for_save() {
		return array(
			'max_hp' => $this->max_hp,
			'hp' => $this->hp,
			'max_mp' => $this->max_mp,
			'mp' => $this->mp,
			'str' => $this->str,
			'dex' => $this->dex,
			'int' => $this->int,
			'attack' => $this->attack,
			'defense' => $this->defense,
			'xp' => $this->xp,
			'gold' => $this->gold,
			'inventory' => $this->inventory,
			'equipped_items' => $this->equipped_items,
			'current_room' => $this->current_room,
			'quest_flags' => $this->quest_flags,
			'current_guild' => $this->current_guild,
			'guild_levels' => $this->guild_levels,
			'skills' => $this->skills,
			'achievements' => $this->achievements,
			'last_access' => $this->this_access,
		);
	}


	/**
	 * Save player's data to user meta.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 */
	public function save_player() {
		update_user_meta( $this->id, 'drgrpg_player_stats', $this->get_player_data_for_save() );
	}

	/**
	 * Add a new item player's inventory.
	 *
	 * Add an array representing a new instance of an item to the
	 * player's inventory. This method requires more data saved to
	 * user meta than having inventory be an array of drgrpg_item
	 * post IDs, but it allows each item in inventory to have unique
	 * stats and potentially be manipulated by crafting skills.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param DRGRPG_Item $item Item object to be added.
	 * @return boolean Was item added successfully?
	 */
	public function add_item_to_inventory( $item ) {

		// Fail if argument is empty.
		if ( empty( $item ) ) {
			return false;
		}

		// Get an array of the item's data so it can be saved.
		$new_item = $item->get_item_data_as_array();

		// No data was returned so fail. The item probably
		// does not exist.
		if ( empty( $new_item ) ) {
			return false;
		}

		// Add the new item to the player's inventory.
		$this->inventory[] = $new_item;

		// Set this flag so the engine knows to send an updated inventory
		// list to the browser at the end of the turn.
		$this->have_items_changed = true;
	}

	/**
	 * Move an item from player's inventory to an equipment slot.
	 *
	 * The entire item's array of data is passed in as a parameter. I
	 * considered sending only the array index of the item, but
	 * decided to send the full array because 1) it is already available,
	 * and 2) it prevents mistakes happening if the order of the array
	 * some how changes.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $item_array Inventory item's array of data.
	 * @return boolean Was this successful?
	 */
	public function equip_item( $item_array ) {

		// Fail if argument is empty.
		if ( empty( $item_array ) ) {
			return false;
		}

		// Setup variables we'll be using in the method.
		$new_inventory = array();
		$found_item = false;

		// $item_array is an associative array so use foreach to loop
		// over it. If a $value is numeric then type cast it to ensure it's
		// an integer so there's no values of '1' instead of 1.
		foreach ( $item_array as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$item_array[ $key ] = (int) $value;
			}
		}

		// The equipment slot the item is moving into is already occupied, so
		// unequip the item using the unequip_item method.
		if ( ! empty( $this->equipped_items[ $item_array['type'] ] ) ) {
			$this->unequip_item( $this->equipped_items[ $item_array['type'] ] );
		}

		// Loop over the inventory array to find the item being equipped.
		for ( $i = 0, $len = count( $this->inventory ); $i < $len; $i++ ) {

			// If the item has not previously been found and it's a match
			// then we've found the item to equip.
			if ( false === $found_item && $item_array === $this->inventory[ $i ] ) {

				// Check whether the equipment slot exists.
				if ( isset( $this->equipped_items[ $item_array['type'] ] ) ) {
					$this->equipped_items[ $item_array['type'] ] = $item_array;

					// If the item has attack and/or defense then run
					// augment_stat to give player the boost from the item.
					if ( 0 !== $item_array['attack'] ) {
						$this->augment_stat( 'attack', $item_array['attack'] );
					}
					if ( 0 !== $item_array['defense'] ) {
						$this->augment_stat( 'defense', $item_array['defense'] );
					}
				}

				// Item was found, so set this flag to true. Instead of breaking out of
				// the loop we need to continue in order to build up the
				// $new_inventory array that will be filled with all the items left
				// in the player's inventory after removing the item being equipped.
				// With this set to true we can skip this block in the following
				// iterations due to this if block failing the false === $found_item check.
				$found_item = true;
			} else {
				// This isn't the item being equipped, so add it to the $new_inventory.
				$new_inventory[] = $this->inventory[ $i ];
			}
		} // end for loop

		// Set $inventory to the newly formed $new_inventory array.
		$this->inventory = $new_inventory;

		// Set this flag so the engine knows to send an updated inventory
		// list to the browser at the end of the turn.
		$this->have_items_changed = true;

		return true;
	} // end equip_item

	/**
	 * Move an item from an equipment slot into player's inventory.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $item_array Array of data for the item being unequipped.
	 * @return boolean Was this successful?
	 */
	public function unequip_item( $item_array ) {

		// Fail if argument is empty.
		if ( empty( $item_array ) ) {
			return false;
		}

		// $item_array is an associative array so use foreach to loop
		// over it. If a $value is numeric then type cast it to ensure it's
		// an integer so there's no values of '1' instead of 1.
		foreach ( $item_array as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$item_array[ $key ] = (int) $value;
			}
		}

		// There's nothing equipped in the equipment slot this item
		// should be in, or the item there does not match the item
		// we're trying to unequip, so fail.
		if ( empty( $this->equipped_items[ $item_array['type'] ] ) ||
			$this->equipped_items[ $item_array['type'] ] !== $item_array ) {
			return false;
		}

		// Empty the equipment slot. Don't need to save what was
		// in it because $item_array is an exact match to it.
		$this->equipped_items[ $item_array['type'] ] = '';

		// Remove any attack and defense boosts this equipment gave
		// the player.
		if ( 0 !== $item_array['attack'] ) {
			$this->augment_stat( 'attack', $item_array['attack'] * -1 );
		}

		if ( 0 !== $item_array['defense'] ) {
			$this->augment_stat( 'defense', $item_array['defense'] * -1 );
		}

		// Use array_unshift to insert the item as the first item in the
		// player's inventory. If the player changes their mind about
		// unequipping the item it's less annoying if the player can avoid
		// scrolling all the way through their inventory to find it.
		array_unshift( $this->inventory, $item_array );

		// Set this flag so the engine knows to send an updated inventory
		// list to the browser at the end of the turn.
		$this->have_items_changed = true;

		return true;
	} // end unequip_item

	/**
	 * Drop an item from player's inventory.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param array $item_array Array of data for the item being dropped.
	 * @return boolean Was item dropped?
	 */
	public function drop_item( $item_array ) {

		// Fail if argument is empty.
		if ( empty( $item_array ) ) {
			return false;
		}

		$new_inventory = array();
		$found_item = false;

		// $item_array is an associative array so use foreach to loop
		// over it. If a $value is numeric then type cast it to ensure it's
		// an integer so there's no values of '1' instead of 1.
		foreach ( $item_array as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$item_array[ $key ] = (int) $value;
			}
		}

		// Loop over the player's inventory. If it finds the item to drop
		// then set $found_item to true. If the item has already been found,
		// or if the item being checked is not the item to be dropped,
		// then add the item to the $new_inventory array. This will result
		// in a new array made up of every inventory item except the item
		// that was dropped.
		for ( $i = 0, $len = count( $this->inventory ); $i < $len; $i++ ) {
			if ( false === $found_item && $item_array === $this->inventory[ $i ] ) {
				$found_item = true;
			} else {
				$new_inventory[] = $this->inventory[ $i ];
			}
		}

		$this->inventory = $new_inventory;

		// Set this flag so the engine knows to send an updated inventory
		// list to the browser at the end of the turn.
		$this->have_items_changed = true;

		return true;
	} // end drop_item

	/**
	 * Check whether a player has a specific item in their inventory or
	 * equipment slots.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer $item_id ID of the item in question.
	 * @return boolean True if item found. False it not.
	 */
	public function has_item( $item_id ) {

		// If $item_id is not empty and is numeric then type cast it as an integer.
		// If not then the argument is not using the correct format. Fail.
		if ( ! empty( $item_id ) && is_numeric( $item_id ) ) {
			$item_id = (int) $item_id;
		} else {
			return false;
		}

		// Check equipment slots first since it's likely to be the shorter list.
		// Return true as soon as the item is found.
		foreach ( $this->equipped_items as $item ) {
			if ( ! empty( $item ) && $item['id'] === $item_id ) {
				return true;
			}
		}

		// Item was not in an equipment slot, so loop through the inventory list.
		for ( $i = 0, $len = count( $this->inventory ); $i < $len; $i++ ) {
			if ( $item_id === $this->inventory[ $i ]['id'] ) {
				return true;
			}
		}

		// Never returned true, meaning the item was never found. Check failed.
		return false;
	} // end has_item

	/*
	Potentially move away from having get_inventory() and
	get_equipped_items() in the future, but for now I want
	to build with the assumption that getting player inventory may become more
	complex in the future. With this I can change one function instead of
	everywhere that's calling it.
	*/

	/**
	 * Get a list of items in the player's inventory (not equipment list).
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array An array of player's inventory items.
	 */
	public function get_inventory() {
		return $this->inventory;
	}

	/**
	 * Get a list of player's equipped slots and what, if any, items are in them.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array An associative array of player's equipped slots.
	 */
	public function get_equipped_items() {
		return $this->equipped_items;
	}

	/**
	 * Set quest flag on player.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $flag Name of the quest flag.
	 * @param integer $value Value to use for the quest flag.
	 * @return boolean Was this successful?
	 */
	public function set_quest_flag( $flag, $value ) {

		// Fail if either argument is empty or $value isn't numeric.
		// Note that empty() is used and not isset() for checking $value
		// because we do not want to accept a $value of 0.
		if ( empty( $flag ) || empty( $value ) || ! is_numeric( $value ) ) {
			return false;
		}

		// Force $value to be an integer with type casting.
		$value = (int) $value;

		// Only set the quest flag if it doesn't exist or if the new value
		// is greater than the existing value. If a player needs to go
		// through a quest again they should remove_quest_flag to start
		// over from scratch on the quest. Being able to lower the quest
		// flag value would open the door to accidental loops in the quest.
		if ( empty( $this->quest_flags[ $flag ] ) ||
				$value > $this->quest_flags[ $flag ] ) {
			$this->quest_flags[ $flag ] = $value;

			// When a quest flag is changed there is a high chance of an
			// object or exit in the room changing, so this tells the engine to
			// send updated room data to the browser at the end of the turn.
			$this->have_quest_flags_changed = true;
		}
	}

	/**
	 * Remove a quest flag from the player.
	 *
	 * Do not set it to a value of 0. Completely remove it from the quest_flags array.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $flag Name of the quest flag to remove.
	 * @return void
	 */
	public function remove_quest_flag( $flag ) {

		// Fail if argument is empty.
		if ( empty( $flag ) ) {
			return;
		}

		unset( $this->quest_flags[ $flag ] );
	}

	/**
	 * Check whether the player has a quest flag set to a specific value.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $flag Name of the quest flag.
	 * @param integer $value Value checking for. Defaults to 1. A value of 0
	 *                        		will check for the absence of the quest flag when
	 *                        		$exact_match is set to true.
	 * @param boolean $exact_match  Whether to check for $value exactly matching
	 *                              $flag's value, or whether to check for flag's value being equal
	 *                              to than $value. Defaults to equal to or greater.
	 * @return boolean Whether the check passed or failed.
	 */
	public function has_quest_flag( $flag, $value = 1, $exact_match = false ) {

		// Fail if $flag is empty or $value is not numeric.
		if ( empty( $flag ) || ! is_numeric( $value ) ) {
			return false;
		}
		$value = (int) $value;

		// If exact_match is set to false and the quest flag's value is greater than
		// or equal to $value, then return true.
		if ( false === $exact_match && ! empty( $this->quest_flags[ $flag ] ) &&
			$this->quest_flags[ $flag ] >= $value ) {
			return true;
		} else if ( true === $exact_match ) {

			// If the quest flag exactly matches the $value specified, then return true.
			if ( ! empty( $this->quest_flags[ $flag ] ) &&
					$this->quest_flags[ $flag ] === $value ) {
				return true;
			} else if ( 0 === $value && empty( $this->quest_flags[ $flag ] ) ) {
				// If $value is set to 0 then check for the absence of the quest flag.
				// If the quest flag is not found then return true.
				return true;
			}
		} else {
			// Never returned true, so return false.
			return false;
		}
	} // end has_quest_flag

	/**
	 * Make player join a guild.
	 *
	 * If player has been a member of the guild before and left, the player will
	 * rejoin at the guild level they were at previously.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer| string $which_guild  The post ID or title of the guild.
	 * @return boolean Was the player successful in joining?
	 */
	public function join_guild( $which_guild ) {

		// Fail if player is already a member of a guild, or the argument is empty
		// or the agument is not either a string or number.
		if ( ! empty( $this->current_guild ) || empty( $which_guild ) ||
				( ! is_string( $which_guild ) && ! is_numeric( $which_guild ) ) ) {
			return false;
		}

		$guild = new DRGRPG_Guild( $which_guild );
		$guild_data = $guild->get_guild_data_as_array();

		// If $guild_data is empty then no data was found for the guild to be
		// joined, meaning it must not exist. Fail.
		if ( empty( $guild_data['id'] ) ) {
			return false;
		}

		// Set flag to true so the engine will send updated guild membership info
		// to the browser at the end of the turn.
		$this->have_guild_changed = true;

		// Joining or leaving a guild almost always happens in a guild hall room,
		// which will likely offer different options depending on whether a player
		// is a member is an active member of the guild. Set this flag to true
		// to send updated room data to the player.
		$this->have_moved = true;

		// Set current_guild to be the name of the guild being joined.
		$this->current_guild = $guild_data['name'];

		// No data exists in $guild_levels for this guild, so this is the first time
		// the player is joining. Set them to be level 1, send a notification to the
		// browser, and return true.
		if ( empty( $this->guild_levels[ $guild_data['name'] ] ) ) {
			$this->guild_levels[ $guild_data['name'] ] = array( $guild_data['id'], 1 );

			$joined_guild_message = sprintf(
				__( 'Congratulations. You are now a member of the %s Guild!', 'drgrpg' ),
				$guild_data['name']
			);

			/**
			 * Filter the notification shown upon joining a guild for the first time.
			 *
			 * @since 0.1.0
			 * @param string $message The message to be shown.
			 * @param string $guild_name The name of the guild being joined.
			 */
			$joined_guild_message = apply_filters( 'drgrpg_join_new_guild_message',
				$joined_guild_message, $guild_data['name']
			);

			$this->add_notification( 'guild', $joined_guild_message );

			return true;
		}

		// Data already existed in $guild_levels for this guild, so player is rejoining
		// the guild. No need to adjust the $guild_level data. Just send a notification
		// to the player's browser.
		$joined_guild_message = sprintf(
			__( 'You are once again a level %d member of the %s Guild!', 'drgrpg' ),
			$this->guild_levels[ $guild_data['name'] ][1],
			$guild_data['name']
		);

		/**
		 * Filter the notification shown upon rejoining a guild.
		 *
		 * @since 0.1.0
		 * @param string $message The message to be shown.
		 * @param string $guild_name The name of the guild being joined.
		 * @param string $guild_level The level player will be after rejoining.
		 */
		$joined_guild_message = apply_filters( 'drgrpg_join_old_guild_message',
			$joined_guild_message, $guild_data['name'],
			$this->guild_levels[ $guild_data['name'] ][1]
		);

		$this->add_notification( 'guild', $joined_guild_message );

		return true;
	} // end join_guild

	/**
	 * Remove player from their current guild while preserving their guild level data.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return boolean Did the player leave the guild?
	 */
	public function leave_guild() {

		// Not currently a member of a guild, so fail.
		if ( empty( $this->current_guild ) ) {
			return false;
		} else {

			$left_guild = $this->current_guild.

			// The guild level data is stored within $guild_levels so it can be
			// used if the player rejoins. Just need to empty $current_guild.
			$this->current_guild = '';

			// Set these flags to true to cause new guild and room data to be sent
			// to the browser at the end of the turn.
			$this->have_guild_changed = true;
			$this->have_moved = true;

			// Notify the player within the browser.
			$leave_guild_message = sprintf(
				__( 'You have left the %s Guild, but can rejoin in the future at the same level.', 'drgrpg' ),
				$left_guild
			);

			/**
			 * Filter the notification shown upon leaving a guild.
			 *
			 * @since 0.1.0
			 * @param string $message The message to be shown.
			 * @param string $guild_name The name of the guild being left.
			 */
			$leave_guild_message = apply_filters( 'drgrpg_leave_guild_message',
				$leave_guild_message, $left_guild
			);

			$this->add_notification( 'guild', $leave_guild_message );

			return true;
		}
	} // end leave_guild

	/**
	 * Increase the player's guild level to a specific level.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $guild_name The name of the guild.
	 * @param integer $new_guild_level  The new guild level for player to gain.
	 * @return boolean Was this successful?
	 */
	public function increase_guild_level( $guild_name, $new_guild_level ) {

		// Fail if either argument is empty, $guild_name is not a string, or
		// $new_guild_level is not numeric.
		if ( empty( $guild_name ) || ! isset( $new_guild_level ) ||
				! is_numeric( $new_guild_level ) || ! is_string( $guild_name ) ) {
			return false;
		}

		// Type cast $new_guild_level to ensure it is an integer.
		$new_guild_level = (int) $new_guild_level;

		// Fail if this would be setting the player to a guild level they already have
		// gained.
		if ( $this->guild_levels[ $guild_name ][1] >= $new_guild_level ) {
			return false;
		}

		// Set the player's guild level to the new level.
		$this->guild_levels[ $guild_name ][1] = $new_guild_level;

		// Prepare a notification to the browser informing player of the new level.
		$new_guild_level_message = sprintf(
			__( 'You have advanced to level %d in the %s Guild!', 'drgrpg' ),
			$this->guild_levels[ $guild_name ][1],
			$guild_name
		);

		/**
		 * Filter the notification shown when player earns a new guild level.
		 *
		 * @since 0.1.0
		 * @param string $message The message to be shown.
		 * @param string $guild_name The name of the guild.
		 * @param string $guild_level The guild level player just earned.
		 */
		$new_guild_level_message = apply_filters( 'drgrpg_new_guild_level_message',
			$new_guild_level_message,
			$guild_name,
			$this->guild_levels[ $guild_name ][1]
		);

		$this->add_notification( 'guild', $new_guild_level_message );

		// Set these flags to true to send updated guild and room data to the browser
		// at the end of the turn.
		$this->have_guild_changed = true;
		$this->have_moved = true;

		return true;
	} // end increase_guild_level

	/**
	 * Check whether player has a specific guild level.
	 *
	 * This checks all guild levels the player has ever achieved, not only the current
	 * guild level.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $guild_name Name of the guild.
	 * @param integer $guild_level Level checking for.
	 * @param boolean $exact_match Match exactly or checking if player's guild
	 *                             level is at least equal to $guild_level.
	 * @return boolean Check passed?
	 */
	public function has_guild_level( $guild_name, $guild_level, $exact_match = false ) {

		// Fail if either $guild_name or $guild_level is empty, $guild_name is
		// not a string, or $guild_level is not numeric.
		if ( empty( $guild_name ) || ! isset( $guild_level ) ||
			! is_numeric( $guild_level ) || ! is_string( $guild_name ) ) {
			return false;
		}

		// Use type casting to ensure $guild_level is an integer.
		$guild_level = (int) $guild_level;

		// If $guild_level is greater than 0 then it will check for the presence
		// of the guild level.
		if ( $guild_level > 0 ) {

			// Player has never been a member of the guild so can't have
			// the required guild level. Fail.
			if ( empty( $this->guild_levels[ $guild_name ] ) ) {
				return false;
			}

			// $exact_match is set to true so return true only if the player's
			// guild level exactly matches the specified $guild_level
			if ( true === $exact_match &&
				$this->guild_levels[ $guild_name ][1] === $guild_level ) {
				return true;
			} else if ( false === $exact_match &&
				// $exact_match is set to false, so pass as long as player's guild
				// level is equal to or greater than the specified $guild_level
				$this->guild_levels[ $guild_name ][1] >= $guild_level ) {
				return true;
			}
		} else {
			// If $guild_level is 0 then the check will pass if the player does not have
			// the specified guild level.
			if ( empty( $this->guild_levels[ $guild_name ] ) ||
					$this->guild_levels[ $guild_name ][1] < $guild_level ) {
				return true;
			}
		}

		// Never returned true, so must return false and fail.
		return false;
	} // end has_guild_level

	/**
	 * Check whether the player is currently in the specified guild.
	 *
	 * Check whether the player is currently in the specified guild, or if you
	 * pass false to the second argument you can check to confirm the player
	 * is not in the guild currently. Note, this is only checking the current guild. It
	 * does not take into account the player having joined the guild and left.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $guild_name Name of the guild.
	 * @param boolean $checking_for_in True if checking to see if player is in the
	 *                                   guild. False if checking to confirm the player is not.
	 * @return boolean Whether the check passed or failed.
	 */
	public function currently_in_guild( $guild_name, $checking_for_in = true ) {

		// No $guild_name indicated, so fail.
		if ( empty( $guild_name ) ) {
			return false;
		}

		// If $checking_for_in is false and player is not currently a member of the
		// guild then return true.
		if ( false === $checking_for_in && $this->current_guild !== $guild_name ) {
			return true;
		} else if ( true === $checking_for_in && $this->current_guild === $guild_name ) {
			// If $checking_for_in is true and player is currently a member of the guild
			// then return true.
			return true;
		}

		// Never returned true, so must be false.
		return false;
	}

	/**
	 * Mark a skill for use during combat.
	 *
	 * If the player has the skill and enough MP then this will calculate
	 * the strength of the skill this use, taking into account skill levels and
	 * other factors, and will set $active_skill in the DRGRPG_Player object to
	 * hold an array representing the calculated strength and all other data
	 * needed to execute the skill during combat.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $skill_name Name of the skill being used.
	 * @return boolean Was this skill used successfully?
	 */
	public function use_skill( $skill_name ) {

		// Fail if no $skill_name or player doesn't have the skill they are
		// trying to use, or if they have already used a skill this turn.
		if ( empty( $skill_name ) || empty( $this->skills[ $skill_name ] ) ||
				! empty( $this->active_skill ) ) {
			return false;
		}

		$skill = new DRGRPG_Skill( $skill_name );

		// This will get the skill's name, type of effect, and the strength of
		// the skill during this use. Note if the skill has strength that varies
		// each use then this data will already take that into account.
		$skill_data = $skill->get_skill_data_as_array();

		// This skill doesn't exist, or has no combat effect, or player
		// does not have enough MP, sp fail.
		if ( empty( $skill_data ) || 'none' === $skill_data['effect'] ||
			$this->mp < $skill_data['cost'] ) {
			return false;
		}

		// Do not activate the skill or reduce player's MP right now. Wait until
		// the combat turn is executed so MP can be reduced at the moment
		// of use. Store the array of data representing the strength of this
		// use of the skill in $active_skill for the combat class to access later.
		$this->active_skill = $skill_data;
	}

	/**
	 * Teach player a new skill.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $skill_name Name of the new skill.
	 * @return boolean Was a new skill learned?
	 */
	public function add_skill( $skill_name ) {

		// Fail if the argument is empty.
		if ( empty( $skill_name ) ) {
			return false;
		}

		// If player already knows this skill then fail.
		if ( ! empty( $this->skills[ $skill_name ] ) ) {
			return false;
		}

		$new_skill = new DRGRPG_Skill( $skill_name );
		$new_skill_data = $new_skill->get_skill_data_as_array();

		// If the new instance of DRGRPG_Skill returned no data
		// in $new_skill_data then the skill must not exist. Fail.
		if ( empty( $new_skill_data ) ) {
			return false;
		}

		// Set the new skill to be level 1.
		$this->skills[ $new_skill_data['name'] ] = 1;

		// Prepare a notification for the browser notifiying the player of the new skill.
		$new_skill_message = sprintf(
			__( 'You\'ve learned the %s skill!', 'drgrpg' ), $new_skill_data['name']
		);

		/**
		 * Filter the notification shown upon learn a new skill.
		 *
		 * @since 0.1.0
		 * @param string $message The message to be shown.
		 * @param string $guild_name The name of the skill.
		 */
		$new_skill_message = apply_filters( 'drgrpg_new_skill_message',
			$new_skill_message, $new_skill_data['name']
		);

		$this->add_notification( 'skill', $new_skill_message );

		// Set flag to true so the engine knows to send an updated list
		// of player skills to the browser at the end of the turn. Also need
		// to update the room just in case the available actionsd changed.
		$this->have_skills_changed = true;
		$this->have_moved = true;
	}

	/*
	TODO: Add increase_skill_level
	*/

	/**
	 * Get list of player's skills and skill levels.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array List of player skills.
	 */
	public function get_skills() {
		return $this->skills;
	}

	/**
	 * Add notification to be displayed in the browser.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string $type Used in specifying a CSS class for the notification.
	 * @param string $message The message that will be displayed.
	 * @return void
	 */
	private function add_notification( $type, $message ) {

		// Fail if arguments are empty.
		if ( empty( $type ) || empty( $message ) ) {
			return;
		}

		$this->notifications[] = array( $type, $message );
	}

	/**
	 * Get list of notifications to be displayed in the browser.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array List of notifications.
	 */
	public function get_notifications() {
		return $this->notifications;
	}

	/**
	 * Award player a new achievement.
	 *
	 * @since 0.1.0
	 * @access public
	 * @param integer $achievement_id  Post ID of the achievement.
	 * @return boolean True if achievement awarded. False is not.
	 */
	public function award_achievement( $achievement_id ) {

		// Don't waste processing time if player already has this achievement
		// or if $achievement_id is empty or not numeric. Fail.
		if ( empty( $achievement_id ) || ! is_numeric( $achievement_id ) ||
			! empty( $this->achievements[ $achievement_id ] ) ) {
			return false;
		}

		// Type cast $achievement_id to ensure it is an integer.
		$achievement_id = (int) $achievement_id;

		// This will be passed to WP_Query to tell it
		// to get only the achievement with this specific post id( $achievement_id),
		// but only if it's been published.
		$args = array(
			'post_type' => 'drgrpg_achievement',
			'post_status' => 'publish',
			'p' => $achievement_id,
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			// Loop through the posts returned by the query.
			while ( $query->have_posts() ) {
				$query->the_post();

				// Mark achievement as earned.
				$this->achievements[ $achievement_id ] = 1;

				// Set the title and content to the browser, so
				// the achievements list in-browser is updated
				// without downloading the entire achievements
				// list again.
				$this->new_achievements[ $achievement_id ] = array(
					get_the_title(),
					get_the_content(),
				);

				wp_reset_postdata();

				return true;
			}
		} // end if have_posts

		wp_reset_postdata();

		// Achievement didn't exist so fail.
		return false;
	}

	/**
	 * Get list of achievements player has earned this turn.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array List of achievements player earned this turn.
	 */
	public function get_new_achievements() {
		return $this->new_achievements;
	}

	/**
	 * Get a list of all achievements.
	 *
	 * Get an array of all achievements. If player has earned an achievement
	 * already then display the achievement's content as well as title. If it
	 * has not been earned then only display the title.
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array List of achievements.
	 */
	public function get_all_achievements() {
		$allAchievements = array();

		// Arguments for a query to grab all the achievements
		// that have been created and published (up to 500 of them anyway).
		$args = array(
			'post_type' => 'drgrpg_achievement',
			'post_status' => 'publish',
			'posts_per_page' => '500',
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			// Loop over all the posts returned by the query.
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_ID = get_the_ID();

				if ( ! empty( $this->achievements[ $post_ID ] ) ) {
					// Player has already earned the achievement. Show the title
					// and the content.
					$allAchievements[ $post_ID ] = array(
						get_the_title(),
						get_the_content(),
					);
				} else {
					// Unearned achievement. Just show the achievement's title.
					$allAchievements[ $post_ID ] = array( get_the_title(), null );
				}
			} // end while loop
		} // end if have_posts

		wp_reset_postdata();

		return $allAchievements;
	}
} // end DRGRPG_Player class
