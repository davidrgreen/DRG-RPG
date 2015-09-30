/**
 * The main JS for the DRG RPG plugin. Control the interface and handle
 * processing data sent to and received from the server.
 *
 * @summary Main JS for the DRG RPG plugin.
 *
 * @since 0.1.0
 * @package DRGRPG
 * @requires jQuery.js
 * @copyright 2015 David Green
 * @license GPL2
 */

/**
 * Main game object.
 *
 * The DRGRPG variable uses the revealing module pattern in order to treat
 * most of its code as private, selectively revealing properties and methods
 * as public in its return statement.
 *
 * @access public
 * @param {object} $ The jQuery object.
 * @return {object} The properties and methods being revealed as public.
 */
var DRGRPG = ( function( $ ) {
	// Need to test in IE 8/9 to make sure the code works with strict mode.
	"use strict";

	/**
	 * 'Private' vars and methods
	 */

	var  initialized = false,
		heartBeat, // Reference to the timeout used to delay turns.
		turnTimeout = 3000, // Time in milliseconds between turns.
		config = {
			constants: {
				'processingBarHeight': 50,
			},
			playerStatsToShow: [
				// Hiding these stats until I flesh out the player leveling system.
				/*'str',
				'dex',
				'int',*/
				'attack',
				'defense',
				'xp',
				'gold',
			 ],
			language: {
				'mainMenu_Map': 'Map',
				'mainMenu_Combat': 'Combat',
				'mainMenu_Items': 'Items',
				'mainMenu_Skills': 'Skills',
				'mainMenu_Achievements': 'Achievements',
				'fleeButton': 'Flee Battle',
				'huntMonsters': 'Hunt for Monsters',
				'noMonstersToHunt': 'Nothing to Hunt',
				'achievementsHeader': 'Your Achievements',
				'haveNewAchievements': '{{number}} New Achievements!',
				'unearnedAchievement': '---',
				'skillsHeader': 'Your Skills',
				'guildLevelLabel': 'Rank {{level}} {{name}}',
				'roomObjects_examine_header': 'Examine:',
				'roomObjects_action_header': 'Take Action:',
				'roomObjects_npc_header': 'Talk To:',
				'roomObjects_exits_header': 'Move:',
				'entireRoom': 'entire room',
				'roomObjectList_examine_header': 'Examine:',
				'roomObjectList_examine_description': 'Examining ',
				'roomObjectList_action_header': 'Take Action:',
				'roomObjectList_action_description': 'Attempting action ',
				'roomObjectList_npc_header': 'Talk to:',
				'roomObjectList_npc_description': 'Talking to ',
				'pauseGame': 'Pause Game',
				'unpauseGame': 'Unpause Game',
				'playerHPLabel': 'HP: ',
				'playerMPLabel': 'MP: ',
				'playerStats_str': 'STR: ',
				'playerStats_dex': 'DEX: ',
				'playerStats_int': 'INT: ',
				'playerStats_attack': 'Attack: ',
				'playerStats_defense': 'Defense: ',
				'playerStats_xp': 'XP: ',
				'playerStats_gold': 'Gold: ',
				'enemyHPLabel': 'HP: ',
				'dismissNotification': 'click to dismiss this notice',
				'processingActions': 'Processing actions...Minimum wait between turns of 3 seconds',
				'processingError': 'Error contacting server. Attempting again...',
				'combatRecapTitle': 'Recap of Previous Battle:',
				'noCombatResultsYet': 'No battle results yet received since you loaded the game.',
				'monsterDefeated': [
					'{{name}} was defeated. You receive {{xp}} XP and {{gold}} gold.',
					'You beat {{name}} and receive {{xp}} XP and {{gold}} gold for your violence...I mean efforts.'
				],
				'playerDefeated': 'You were defeated by {{monster}} but manage to escape.',
				'playerFleeBattle': 'You flee the battle!',
				'dropConfirmation': 'Are you sure you want to drop your ',
				'emptyInventory': 'You don\'t have any items in your inventory.',
				'inventoryTitle': 'Your Inventory',
				'equipmentSlotsTitle': 'Your Equipment Slots',
				'equipmentSlotLabels_helmet': 'Helmet: ',
				'equipmentSlotLabels_necklace': 'Necklace: ',
				'equipmentSlotLabels_bodyarmor': 'Bodyarmor: ',
				'equipmentSlotLabels_back': 'Back: ',
				'equipmentSlotLabels_weapon': 'Weapon: ',
				'equipmentSlotLabels_shield': 'Shield: ',
				'equipmentSlotLabels_leggings': 'Leggings: ',
				'equipmentSlotLabels_boots': 'Boots: ',
				'itemLabels_attack': 'Attack: ',
				'itemLabels_defense': 'Defense: ',
				'unequipItemLink': 'unequip',
				'equipItemLink': 'equip',
				'dropItemLink': 'drop',
			},
		},
		pendingTranslation = null,
		first_turn = true,
		executingTurn = false,
		showingProcessingBar = false,
		currentProcessingBarMessage = 'processingActions',
		cached = {}, // Cached selectors.
		turnsSinceLastAction = 0,
		paused = false,
		currentlyViewing = 'Room', // Determine what is shown in the lookingAt area.
		interactingWith = '', // The description of the room object last interacted with.
		actionsToTake = [ [ 'system', 'first-turn' ] ],
		actionsTaken = [],
		// Expected format for mainMenu: [ "button text", "action", "argument", "id", "class(es)" ]
		mainMenu = [
			[ 'Map', 'showRoom', '', 'drgrpg-mainMenu__map',
				'drgrpg-mainMenu__button drgrpg-mainMenu__button--active drgrpg-mainMenu__map' ],
			[ 'Combat', 'showCombat', '', 'drgrpg-mainMenu__combat',
				'drgrpg-mainMenu__button drgrpg-mainMenu__combat' ],
			[ 'Items', 'showItems', '', 'drgrpg-mainMenu__items',
				'drgrpg-mainMenu__button drgrpg-mainMenu__items' ],
			[ 'Skills', 'showSkills', '', 'drgrpg-mainMenu__skills',
				'drgrpg-mainMenu__button drgrpg-mainMenu__skills' ],
			[ 'Achievements', 'showAchievements',
				'', 'drgrpg-mainMenu__achievements', 'drgrpg-mainMenu__button drgrpg-mainMenu__achievements' ],
		],
		player = {}, // Hold the player data.
		room = {},
		combat = {},
		combatResults = [], // Holds messages related to the last battle.
		equipmentSlots = [ 'helmet', 'necklace', 'bodyarmor', 'back', 'weapon', 'shield',
		'leggings', 'boots' ],
		notifications = [],
		unviewedAchievements = {},
		hooks = {};

	/**
	 * Cache element selectors in order to improve performance.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var cacheSelectors = function() {
		cached.playerStats = document.getElementById( 'drgrpg-playerStats' );
		cached.$playerStats = $( cached.playerStats );
		cached.mainMenu = document.getElementById( 'drgrpg-mainMenu' );
		cached.$mainMenu = $( cached.mainMenu );
		cached.$playerIdentity = $( document.getElementById( 'drgrpg-playerIdentity' ) );
		cached.$body = $( document.getElementsByTagName( 'body' )[0] );
		cached.$lookingAt = $( document.getElementById( 'drgrpg-lookingAt' ) );
		cached.notifications = document.getElementById( 'drgrpg-notifications' );
		cached.$notifications = $( cached.notifications );
		cached.$processingBar = $( document.getElementById( 'drgrpg-processingBar' ) );
	};

	/**
	 * Add any elements there are missing and cache their selectors.
	 *
	 * An element could be missing due to the game admin not using every available
	 * shortcode to insert the interface elements. This will simply insert any missing
	 * elements so as long as the admin inserted the main game window shortcode
	 * this will make sure the page has all the elements it needs to run.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var addMissingElements = function() {
		if ( 0 === cached.$mainMenu.length ) {
			cached.$lookingAt.before( '<div id="drgrpg-mainMenu" class="drgrpg-mainMenu"></div>' );
			cached.$mainMenu = $( document.getElementById( 'drgrpg-mainMenu' ) );
		}

		if ( 0 === cached.$playerStats.length ) {
			 cached.$lookingAt.after( '<br><div id="drgrpg-playerInfo" class="drgrpg-playerInfo"><div id="drgrpg-playerIdentity" class="drgrpg-playerIdentity"></div><div id="drgrpg-playerStats" class="drgrpg-playerStats">Loading player stats...</div></div>' );
			cached.$playerStats = $( document.getElementById( 'drgrpg-playerStats' ) );
			cached.$playerIdentity = $( document.getElementById( 'drgrpg-playerIdentity' ) );
		}
	};

	/**
	 * Fill any remaining elements needing text.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	var fillElementsWithText = function() {
		cached.$processingBar.html( getText( 'processingActions' ) );
	};

	/**
	 * Attempt to contact the server and execute a turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	var executeTurn = function() {
		// Prevent having two heartbeats.
		clearTimeout( heartBeat );
		heartBeat = null;
		executingTurn = true;

		// IDEA: Possibly contact the server every 5 turns to make sure
		// there's no update of some sort waiting on you. That isn't
		// needed currently, but if you can later interact with other players
		// it will be needed.
		if ( paused ||
			( 0 === actionsToTake.length &&
				player.hp >= player.max_hp &&
				player.mp >= player.max_mp &&
				! combat.round ) ) {
			turnsSinceLastAction += 1;
			heartBeat = setTimeout( executeTurn, turnTimeout );
			executingTurn = false;
			// Skip contacting the server if there are no
			// commands to send and no need to restore our HP.
			return;
		}

		// Copy actionsTaken to actionsToTake so at the end of a
		// successful ajax request only the actions sent to the server
		// are removed from actionsToTake, leaving behind any
		// actions the player added while the server was processing
		// the request.
		actionsTaken = actionsToTake.slice( 0 );
		$.ajax( infoFromServer.siteURL + '/game/api/turn/', {
			type: 'POST',
			success: function( data ) {
				// console.log( data );
				processDataReceived( data );
				hideProcessingBar();
				removeActionsTaken();
				turnsSinceLastAction = 0;

				heartBeat = setTimeout( executeTurn, turnTimeout );
				first_turn = false;
				if ( pendingTranslation ) {
					processTranslation();
				}
				doAction( 'successfulTurn' );
			},
			error: function() {
				changeProcessingBarMessage( 'processingError' );
				showProcessingBar( 'processingError' );
				heartBeat = setTimeout( executeTurn, turnTimeout );
				doAction( 'failedTurn' );
			},
			data: {
				'action': actionsToTake,
				'security': infoFromServer.nonce,
			}
		});
		executingTurn = false;
	};

	/**
	 * Process through the data from the server.
	 *
	 * This primarily checks to see what data was sent and then calls
	 * on functions to execute based on that data.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data The data returned from the server.
	 * @return {void}
	 */
	var processDataReceived = function( data ) {

		// Nothing received back from the server.
		if ( ! data ) {
			return;
		}

		if ( data.playerStats ) {
			processStatsData( data.playerStats );
			displayPlayerStats();
		}

		if ( data.playerSkills ) {
			processSkillsData( data.playerSkills[0] );
			addNoticeToMenuItem( 'Skills' );
		}

		if ( data.playerGuild ) {
			player.guild = data.playerGuild[0];
		}

		if ( data.updatePlayerGuild ) {
			player.guild = data.updatePlayerGuild[0];
			displayPlayerIdentity();
		}

		if ( data.playerIdentity ) {
			player.name = data.playerIdentity[0].name;
			player.avatar = data.playerIdentity[0].avatar;
			displayPlayerIdentity();
		}

		if ( data.room ) {
			processroomData( data.room );
			if ( 'Room' === currentlyViewing ) {
				displayRoom();
			} else {
				addNoticeToMenuItem( 'Room' );
			}
		}

		if ( data.roomUpdate ) {
			processroomData( data.roomUpdate );
			updateRoom();
		}

		if ( data.playerEquipment && ! data.playerInventory ) {
				processEquipmentData( data.playerEquipment[0] );
			if ( 'Items' === currentlyViewing ) {
				displayItems();
			} else if ( ! first_turn ) {
				addNoticeToMenuItem( 'Items' );
			}
		}

		if ( data.playerInventory ) {
			processInventoryData( data.playerInventory[0] );
			if ( data.playerEquipment ) {
				processEquipmentData( data.playerEquipment[0] );
			}
			if ( 'Items' === currentlyViewing ) {
				displayItems();
			} else if ( ! first_turn ) {
				addNoticeToMenuItem( 'Items' );
			}
		}

		if ( data.clearInventory ) {
			player.inventory = null;
			displayItems();
		}

		if ( data.notifications ) {
			processNotificationsData( data.notifications );
			displayNotifications();
		}

		if ( data.new_combat ) {
			if ( first_turn ) {
				paused = true;
				first_turn = false;
			}
			combatResults = [];
			processCombatData( data.new_combat );
			changeViewing( 'Combat' );
			displayCombat();
		}

		if ( data.fleeCombat ) {
			combatResults.push( [ 'playerFlee', getText( 'playerFleeBattle' ) ] );
			endCombat();
			displayRoom();
			enableMenuItem( 'Room' );
		}

		if ( data.combat ) {
			processCombatData( data.combat );
			if ( data.combat[0].playerDefeated ) {
				var defeatMessage = getText( 'playerDefeated' ).replace(
					'{{monster}}', data.combat[0].playerDefeated[0]
				);
				combatResults.push( [ 'playerDefeated', defeatMessage ] );
				endCombat();
				displayCombatButNoBattle();
				enableMenuItem( 'Room' );
			} else if ( data.combat[0].endCombat ) {
				endCombat();
				enableMenuItem( 'Room' );
				if ( 'Combat' === currentlyViewing ) {
					displayCombatButNoBattle();
				} else {
					addNoticeToMenuItem( 'Combat' );
				}
			} else {
				displayCombat( true );
				if ( 'Combat' !== currentlyViewing ) {
					addNoticeToMenuItem( 'Combat' );
				}
			}
		}

		if ( data.newAchievement ) {
			processAchievementData( data.newAchievement[0], 'new' );
			if ( 'Achievements' === currentlyViewing ) {
				displayAchievements();
			} else {
				addNoticeToMenuItem( 'Achievements' );
			}
		}

		if ( data.achievements ) {
			processAchievementData( data.achievements[0] );
		}

		if ( data.pause ) {
			paused = true;
		}
	}; // end processDataReceived

	/**
	 * Process through the achievements stats data from the server.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Achievement data returned from the server.
	 * @return {void}
	 */
	var processAchievementData = function( data, update ) {
		if ( ! player.achievements ) {
			player.achievements = {};
		}
		var notification = 0;
		for ( var number in data ) {
			if ( data.hasOwnProperty( number ) ) {
				player.achievements[ number ] = data[ number ];
				if ( update ) {
					notification = 1;
					unviewedAchievements[ number ] = 1;
					addNotification( 'achievement', '<b>New Achievement: ' +
						data[ number ][0] + '</b><br>' + data[ number ][1]
					);
				}
			}
		}

		// A new achievement was earned so there should be a notification to display.
		if ( notification ) {
			displayNotifications();
		}

	};

	/**
	 * Process through the combat data from the server.
	 *
	 * This contains the list of enemies, round info, and potentially
	 * messages pertaining to skills used and rewards gained.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Combat data returned from the server.
	 * @return {void}
	 */
	var processCombatData = function( data ) {
		var subObj, thisMessage, i, j, len, jlen, parsingVariables;

		for ( var obj in data ) {
			if ( data.hasOwnProperty( obj ) ) {
				subObj = data[ obj ];
				for ( var key in subObj ) {
					if ( subObj.hasOwnProperty( key ) ) {
						combat[ key ] = subObj[ key ];
					}
				}
			}
		}

		if ( combat.playerSkillUsed ) {
			for ( i = 0, len = combat.playerSkillUsed.length; i < len; i++ ) {
				combatResults.push( [ 'playerSkillUsed', '(round ' + ( combat.round - 1 ) + ') ' + combat.playerSkillUsed[ i ] ] );
			}
		}
		combat.playerSkillUsed = null;

		if ( combat.results ) {
			for ( i = 0, len = combat.results[0].length; i < len; i++ ) {
				for ( var msgType in combat.results[0][ i ] ) {
					if ( combat.results[0][ i ].hasOwnProperty( msgType ) ) {
						for ( j = 0, jlen = combat.results[0][ i ][ msgType ].length; j < jlen; j++ ) {
							if ( 'monsterDefeated' === msgType ) {
								parsingVariables = getText( 'monsterDefeated' );
								parsingVariables = parsingVariables
									.replace( '{{name}}', combat.results[0][ i ][ msgType ][ j ][0] );
								parsingVariables = parsingVariables
									.replace( '{{xp}}', addCommas( combat.results[0][ i ][ msgType ][ j ][1] ) );
								parsingVariables = parsingVariables
									.replace( '{{gold}}', addCommas( combat.results[0][ i ][ msgType ][ j ][2] ) );
								combatResults.push( [ 'monsterDefeated',
									'(round ' + ( combat.round - 1 ) + ') ' +
									parsingVariables ]
								);
							} else {
								combatResults.push( [ msgType, '(round ' + ( combat.round - 1 ) + ') ' + combat.results[0][ i ][ msgType ][ j ] ] );
							}
						}
					}
				}
			}
		}
		combat.results = null;
	}; // end processCombatData

	/**
	 * Process through the player stats data from the server.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Player Stats data returned from the server.
	 * @return {void}
	 */
	var processStatsData = function( data ) {
		var oldHP = player.hp;

		for ( var obj in data ) {
			if ( data.hasOwnProperty( obj ) ) {
				var subObj = data[ obj ];
				for ( var key in subObj ) {
					if ( subObj.hasOwnProperty( key ) ) {
						player[ key ] = subObj[ key ];
					}
				}
			}
		}

		if ( oldHP ) {
			if ( oldHP < player.hp ) {
				doAction( 'playerHealed' );
			} else if ( oldHP > player.hp ) {
				doAction( 'playerHurt' );
			}
		}
	};

	/**
	 * Process through the skills data from the server.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Skills data returned from the server.
	 * @return {void}
	 */
	var processSkillsData = function( data ) {
		player.skills = {};
		for ( var skillName in data ) {
			if ( data.hasOwnProperty( skillName ) ) {
				player.skills[ skillName ] = data[ skillName ];
			}
		}
	};

	/**
	 * Process through notification data from the server and add any
	 * new notifications.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {array} data The list notifications from the server.
	 * @return {void}
	 */
	var processNotificationsData = function( data ) {
		for ( var i = 0, len = data.length; i < len; i++ ) {
			notifications.push( [ new Date().getTime(), data[ i ][0], data[ i ][1] ] );
		}
	};

	/**
	 * Add a new notification.
	 *
	 * Notifications are given a timestamp to make each unique. This helps
	 * prevent the wrong notification being dismissed.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} type The type of notification. Determines CSS applied.
	 * @param {string} message Message to be displayed.
	 */
	var addNotification = function( type, message ) {
		notifications.push( [ new Date().getTime(), type, message ] );
	};

	/**
	 * Display all notifications player has received and not dismissed.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayNotifications = function() {
		var fragment = document.createDocumentFragment(),
			container,
			message,
			dismissLink,
			i,
			len = notifications.length;

		if ( 0 === len ) {
			fadeHTMLFragment( cached.$notifications, fragment );
			return;
		}

		for ( i = 0; i < len; i++ ) {
			container = createActionElement( 'div', null, 'dismissNotification',
				[ notifications[ i ][0], notifications[ i ][2] ]
			);
			container.id = 'drgrpg-notifications__message--' + notifications[ i ][0];
			container.className = 'drgrpg-notifications__message' +
				( notifications[ i ][1] ? ' drgrpg-notifications__message--' +
					notifications[ i ][1] : '');

			message = document.createElement( 'p' );
			message.className = 'drgrpg-notifications__message__text';
			message.innerHTML = notifications[ i ][2];
			container.appendChild( message );

			dismissLink = document.createElement( 'a' );
			dismissLink.innerHTML = getText( 'dismissNotification' );
			dismissLink.href = '#/';
			dismissLink.className = 'drgrpg-notifications__dismissLink';
			container.appendChild( dismissLink );

			fragment.appendChild( container );
		}

		fadeHTMLFragment( cached.$notifications, fragment );
	};

	/**
	 * Remove a notification and trigger an update to the displayed notifications.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {number} time The timestamp the notification was created.
	 * @param {string} message The content of the notification.
	 * @return {void}
	 */
	var removeNotification = function( time, message ) {
		for ( var i = 0, len = notifications.length; i < len; i++ ) {
			if ( time === notifications[ i ][0] &&
					message === notifications[ i ][2] ) {
				notifications.splice( i, 1 );
				break;
			}
		}
		displayNotifications();
	};

	/**
	 * Process through the inventory data from the server.
	 *
	 *  Store the data in the player.inventory object as well as create
	 *  a count of how many of each item the player has. This way each
	 *  item is listed only once, preceded by a count indicator (3x, 10x, etc).
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Inventory data returned from the server.
	 * @return {void}
	 */
	var processInventoryData = function( data ) {
		var alreadyInInventory, i, j, len, jlen, comparingTo;
		player.inventoryCount = [];
		player.inventory = [];

		// First, use JSON.stringify on the data to make comparisons easier.
		// Better to do it all once here rather than repeatedly in the loops.
		for ( i = 0, len = data.length; i < len; i++ ) {
			data[ i ] = JSON.stringify( data[ i ] );
		}

		// Now do loops comparing the items(data[i]) to items already listed
		// in the inventory. Keep a count of how many of each unique
		// item in player.inventoryCount.
		for ( i = 0, len = data.length; i < len; i++ ) {
			alreadyInInventory = false;

			for ( j = 0, jlen = player.inventory.length; j < jlen; j++ ) {
				if ( data [ i ] === player.inventory[ j ] ) {
					alreadyInInventory = j;
					break;
				}
			}

			if ( false === alreadyInInventory ) {
				player.inventory.push( data[ i ] );
				player.inventoryCount.push( 1 );
			} else {
				player.inventoryCount[ alreadyInInventory ] += 1;
			}
		}

		// Now that all the comparisons are over, convert the inventory back
		// to objects with JSON.parse.
		for ( i = 0, len = player.inventory.length; i < len; i++ ) {
			player.inventory[ i ] = JSON.parse( player.inventory[ i ] );
		}
	}; // end processInventoryData

	/**
	 * Process through the equipment data from the server and store it into
	 * the player.equippedItems object.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Equipment data returned from the server.
	 * @return {void}
	 */
	var processEquipmentData = function( data ) {
		player.equippedItems = data;
	};

	/**
	 * Display the player's identity (avatar, name, guild).
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayPlayerIdentity = function() {
		var fragment = document.createDocumentFragment(),
			span,
			avatar,
			guildTitle;

		fragment.appendChild( convertStringToFragment( player.avatar ) );

		span = document.createElement( 'span' );
		span.className = 'drgrpg-playerName';
		span.innerHTML = player.name;
		fragment.appendChild( span );

		if ( player.guild && player.guild.name ) {
			span = document.createElement( 'span' );
			span.className = 'drgrpg-playerGuild';
			guildTitle = getText( 'guildLevelLabel' );
			guildTitle = guildTitle.replace( '{{level}}', player.guild.level );
			guildTitle = guildTitle.replace( '{{name}}', player.guild.name );
			span.innerHTML = guildTitle;
			fragment.appendChild( span );
		}

		fadeHTMLFragment( cached.$playerIdentity, fragment );
	};

	/**
	 * Display the main menu
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayMainMenu = function() {
		var i, len,
			fragment = document.createDocumentFragment();

		for ( i = 0, len = mainMenu.length; i < len; i++ ) {
			fragment.appendChild(
				createActionElement( 'button',
					getText( 'mainMenu_' + mainMenu[ i ][0] ), mainMenu[ i ][1],
					mainMenu[ i ][2], mainMenu[ i ][3], mainMenu[ i ][4]
				)
			);
		}

		emptyElement( cached.mainMenu );

		cached.mainMenu.appendChild( sanitizeFragment( fragment ) );

		// Now that the fragment is appended, cache selectors.
		for ( i = 0; i < len; i++ ) {
			cached[ '$' + mainMenu[ i ][1] ] = $( document.getElementById( mainMenu[ i ][3] ) );
		}
	};

	/**
	 * Display the player's stats.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayPlayerStats = function() {
		var  fragment = document.createDocumentFragment(),
			dl, dt, dd, hp, hpContainer, hpLabel, hpNumber, hpValue,
			mp, mpContainer, mpLabel, mpNumber, mpValue,
			hpPercentage, mpPercentage, statsContainer;

		hpContainer = document.createElement( 'div' );
		hpContainer.className = 'drgrpg-playerHPContainer';

		hp = document.createElement( 'div' );
		hp.className = 'drgrpg-playerHP';
		hpPercentage = Math.round( player.hp / player.max_hp * 100 );
		hp.style.width = hpPercentage + '%';
		hpContainer.appendChild( hp );

		hpNumber = document.createElement( 'div' );
		hpNumber.className = 'drgrpg-playerHPNumber';

		hpLabel = document.createElement( 'span' );
		hpLabel.className = 'drgrpg-playerHPNumber__label';
		hpLabel.innerHTML = getText( 'playerHPLabel' );
		hpNumber.appendChild( hpLabel );

		hpValue = document.createElement( 'span' );
		hpValue.className = 'drgrpg-playerHPNumber__value';
		hpValue.innerHTML = player.hp + ' / ' + player.max_hp;
		hpNumber.appendChild( hpValue );

		hpContainer.appendChild( hpNumber );
		fragment.appendChild( hpContainer );

		mpContainer = document.createElement( 'div' );
		mpContainer.className = 'drgrpg-playerMPContainer';

		mp = document.createElement( 'div' );
		mp.className = 'drgrpg-playerMP';
		mpPercentage = Math.round( player.mp / player.max_mp * 100 );
		mp.style.width = mpPercentage + '%';
		mpContainer.appendChild( mp );

		mpNumber = document.createElement( 'div' );
		mpNumber.className = 'drgrpg-playerMPNumber';

		mpLabel = document.createElement( 'span' );
		mpLabel.className = 'drgrpg-playerMPNumber__label';
		mpLabel.innerHTML = getText( 'playerMPLabel' );
		mpNumber.appendChild( mpLabel );

		mpValue = document.createElement( 'span' );
		mpValue.className = 'drgrpg-playerMPNumber__value';
		mpValue.innerHTML = player.mp + ' / ' + player.max_mp;
		mpNumber.appendChild( mpValue );

		mpContainer.appendChild( mpNumber );
		fragment.appendChild( mpContainer );

		dl = document.createElement( 'dl' );
		dl.className = 'drgrpg-playerStats__list';

		for ( var i = 0, len = config.playerStatsToShow.length; i < len; i++ ) {
			dt = document.createElement( 'dt' );
			dt.innerHTML = getText( 'playerStats_' + config.playerStatsToShow[i] );
			dt.className = 'drgrpg-playerStats__label drgrpg-playerStats__' + config.playerStatsToShow[ i ] + '__label';
			dl.appendChild( dt );
			dd = document.createElement( 'dd' );
			dd.innerHTML = addCommas( player[ config.playerStatsToShow[ i ] ] );
			dd.className = 'drgrpg-playerStats__value drgrpg-playerStats__' + config.playerStatsToShow[ i ] + '__value';
			dl.appendChild( dd );
		}

		fragment.appendChild( dl );

		sanitizeFragment( fragment );
		emptyElement( cached.playerStats );
		cached.playerStats.appendChild( fragment );
	}; // end displayPlayerStats

	/**
	 * Process through the room data from the server and store it into
	 * the room object.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {object} data Room data returned from the server.
	 * @return {void}
	 */
	var processroomData = function( data ) {
		var oldRoom = room.id;

		for ( var obj in data ) {
			if ( data.hasOwnProperty( obj ) ) {
				var subObj = data[ obj ];
				for ( var key in subObj ) {
					if ( subObj.hasOwnProperty( key ) ) {
						room[ key ] = subObj[ key ];
					}
				}
			}
		}

		// Execute an action based on whether the player moved to a different
		// room or if they stayed in their current room but received a room update.
		if ( oldRoom && oldRoom !== room.id ) {
			doAction( 'playerMoved' );
			interactingWith = '';
		} else if ( oldRoom ) {
			doAction( 'roomUpdated' );
		}

		groupRoomObjects();
	};

	/**
	 * Take the room.objects array and divide it by object type.
	 *
	 * This makes it easier to divide the room object links into groups
	 * when the room is being displayed.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var groupRoomObjects = function() {
		room.actionObjects = [];
		room.examineObjects = [];
		room.npcObjects = [];
		for ( var i = 0, len = room.objects.length; i < len; i++ ) {
			switch ( room.objects[ i ].group ) {
				case 'action':
					room.actionObjects.push( room.objects[ i ] );
					break;
				case 'examine':
					room.examineObjects.push( room.objects[ i ] );
					break;
				case 'npc':
					room.npcObjects.push( room.objects[ i ] );
					break;
			}
		}
	};

	/**
	 * Display the Map/Room screen.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayRoom = function() {
		var fragment = document.createDocumentFragment(),
			roomExits, roomExamine, roomNPC, roomActions;

		setRoomEnvironment( room.environment );

		fragment.appendChild( generateRoomDescription() );

		roomActions = document.createElement( 'div' );
		roomActions.id = 'drgrpg-roomActions';
		room.className = 'drgrpg-roomActions';

		if ( room.examineObjects.length > 0 &&
				room.examineObjects[0].description ) {
			roomActions.appendChild(
				generateRoomObjects( 'examine', room.examineObjects )
			);
		}

		if ( room.actionObjects.length > 0 ) {
			roomActions.appendChild(
				generateRoomObjects( 'action', room.actionObjects )
			);
		}

		if ( room.npcObjects.length > 0 ) {
			roomActions.appendChild(
				generateRoomObjects( 'npc', room.npcObjects )
			);
		}

		if ( room.exits.length > 0 ) {
			roomExits = generateRoomExits();
			if ( roomExits ) {
				roomActions.appendChild( roomExits );
			}
		}

		roomActions.appendChild( generateHuntButton() );

		fragment.appendChild( roomActions );

		fadeHTMLFragment( cached.$lookingAt, fragment, function(){
			cacheRoomDescription();
			changeViewing( 'Room' );
		} );
	};

	/**
	 * Update the current room being displayed without changing the room or
	 * object description currently being viewed.
	 *
	 * This avoids clicking an object, seeing the object description for a moment,
	 * and then the description being hidden as the entire room updates due to
	 * a response from the server.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var updateRoom = function() {
		var fragment = document.createDocumentFragment();

		setRoomEnvironment( room.environment );

		if ( room.examineObjects.length > 0 ) {
			fragment.appendChild(
				generateRoomObjects( 'examine', room.examineObjects )
			);
		}

		if ( room.actionObjects.length > 0 ) {
			fragment.appendChild(
				generateRoomObjects( 'action', room.actionObjects )
			);
		}

		if ( room.npcObjects.length > 0 ) {
			fragment.appendChild(
				generateRoomObjects( 'npc', room.npcObjects )
			);
		}

		if ( room.exits.length > 0 ) {
			fragment.appendChild( generateRoomExits( room.exits ) );
		}

		fragment.appendChild( generateHuntButton() );

		fadeHTMLFragment( cached.$lookingAt
			.find( document.getElementById( 'drgrpg-roomActions' ) ),
			fragment, function() {
				cacheRoomDescription();
				if ( 'Room' !== currentlyViewing ) {
					addNoticeToMenuItem( 'Room' );
				}
			}
		);
	}; // end updateRoom

	/**
	 * Update the cached selector for the room description.
	 *
	 * This is needed because the room description element is often
	 * destroyed due to the game screen changes.
	 *
	 * @since 0.10
	 * @access private
	 * @return {void}
	 */
	var cacheRoomDescription = function() {
		cached.$roomDescription = $( document.getElementById( 'drgrpg-roomDescription' ) );
	};

	/**
	 * Return a an element containing the room description.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {element} Element containing the room description.
	 */
	var generateRoomDescription = function() {
		var div = document.createElement( 'div' );
		div.id = 'drgrpg-roomDescription';
		div.className = 'drgrpg-roomDescription';
		// TODO: Might need to make this a text node
		// div.appendChild( convertStringToFragment( room.description ) );
		div.appendChild( getCurrentDescription() );

		return div;
	};

	/**
	 * Return a fragment containing a list of links for all the room objects.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {DocumentFragment} DocumentFragment of room objects.
	 */
	var generateRoomObjects = function( objectType, objects ) {
		if ( ! objects ) {
			return;
		}
		var fragment = document.createDocumentFragment(),
			ul = document.createElement( 'ul' ),
			li;

		var header = document.createElement( 'h4' );
		header.innerHTML = getText( 'roomObjects_' + objectType + '_header' );
		header.className = 'drgrpg-roomActions__header drgrpg-roomActions__' + objectType + '__header';
		fragment.appendChild( header );

		ul.id = 'drgrpg-roomActions__' + objectType;
		ul.className = 'drgrpg-roomActions__list drgrpg-roomActions__' +
			objectType + '__list';

		if ( 'examine' === objectType ) {
			li = document.createElement( 'li' );
			li.className = 'drgrpg-roomActions__list__item';
			li.appendChild(
				createActionElement( 'a', getText( 'entireRoom' ), 'interactWithRoomObject',
					[ 'entireRoom',
					getText( 'roomObjectList_' + objectType +
					'_description' ) ]
				)
			);
			ul.appendChild( li );
		}

		for ( var obj in objects ) {
			if ( objects.hasOwnProperty( obj ) ) {
				var subObj = objects[ obj ];
				li = document.createElement( 'li' );
				li.className = 'drgrpg-roomActions__list__item';
				li.appendChild( createActionElement( 'a', subObj.name,
					'interactWithRoomObject', [ subObj.name,
							getText( 'roomObjectList_' + objectType + '_description' )
					]
				) );
				ul.appendChild( li );
			}
		}

		fragment.appendChild( ul );

		return fragment;
	}; // end generateRoomObjects

	/**
	 * Return a fragment containing a list of links for all the room exits.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {DocumentFragment} DocumentFragment of room exits.
	 */
	var generateRoomExits = function( ) {
		if ( 0 === room.exits.length ) {
			return false;
		}

		var fragment = document.createDocumentFragment(),
			ul = document.createElement( 'ul' ),
			li;

		var header = document.createElement( 'h4' );
		header.innerHTML = getText( 'roomObjects_exits_header' );
		header.className = 'drgrpg-roomActions__header drgrpg-roomActions__exit__header';
		fragment.appendChild( header );

		ul.id = 'drgrpg-roomActions__exit';
		ul.className = 'drgrpg-roomActions__list drgrpg-roomActions__exit__list';

		for ( var obj in room.exits ) {
			if ( room.exits.hasOwnProperty( obj ) ) {
				var exit = room.exits[obj];
				li = document.createElement( 'li' );
				li.className = 'drgrpg-roomActions__list__item';
				li.appendChild(
					createActionElement( 'a', exit.link, 'movePlayer', exit.link )
				);
				ul.appendChild( li );
			}
		}

		fragment.appendChild( ul );

		return fragment;
	};

	/**
	 * Generate and return the hunt button element.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {element} Element holding the hunt button.
	 */
	var generateHuntButton = function() {
		var button;

		if ( room.monsters ) {
			// Room has monsters, so player can hunt.
			button = createActionElement( 'button', getText( 'huntMonsters' ), 'huntButton', '', 'drgrpg-huntButton', 'drgrpg-huntButton' );
		} else {
			button = createActionElement( 'button', getText( 'noMonstersToHunt' ), '',
				'', 'drgrpg-huntButton', 'drgrpg-huntButton--noHunting'
			);
			button.disabled = true;
		}
		return button;
	};

	/**
	 * Interact with an object in the room.
	 *
	 * Display the description of the object and, if applicable,
	 * send an action to the server.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param  {string} withWhat Name of the room object.
	 * @param  {string} textToShow Text to show before the object description.
	 * @return {void}
	 */
	var interactWithRoomObject = function( withWhat, textToShow ) {
		var frag, actionDescription;

		if ( 'entireRoom' === withWhat ) {
			interactingWith = '';
			frag = convertStringToFragment( room.description );
			fadeHTMLFragment( cached.$roomDescription, frag );
			scrollIntoView();
			return;
		}

		for ( var obj in room.objects ) {
			if ( room.objects.hasOwnProperty( obj ) ) {
				var subObj = room.objects[ obj ];
				if ( withWhat === subObj.name ) {
					if ( subObj.action ) {
						executeAction( 'roomObjectAction', subObj.name );
					}
					frag = document.createDocumentFragment();
					actionDescription = document.createElement( 'h4' );
					actionDescription.innerHTML = textToShow +
						subObj.name + '...';
					frag.appendChild( actionDescription );
					frag.appendChild(
						convertStringToFragment( subObj.description )
					);
					fadeHTMLFragment( cached.$roomDescription, frag );

					// Remember the description of the object being interacted with
					interactingWith = frag.cloneNode( true );

					scrollIntoView();

					// Found what we need. Break out of the loop early.
					break;
				}
			}
		}
	};

	/**
	 * Scroll the room or object description back into view.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var scrollIntoView = function() {
		$('html, body').animate({
			scrollTop: cached.$mainMenu.offset().top-100
		}, 'fast');
		$("html, body").scrollTop();
	};

	/**
	 * Get the appropriate description to be displayed in the room.
	 *
	 * If your last room action was interacting with an object then that
	 * description will be used. Otherwise the main room description.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {DocumentFragment} DocumentFragment to be appended.
	 */
	var getCurrentDescription = function() {
		var frag, actionDescription;

		if ( '' !== interactingWith ) {
			return interactingWith.cloneNode( true );
		}

		// If never found an object then return the room description
		return convertStringToFragment( room.description );
	};

	/**
	 * Set a CSS class on the body element indicating the environment type
	 * of the current room.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} environment Name of the environment type.
	 */
	var setRoomEnvironment = function( environment ) {
		// Skipping the check for environment's existence since worst case
		// it will set the class to drgrpg-environment-undefined, which will
		// not break anything. I'd rather chain these methods in hopes of
		// minimizing a chance of flicker as the styles are changed.
		cached.$body.removeClass( function ( index, css ) {
			return ( css.match( /(^|\s)drgrpg-environment-\S+/g ) || [] ).join( ' ' );
		} ).addClass( 'drgrpg-environment-' + environment );
	};

	/**
	 * Return a fragment containing the enemies being faced in battle.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {DocumentFragment} DocumentFragment of combat skills.
	 */
	var createEnemiesFragment = function() {
		var ul, li, thisEnemy, enemyName, hpContainer, hp,
			hpNumber, hpLabel, hpValue, hpPercentage, enemyImage;

		ul = document.createElement( 'ul' );
		ul.id = 'drgrpg-enemies';
		ul.className = 'drgrpg-enemies';

		for ( var i = 0, len = combat.enemies[0].length; i < len; i++ ) {
			thisEnemy = combat.enemies[0][i];
			li = document.createElement( 'li' );
			li.id = 'drgrpg-enemy__' + i;
			li.className = 'drgrpg-enemy drgrpg-enemy__' + i;

			if ( 0 === thisEnemy.hp ) {
				li.className += ' drgrpg-enemy--defeated';
			}

			enemyName = document.createElement( 'div' );
			enemyName.className = 'drgrpg-enemy__name';
			enemyName.innerHTML = thisEnemy.name;
			li.appendChild( enemyName );

			hpContainer = document.createElement( 'div' );
			hpContainer.className = 'drgrpg-enemyHPContainer';

			hp = document.createElement( 'div' );
			hp.className = 'drgrpg-enemyHP';
			hpPercentage = Math.round( thisEnemy.hp / thisEnemy.max_hp * 100 );
			hp.style.width = hpPercentage + '%';
			hpContainer.appendChild( hp );

			hpNumber = document.createElement( 'div' );
			hpNumber.className = 'drgrpg-enemyHPNumber';

			hpLabel = document.createElement( 'span' );
			hpLabel.className = 'drgrpg-enemyHPNumber__label';
			hpLabel.innerHTML = getText( 'enemyHPLabel' );
			hpNumber.appendChild( hpLabel );

			hpValue = document.createElement( 'span' );
			hpValue.className = 'drgrpg-enemyHPNumber__value';
			hpValue.innerHTML = thisEnemy.hp + ' / ' + thisEnemy.max_hp;
			hpNumber.appendChild( hpValue );

			hpContainer.appendChild( hpNumber );
			li.appendChild( hpContainer );

			if ( thisEnemy.image ) {
				enemyImage = document.createElement( 'img' );
				enemyImage.src = thisEnemy.image;
				enemyImage.className = 'drgrpg-enemy__image';
				li.appendChild( enemyImage );
			}

			ul.appendChild( li );
		}

		return ul;
	}; // end createEnemiesFragment

	/**
	 * Return a fragment containing a list of links for all skills available to be
	 * used in combat.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {DocumentFragment} DocumentFragment of combat skills.
	 */
	var createCombatSkillsFragment = function() {
		var fragment = document.createDocumentFragment(),
			header, ul, li,
			skillsFound = 0;

		ul = document.createElement( 'ul' );
		ul.className = 'drgrpg-combatSkills';

		for ( var skillName in player.skills ) {
			if ( player.skills.hasOwnProperty( skillName ) ) {
				li = document.createElement( 'li' );
				li.className = 'drgrpg-combatSkills__skill';
				li.appendChild( createActionElement(
						'a', skillName, 'use_skill', skillName
					)
				);

				ul.appendChild( li );
				skillsFound += 1;
			}
		}

		if ( skillsFound > 0 ) {
			header = document.createElement( 'h3' );
			header.innerHTML = 'Use a Skill:';
			header.className = 'drgrpg-combatSkills__header';
			fragment.appendChild( header );
		}

		fragment.appendChild( ul );

		return fragment;
	}; //end createCombatSkillsFragment

	/**
	 * Display the combat screen when there is a battle taking place.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {boolean} update Is this an update to a battle that has
	 *                         already been displayed on the screen?
	 * @return {void}
	 */
	var displayCombat = function( update ) {
		var fragment = document.createDocumentFragment(),
			combatButtons;

		combatButtons = document.createElement( 'div' );
		combatButtons.className = 'drgrpg-combatButtons';

		combatButtons.appendChild( generatePauseButton() );
		combatButtons.appendChild( createActionElement(
				'button', getText( 'fleeButton' ), 'fleeButton', '', 'drgrpg-fleeButton',
				'drgrpg-combatButtons__button drgrpg-fleeButton'
			)
		);

		fragment.appendChild( combatButtons );

		fragment.appendChild( createCombatSkillsFragment() );

		fragment.appendChild( createEnemiesFragment() );

		// If this is only an update, meaning the combat screen has already
		// been generated, then just update the display of enemies.
		if ( update ) {
			emptyElement( cached.enemies );
			cached.enemies.appendChild( sanitizeFragment( createEnemiesFragment() ) );
		} else {
			fadeHTMLFragment( cached.$lookingAt, fragment, function() {
				changeViewing( 'Combat' );
				disableMenuItem( 'Room' );
				cached.enemies = document.getElementById( 'drgrpg-enemies' );
				cached.$pauseButton = $( document.getElementById( 'drgrpg-pauseButton' ) );
			} );
		}
	};

	/**
	 * Display the combat screen when there is no battle taking place.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayCombatButNoBattle = function() {
		var fragment = document.createDocumentFragment(),
			combatButtons, resultsTitle, results, message,
			i, len;

		combatButtons = document.createElement( 'div' );
		combatButtons.className = 'drgrpg-combatButtons';

		combatButtons.appendChild( generateHuntButton() );
		fragment.appendChild( combatButtons );

		results = document.createElement( 'div' );
		results.className = 'drgrpg-combatResults';

		resultsTitle = document.createElement( 'h3' );
		resultsTitle.className = 'drgrpg-combatResults__header';
		resultsTitle.innerHTML = getText( 'combatRecapTitle' );
		results.appendChild( resultsTitle );

		len = combatResults.length;
		if ( len > 0 ) {
			for ( i = 0; i < len; i++ ) {
				message = document.createElement( 'p' );
				message.className = 'drgrpg-combatResults__message drgrpg-combatResults__message--' + combatResults[ i ][0];
				message.innerHTML = combatResults[ i ][1];
				results.appendChild( message );
			}
		} else {
			message = document.createElement( 'p' );
			message.className = 'drgrpg-combatResults__message';
			message.innerHTML = getText( 'noCombatResultsYet' );
			results.appendChild( message );
		}

		fragment.appendChild( results );

		fadeHTMLFragment( cached.$lookingAt, fragment, function() {
			changeViewing( 'Combat' );
		} );
	}; // displayCombatButNoBattle

	/**
	 * Clean up after the end of combat.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var endCombat = function() {
		combat = {};
	};

	/**
	 * Display the items screen.
	 *
	 * Display the list of equipped items and then the list of items held in the
	 * player's inventory.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayItems = function() {
		var fragment = document.createDocumentFragment(),
			h3, ul, li, span, link, stats, i, len;

		// Begin equipment worn.
		h3 = document.createElement( 'h3' );
		h3.className = 'drgrpg-equipmentList__header';
		h3.innerHTML = getText( 'equipmentSlotsTitle' );
		fragment.appendChild( h3 );
		ul = document.createElement( 'ul' );
		ul.className = 'drgrpg-equipmentList';
		for ( i = 0, len = equipmentSlots.length; i < len; i++ ) {
			li = document.createElement( 'li' );
			li.className = 'drgrpg-equipmentList__item drgrpg-equipmentList__item__' + equipmentSlots[ i ];

			span = document.createElement( 'span' );
			span.className = 'drgrpg-equipmentList__item__slot';
			span.innerHTML = getText( 'equipmentSlotLabels_' + equipmentSlots[ i ] );
			li.appendChild( span );
			if ( player.equippedItems[ equipmentSlots[ i ] ].name ) {
				span = document.createElement( 'span' );
				span.className = 'drgrpg-equipmentList__item__name';
				span.innerHTML += player.equippedItems[ equipmentSlots[ i ] ].name + ' - ';
				li.appendChild( span );
			}

			if ( player.equippedItems[ equipmentSlots[i] ].attack ) {
				span = document.createElement( 'span' );
				span.className = 'drgrpg-equipmentList__item__stats__label drgrpg-equipmentList__item__stats__attack__label';
				span.innerHTML = getText( 'itemLabels_attack' );
				li.appendChild( span );

				span = document.createElement( 'span' );
				span.className = 'drgrpg-equipmentList__item__stats__value drgrpg-equipmentList__item__stats__attack__value';
				span.innerHTML = player.equippedItems[ equipmentSlots[ i ] ].attack;
				li.appendChild( span );
			}

			if ( player.equippedItems[ equipmentSlots[i] ].defense ) {
				span = document.createElement( 'span' );
				span.className = 'drgrpg-equipmentList__item__stats__label drgrpg-equipmentList__item__stats__defense__label';
				span.innerHTML = getText( 'itemLabels_defense' );
				li.appendChild( span );

				span = document.createElement( 'span' );
				span.className = 'drgrpg-equipmentList__item__stats__value drgrpg-equipmentList__item__stats__defense__value';
				span.innerHTML = player.equippedItems[ equipmentSlots[ i ] ].defense;
				li.appendChild( span );
			}

			if ( player.equippedItems[ equipmentSlots[i] ] ) {
				link = createActionElement( 'a', getText( 'unequipItemLink' ), 'unequip_item', player.equippedItems[ equipmentSlots[ i ] ] );
				link.className = 'drgrpg-equipmentList__item__link drgrpg-equipmentList__item__unequip';
				li.appendChild( link );
			}

			ul.appendChild( li );
		}
		fragment.appendChild( ul );

		if ( player.inventory ) {
			h3 = document.createElement( 'h3' );
			h3.className = 'drgrpg-itemsList__header';
			h3.innerHTML = getText( 'inventoryTitle' );
			fragment.appendChild( h3 );

			ul = document.createElement( 'ul' );
			ul.className = 'drgrpg-itemsList';

			for ( i = 0, len = player.inventory.length; i < len; i++ ) {
				li = document.createElement( 'li' );
				li.className = 'drgrpg-itemsList__item';

				if ( player.inventoryCount[ i ] > 1 ) {
					span = document.createElement( 'span' );
					span.className = 'drgrpg-itemsList__item__count';
					span.innerHTML = player.inventoryCount[ i ] + 'x';
					li.appendChild( span );
				}

				span = document.createElement( 'span' );
				span.className = 'drgrpg-itemsList__item__name';
				span.innerHTML = player.inventory[ i ].name +  ' (' +
					player.inventory[ i ].type + ') - ';
				li.appendChild( span );

				if ( player.inventory[ i ].attack ) {
					span = document.createElement( 'span' );
					span.className = 'drgrpg-itemsList__item__stats__label drgrpg-itemsList__item__stats__attack__label';
					span.innerHTML = getText( 'itemLabels_attack' );
					li.appendChild( span );

					span = document.createElement( 'span' );
					span.className = 'drgrpg-itemsList__item__stats__value drgrpg-itemsList__item__stats__attack__value';
					span.innerHTML = player.inventory[ i ].attack;
					li.appendChild( span );
				}

				if ( player.inventory[ i ].defense ) {
					span = document.createElement( 'span' );
					span.className = 'drgrpg-itemsList__item__stats__label drgrpg-itemsList__item__stats__defense__label';
					span.innerHTML = getText( 'itemLabels_defense' );
					li.appendChild( span );

					span = document.createElement( 'span' );
					span.className = 'drgrpg-itemsList__item__stats__value drgrpg-itemsList__item__stats__defense__value';
					span.innerHTML = player.inventory[ i ].defense;
					li.appendChild( span );
				}

				link = createActionElement( 'a', getText( 'equipItemLink' ), 'equip_item', player.inventory[ i ] );
				link.className = 'drgrpg-itemsList__item__link drgrpg-itemsList__item__equip';
				li.appendChild( link );

				link = createActionElement( 'a', getText( 'dropItemLink' ), 'drop_item', player.inventory[ i ] );
				link.className = 'drgrpg-itemsList__item__link drgrpg-itemsList__item__drop';
				li.appendChild( link );

				ul.appendChild( li );
			}

			fragment.appendChild( ul );
		} else {
			h3 = document.createElement( 'h3' );
			h3.className = 'drgrpg-itemsList__header';
			h3.innerHTML = getText( 'emptyInventory' );
			fragment.appendChild( h3 );
		}

		fadeHTMLFragment( cached.$lookingAt, fragment, changeViewing( 'Items' ) );
	}; // end displayItems

	/**
	 * Display the skills screen.
	 *
	 * This is very bare bones at the moment, but will likely be expanded to
	 * show the description of the skills.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displaySkills = function() {
		var fragment = document.createDocumentFragment(),
			ul, li, header;

		header = document.createElement( 'h3' );
		header.className = 'drgrpg-skillList__header';
		header.innerHTML = getText( 'skillsHeader' );
		fragment.appendChild( header );

		ul = document.createElement( 'ul' );
		ul.className = 'drgrpg-skillList';

		for ( var skillName in player.skills ) {
			if ( player.skills.hasOwnProperty( skillName ) ) {
				li = document.createElement( 'li' );
				li.className = 'drgrpg-skillList__skill';
				li.innerHTML = skillName + ' - level ' + player.skills[ skillName ];
				ul.appendChild( li );
			}
		}

		fragment.appendChild( ul );
		fadeHTMLFragment( cached.$lookingAt, fragment, changeViewing( 'Skills' ) );
	};

	/**
	 * Display the achievements screen.
	 *
	 * Each achievement will be given a different CSS class based on whether
	 * it is newly earned, previously earned and viewed, or not yet earned.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var displayAchievements = function() {
		var fragment = document.createDocumentFragment(),
			dl = document.createElement( 'dl' ),
			howManyNew = 0,
			i, len, dt, dd, header, hasNewText, hasNew;

		header = document.createElement( 'h3' );
		header.className = 'drgrpg-achievementList__header';
		header.innerHTML = getText( 'achievementsHeader' );
		fragment.appendChild( header );

		for ( i = 0, len = unviewedAchievements.length; i < len; i++ ) {
			if ( unviewedAchievements[ i ] ) {
				howManyNew += 1;
			}
		}

		if ( howManyNew > 0 ) {
			hasNew = document.createElement( 'h4' );
			hasNewText = getText( 'haveNewAchievements' );
			hasNew.innerHTML = hasNewText.replace( '{{number}}', howManyNew );
			fragment.appendChild( hasNew );
		}

		dl.className = 'drgrpg-achievementList';

		for ( var number in player.achievements ) {
			if ( player.achievements.hasOwnProperty( number ) ) {
				// Newly earned achievement.
				// Might drop this if I use notifications instead.
				if ( unviewedAchievements[ number ] ) {
					dt = document.createElement( 'dt' );
					dt.className = 'drgrpg-achievementList__name drgrpg-achievementList__name--new';
					dt.innerHTML = player.achievements[ number ][0];
					dl.appendChild( dt );

					dd = document.createElement( 'dd' );
					dd.className = 'drgrpg-achievementList__content drgrpg-achievementList__content--new';
					dd.innerHTML = player.achievements[ number ][1];
					dl.appendChild( dd );
				} else if ( player.achievements[number][1] ) {
					// Earned achievement.
					dt = document.createElement( 'dt' );
					dt.className = 'drgrpg-achievementList__name';
					dt.innerHTML = player.achievements[ number ][0];
					dl.appendChild( dt );

					dd = document.createElement( 'dd' );
					dd.className = 'drgrpg-achievementList__content';
					dd.innerHTML = player.achievements[ number ][1];
					dl.appendChild( dd );
				} else {
					// Unearned achievement.
					dt = document.createElement( 'dt' );
					dt.className = 'drgrpg-achievementList__name drgrpg-achievementList__name--unearned';
					dt.innerHTML = player.achievements[ number ][0];
					dl.appendChild( dt );

					dd = document.createElement( 'dd' );
					dd.className = 'drgrpg-achievementList__content drgrpg-achievementList__content--unearned';
					dd.innerHTML = getText( 'unearnedAchievement' );
					dl.appendChild( dd );
				}
			}
		}

		fragment.appendChild( dl );
		fadeHTMLFragment(
			cached.$lookingAt, fragment, changeViewing( 'Achievements' )
		);
		unviewedAchievements = []; // Reset unviewed achievements.
	};// end displayAchievements()

	// This is typically called as a callback after switching what the player is seen
	// following a main menu click.
	/**
	 * Update the menu buttons to show the currently viewed screen has changed.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} newView Name of the button to be marked as viewed.
	 * @return {void}
	 */
	var changeViewing = function( newView ) {
		cached[ '$show' + currentlyViewing ].removeClass( 'drgrpg-mainMenu__button--active' );
		cached[ '$show' + newView ].removeClass( 'drgrpg-mainMenu__button--notice' ).addClass( 'drgrpg-mainMenu__button--active' );

		currentlyViewing = newView;
	};

	/**
	 * Disable a menu item.
	 *
	 * Technically this doesn't disable a menu item itself, but it sets a class that
	 * makes the menu item look disabled. Other code is responsible for the menu
	 * item behaving disabled.
	 *
	 * @since  0.1.0
	 * @access private
	 * @param {name} item Name of the menu item.
	 * @return {void}
	 */
	var disableMenuItem = function( item ) {
		cached[ '$show' + item ].addClass( 'drgrpg-mainMenu__button--disabled' );
	};

	/**
	 * Enable a previously disabled menu item.
	 *
	 * @since  0.1.0
	 * @access private
	 * @param {name} item Name of the menu item.
	 * @return {void}
	 */
	var enableMenuItem = function( item ) {
		cached[ '$show' + item ].removeClass( 'drgrpg-mainMenu__button--disabled' );
	};

	/**
	 * Add class to a menu button indicating that button's corresponding screen
	 * is holding an update.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} item Name of the menu item
	 */
	var addNoticeToMenuItem = function( item ) {
		cached[ '$show' + item ].addClass( 'drgrpg-mainMenu__button--notice' );
	};

	/**
	 * Show the processing bar.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var showProcessingBar = function() {
		if ( true === showingProcessingBar ) {
			return;
		}
		showingProcessingBar = true;
		cached.$processingBar.animate( { top: "0" } );
	};

	/**
	 * Hide the processing bar.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var hideProcessingBar = function() {
		if ( false === showingProcessingBar ) {
			return;
		}
		cached.$processingBar.animate( { top: "-" + ( config.constants.processingBarHeight + 50 ) }, 400, function() {
			showingProcessingBar = false;
			changeProcessingBarMessage();
		});
	};

	/**
	 * Change the text shown when the processing bar is visible.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} message The name of the config.language property to use
	 *                         to get the text to display.
	 * @return {void}
	 */
	var changeProcessingBarMessage = function( message ) {
		if ( ! message && currentProcessingBarMessage !== 'processingActions' ) {
			cached.$processingBar.html( getText( 'processingActions' ) ).removeClass( 'drgrpg-processingBar--error' );
			currentProcessingBarMessage = 'processingActions';
		} else if ( 'processingError' === message && currentProcessingBarMessage !== 'processingError' ) {
			cached.$processingBar.html( getText( 'processingError' ) ).addClass( 'drgrpg-processingBar--error' );
			currentProcessingBarMessage = 'processingError';
		}
	};

	/**
	 * Remove actions taken last turn from the list of actions to take next turn.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {void}
	 */
	var removeActionsTaken = function() {
		for ( var i = 0, ilen = actionsTaken.length; i < ilen; i++ ) {
			for ( var j = 0, jlen = actionsToTake.length; j < jlen; j++ ) {
				if ( actionsTaken[ i ][0] === actionsToTake[ j ][0] &&
					actionsTaken[ i ][0] === actionsToTake[ j ][0] ) {
					actionsToTake.splice( j, 1 );
					break;
				}
			}
		}
		// After removing all the actions listed in actionsTaken from
		// actionsToTake we've cleared out the list of actions the server
		// received last turn so they don't get sent again. Now simply
		// empty the actionsTaken array so it's ready for next turn.
		actionsTaken = [];
	};

	/**
	 * Create an element that has an event attached to its click event.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} elementType Type of element to be created.
	 * @param {string} text Text to be displayed in the element.
	 * @param {string} action Action to execute when this element is clicked.
	 * @param {mixed} args Arguments to pass with the action when executed.
	 * @param {string} id ID of the element.
	 * @param {string} classes String listing CSS classes in format "class1 class2".
	 * @return {element} Element that has been created.
	 */
	var createActionElement = function( elementType, text, action, args, id, classes ) {
		var element = document.createElement( elementType );

		// If element is not a button or a link then set the element's style
		// to display a pointer cursor upon mouseover. This will help
		// ensure elements able to be interacted with can be seen to be
		// able to be clicked.
		if ( 'button' !== elementType && 'a' !== elementType ) {
			element.style.cursor = 'pointer';
		}

		if ( 'a' === elementType ) {
			element.href = '#/';
		}

		if ( text ) {
			element.innerHTML = text;
		}

		if ( id ) {
			element.id = id;
		}

		if ( classes ) {
			element.className = classes;
		}

		addEventListener( element, 'click', function( ) { executeAction( action, args ); } );
		return element;
	};

	/**
	 * Try adding an action. Avoid adding a lot of the same action by
	 * checking to see if this exact same set of action and args is
	 * already in the actionsToTake array.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} action The action to be executed.
	 * @param {mixed} args The arguments to pass to the action.
	 * @return {boolean} Was this action added?
	 */
	var tryAddingAction = function( action, args ) {
		for ( var i = 0, len = actionsToTake.length; i < len; i++ ) {
			if ( actionsToTake[ i ][0] === action && actionsToTake[ i ][1] === args ) {
				return false;
			}
		}
		actionsToTake.push( [ action, args ] );
		return true;
	};

	/**
	 * Execute actions submitted by the player.
	 *
	 * Actions are submitted through interacting with the game interface.
	 * Typically buttons and links.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {string} action The action to be executed.
	 * @param {mixed} args The arguments to pass to the action.
	 * @return {void}
	 */
	var executeAction = function( action, args ) {
		var addedAction = false;

		if ( ! action ) {
			return;
		}

		switch ( action ) {
			case 'pauseButton':
				togglePause();
				break;
			case 'huntButton':
				if ( ! combat.round ) {
					addedAction = tryAddingAction( 'combat', 'hunt' );
				}
				break;
			case 'fleeButton':
				if ( combat.round ) {
					addedAction = tryAddingAction( 'combat', 'flee' );
					first_turn = false;
					paused = false;
				}
				break;
			case 'interactWithRoomObject':
				interactWithRoomObject( args[0], args[1] );
				break;
			case 'movePlayer':
				addedAction = tryAddingAction( 'movePlayer', args );
				break;
			case 'equip_item':
				addedAction = tryAddingAction( 'equip_item', args );
				break;
			case 'unequip_item':
				addedAction = tryAddingAction( 'unequip_item', args );
				break;
			case 'use_skill':
				addedAction = tryAddingAction( 'use_skill', args );
				if ( paused ) {
					togglePause();
				}
				break;
			case 'drop_item':
				if ( window.confirm( getText( 'dropConfirmation' ) + args.name ) ) {
					addedAction = tryAddingAction( 'drop_item', args );
				}
				break;
			case 'showRoom':
				if ( ! combat.enemies ) {
					displayRoom();
					changeViewing( 'Room' );
				}
				break;
			case 'showCombat':
				if ( combat.enemies ) {
					displayCombat();
				} else {
					displayCombatButNoBattle();
				}
				break;
			case 'showItems':
				displayItems();
				break;
			case 'showSkills':
				displaySkills();
				break;
			case 'showAchievements':
				displayAchievements();
				break;
			case 'roomObjectAction':
				addedAction = tryAddingAction( 'roomObjectAction', args );
				break;
			case 'dismissNotification':
				removeNotification( args[0], args[1] );
				break;
		}

		// Check to see if a turn needs to be executed.
		if ( true === addedAction && turnsSinceLastAction > 0 && actionsToTake.length == 1 && false === executingTurn ) {

			// If the game is paused then it needs to be unpaused.
			if ( paused ) {
				if ( 'Combat' === currentlyViewing ) {
					// TogglePause handles executing the turn when unpausing
					// so no need to call it here.
					togglePause();
				} else {
					paused = false;
					executingTurn = true;
					executeTurn();
				}
			} else {
				executingTurn = true;
				executeTurn();
			}
		} else if ( true === addedAction && turnsSinceLastAction === 0) {
			showProcessingBar();
		}
	}; // end executeAction()

	/**
	 * Generate and return the pause button element.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return {element} Element holding the pause button.
	 */
	var generatePauseButton = function() {
		var button;
		if ( ! paused ) {
			button = createActionElement( 'button', getText( 'pauseGame' ),
				'pauseButton', '', 'drgrpg-pauseButton',
				'drgrpg-combatButtons__button drgrpg-pauseButton'
			);
		} else {
			button = createActionElement( 'button', getText( 'unpauseGame' ),
				'pauseButton', '', 'drgrpg-pauseButton',
				'drgrpg-combatButtons__button drgrpg-pauseButton--paused'
			);
		}
		return button;
	};

	/**
	 * Toggle the game between paused and unpaused.
	 *
	 * This will change both the paused state and update the pause button.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	var togglePause = function() {
		if ( false === paused ) {
			paused = true;
			cached.$pauseButton
				.html( getText( 'unpauseGame' ) )
				.attr( 'class', 'drgrpg-combatButtons__button drgrpg-pauseButton--paused' );
		} else {
			paused = false;
			cached.$pauseButton
				.html( getText( 'pauseGame' ) )
				.attr( 'class', 'drgrpg-combatButtons__button drgrpg-pauseButton' );
			if ( turnsSinceLastAction > 0 ) {
				executeTurn();
			}
		}
	};

	/**
	 * Utility Functions
	 */

	/**
	 * Add an event listener to an element.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {element} element Element getting the event listener.
	 * @param {string} eventName Name of the event('click', etc)
	 * @param {function} handler Function to be executed when the event fires.
	 */
	var addEventListener = function( element, eventName, handler ) {
		if ( element.addEventListener ) {
			element.addEventListener( eventName, handler );
		} else {
			element.attachEvent( 'on' + eventName, function() {
				handler.call( element );
			} );
		}
	};

	/**
	 * Empty an element.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param  {element} element Element to empty.
	 * @return {void}
	 */
	var emptyElement = function( element ) {
		while ( element.firstChild ) {
			element.removeChild( element.firstChild );
		}
	};

	// Function gives me a little more flexibility than using $.hide, also
	// a bit better performance I think.
	/**
	 * Replace the content in an element while fading it in and out.
	 *
	 * Fade out the element, empty it, append a fragment, and then
	 * fade the new content in.
	 * This gives me a little more flexibility than using $.hide, and is also
	 * a bit more performant I think.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {element} element Element the fragment is going into.
	 * @param {DocumentFragment} fragment Fragment to be appended.
	 * @param {function} callback Optional. Function to execute after
	 *                            fade in completes.
	 * @return {void}
	 */
	var fadeHTMLFragment = function( element, fragment, callback ) {
		if ( false === element instanceof jQuery ) {
			element = $( element );
		}

		// Remove any script tags. When inserted here it shouldn't execute
		// the script tag, but I still want to remove the script tags to be doubly
		// sure, just in case some browser behaves differently.
		fragment = sanitizeFragment( fragment );

		// NOTE: Adding/removing a visibility class might be more performant.
		element.animate( { 'opacity': 0 }, 200, function() {
			emptyElement( element[0] );
			element[0].appendChild( fragment );
			element.animate( { 'opacity': 1 }, 200 );
			if ( callback ) {
				callback();
			}
		} );
	};

	/**
	 * Remove unsafe elements from a document fragment.
	 *
	 * Instead of running wp_kses on the server-side repeatedly
	 * the game content is instead sanitized here immediately before
	 * being used. Also, escaping the invalid data can lead to it looking
	 * bad when displayed, so sanitizeFragment simply removes
	 * offending elements from the fragment.
	 * TODO: Perhaps remove more than script tags, such as iframes.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {DocumentFragment} raw DocumentFragment to be sanitized.
	 * @return {DocumentFragment} Sanitized DocumentFragment.
	 */
	var sanitizeFragment = function( raw ) {
		var temp = document.createElement( 'div' ),
			scripts,
			length;

		while ( raw.firstChild ) {
			temp.appendChild( raw.firstChild );
		}

		scripts = temp.getElementsByTagName( 'script' );
		length = scripts.length;

		while ( length-- ) {
			scripts[ length ].parentNode.removeChild( scripts[ length ] );
		}

		// Add elements back to fragment.
		while ( temp.firstChild ) {
			raw.appendChild( temp.firstChild );
		}

		return raw;
	};

	/**
	 * Convert a string containing HTML into a DocumentFragment.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param string raw HTML string to be converted into a DocumentFragment.
	 * @return {DocumentFragment} Converted DocumentFragment.
	 */
	var convertStringToFragment = function( raw ) {

		var frag = document.createDocumentFragment();
		var holder = document.createElement( 'div' );

		holder.innerHTML = raw;

		while ( holder.firstChild ) {
		    frag.appendChild( holder.firstChild );
		}

		return frag;
	};

	/**
	 * Add commas to a number if needed.
	 *
	 * Takes 1000000 and returns 1,000,000.
	 *
	 * @since 0.1.0
	 * @access private
	 * @param {number | string} num Number potentially needing commas.
	 * @return {string} Number with any commas needed.
	 */
	var addCommas = function( number ) {
		// Need the number to be a string to be able to use the replace method.
		number = number.toString();

		// Overly long explanation that minifying will remove:
		// Essentially the regular expression is looking for a number (single digit)
		// directly followed by 3 more digits. When it finds it that it captures
		// two matches - the single digit, and then the group of 3 digits. Then it
		// replaces the entire match of 4 digits with [first-group],[second-group],
		// adding a comma in where it's needed.
		// Then the while loop performs another iteration to see if there is
		// another group of 4 numbers/digits to match. By doing it this way
		// the regular expression and loop work their way from left to right
		// over the number.
		 while ( /(\d+)(\d{3})/.test( number ) ) {
			 number = number.replace( /(\d+)(\d{3})/, '$1,$2' );
		 }

		 return number;
	 };

	 /**
	  * Pick a random entry form an array.
	  *
	  * @since 0.1.0
	  * @access private
	  * @param {array} anArray Array of strings
	  * @return {string} String randomly chosen.
	  */
	 var pickRandomMessage = function ( anArray ) {
		var typeOfArg = typeof anArray,
			randomIndex,
			typeOfRandomlySelected,
			funcReturn;

		// Typeof doesn't detect an array straight away, so you have to do
	 	// more work to tell if what it detected as an object is actually an array.
		if ( 'object' === typeOfArg ) {
			if ( Object.prototype.toString.call( anArray ) === '[object Array]' ) {
				typeOfArg = 'array';
			}
		}

		if ( 'array' === typeOfArg ) {
			randomIndex = Math.floor( Math.random() * anArray.length );
			typeOfRandomlySelected = typeof anArray[ randomIndex ];
			if ( 'string' === typeOfRandomlySelected ) {
				return anArray[ randomIndex ];
			} else if ( 'function' === typeOfRandomlySelected ) {
				return 'null';
				/*
				funcReturn = anArray[ randomIndex ]();
				if ( 'string' === typeof funcReturn ) {
					return funcReturn;
				}
				*/
			}
		} else if ( 'string' === typeOfArg ) {
			return anArray;
		} else {
			return 'You must pass a string or an array.';
		}
	 };

	 /**
	  * Refresh the game screen without reloading the page.
	  *
	  * This is primarily used after an update in the game's translation has
	  * occurred. It refreshes to display the new text.
	  *
	  * @since 0.1.0
	  * @return void
	  */
	 var refreshGameScreen = function() {
	 	displayMainMenu();
	 	if ( 'Room' === currentlyViewing ) {
	 		displayRoom();
	 	} else if ( 'Combat' === currentlyViewing ) {
	 		displayCombat();
	 	} else if ( 'Items' === currentlyViewing ) {
	 		displayItems();
	 	} else if ( 'Skills' === currentlyViewing ) {
	 		displaySkills();
	 	} else if ( 'Achievements' === currentlyViewing ) {
	 		displayAchievements();
	 	}
	 	displayPlayerStats();
	 	displayPlayerIdentity();
	 };

	 /**
	  * Accept an object and set it as a pending update for the game's text.
	  *
	  * @since 0.1.0
	  * @access public
	  * @param {object} newTranslation Object containing new translation.
	  * @return void
	  */
	 var updateTranslation = function( newTranslation ) {
	 	if ( null === pendingTranslation ) {
		 	// No currently pending translation so use the object passed
		 	// in as is.
			pendingTranslation = newTranslation;
	 	} else {
	 		// Already a pending translation so need to combine the
	 		// two objects.
	 		for ( var key in newTranslation ) {
	 			if ( newTranslation.hasOwnProperty( key ) ) {
	 				pendingTranslation[ key ] = newTranslation[ key ];
	 			}
	 		}
	 	}
	};

	/**
	 * Process through a pending translation.
	 *
	 * Iterate over the object stored in pendingTranslation. When it
	 * finds valid values (arrays or strings) then update the appropriate
	 * config.language property.
	 *
	 * @since 0.1.0
	 * @access private
	 * @return void
	 */
	 var processTranslation = function() {
	 	var typeOfValue,
	 		funcReturn;

	 	for ( var key in pendingTranslation ) {
	 		if ( pendingTranslation.hasOwnProperty( key ) ) {
	 			typeOfValue = typeof pendingTranslation[ key ];

	 			// Typeof doesn't detect an array straight away, so you have to do
	 			// more work to tell if what it detected as an object is actually an array.
	 			if ( 'object' === typeOfValue ) {
	 				if ( Object.prototype.toString.call( pendingTranslation[ key ] ) === '[object Array]' ) {
	 					typeOfValue = 'array';
	 				}
	 			}

	 			if ( 'string' === typeOfValue || 'array' === typeOfValue ) {
	 				config.language[ key ] = pendingTranslation[ key ];
	 			} else if ( 'function' === typeOfValue ) {
	 				continue;
	 				/*
	 				funcReturn = pendingTranslation[ key ]();
	 				if ( 'string' === typeof funcReturn ) {
	 					config.language[ key ] = funcReturn;
	 				}
	 				*/
	 			}
	 		}
	 	}

	 	pendingTranslation = null;
	 	refreshGameScreen();
	 };

	 /**
	  * Get text from config.language
	  *
	  * If config.language[ text ] contains a string, use that. If it contains
	  * an array then it will randomly choose one of the array's entry.
	  *
	  * @since 0.1.0
	  * @access private
	  * @param {string} text Key in config.language to use.
	  * @return {string} The text to be displayed.
	  */
	 var getText = function( text ) {
	 	var typeOfValue,
	 		funcReturn;

	 	if ( ! config.language[ text ] ) {
	 		return '';
	 	}

	 	typeOfValue = typeof config.language[ text ];

	 	// Typeof doesn't detect an array straight away, so you have to do
	 	// more work to tell if what it detected as an object is actually an array.
	 	if ( 'object' === typeOfValue ) {
			if ( Object.prototype.toString.call( config.language[ text ] ) === '[object Array]' ) {
				typeOfValue = 'array';
			}
		}

		if ( 'string' === typeOfValue ) {
			return config.language[ text ];
		} else if ( 'array' === typeOfValue ) {
			return pickRandomMessage( config.language[ text ] );
		} else if ( 'function' === typeOfValue ) {
			return 'null';
			/*
			funcReturn = config.language[ text ]();
			if ( 'string' === typeof funcReturn ) {
				config.language[ key ] = funcReturn;
			}
			*/
		} else {
			return 'null';
		}
	 };

	 /**
	  * Execute a function if hook is not empty.
	  *
	  * Actions (functions) are added to hooks with addAction. If doAction is
	  * called and an action has been added then execute the function.
	  *
	  * @since 0.1.0
	  * @access private
	  * @param  {string} name Name of the hook.
	  * @return {void}
	  */
	 var doAction = function( name ) {

	 	// Fail if hook is empty or does not hold a function.
	 	if ( ! name || ! hooks[ name ] || 'function' !== typeof hooks[ name ] ) {
	 		return;
	 	}

	 	hooks[ name ]();
	 };

	 /**
	  * Set a function(action) to execute when a specific hook is encountered.
	  *
	  * @since 0.1.0
	  * @access public
	  * @param {string} name Name of the hook.
	  * @param {function} func Function to be executed.
	  */
	 var addAction = function( name, func ) {

	 	// Fail if either argument is empty or func is not a function.
	 	if ( ! name || ! func || 'function' !== typeof func ) {
	 		return;
	 	}

	 	hooks[ name ] = func;
	 };

	 /**
	  * Initialize the game.
	  *
	  * @since  0.1.0
	  * @access public
	  * @return {void}
	  */
	 var init = function() {

	 	// Game is already initialized. Do not complete init() again.
	 	if ( true === initialized ) {
	 		return;
	 	}

		initialized = true;
		cacheSelectors();
		addMissingElements();
		fillElementsWithText();
		displayMainMenu();
		executeTurn();
		doAction( 'init' );
	};

	/**
	 * "Public" vars and methods
	 */
	return {
		/**
		 * Expose the list of cached selectors but do not allow writing to it.
		 *
		 * @since  0.1.0
		 * @access public
		 * @return {object} Object containing all cached selectors.
		 */
		getSelectors: function() {
			return cached;
		},
		init: init,
		updateTranslation: updateTranslation,
		addAction: addAction,
	};
} )( jQuery );

// Initialize the game automatically if the server said so. This comes from
// wp_localize_script in DRGRPG_Assets.
if ( '1' === infoFromServer.autostart ) {
	jQuery( document ).ready( DRGRPG.init );
}
