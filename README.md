# DRG RPG #

DRG RPG (David R Green Role-Playing Game) allows you to easily setup a RPG using WordPress. Explore a world (fantasy, science fiction, or whatever you want), fight enemies, join guilds, earn achievements, and more.

[Play the demo](http://drg-rpg.com) where there is a game world built around people and businesses in the WordPress community, both praising and teasing them. [Read the wiki](https://github.com/davidrgreen/DRG-RPG/wiki) for info on personalizing the game through WP hooks, CSS, and a JavaScript hook and translation system I built into the plugin.

It began as (and still is) the testing/demonstrating of my PHP and JS skills to get a full-time job and stop freelancing. If you have any job openings for front or back end developers, please [visit my personal site](http://davidrg.com).

**Warning:** This plugin is still a prototype. Do not use it on a production website. At this point there will be breaking changing in between versions.

### Contribution Guidelines ###

Feel free to fork the plugin, but I'm not accepting pull requests currently. I have a fairly concrete plan for the next few phases of this project and am wanting to do them myself. Once the plugin hits phase 6 then I will open it to pull requests. If you find a bug, however, please open an issue.

### Phases of Development ###

1. **[DONE]** Create the plugin with whatever base functionality can be done in 5 weeks.

2.  Refactoring and Quality Check
	- Probably move player data out of user meta into a custom database table.
	- Reevaluate class structure to reduce duplicate code.
	- Audit SQL queries and general performance improvements.
	- Eliminate screen flicker during combat on mobile by manipulating styles instead of overwriting document nodes.
	- Test browser compatibility.

3. Continue adding features:
	- Player levels & ability to increase stats.
	- Settings page for changing character info.
	- Usable items.
	- Item crafting and modifying.
	- Pets
	- More types of skills( target multiple monsters, inflict negative status, boost stats, recover HP)
	- Battle types (boss, reinforcements entering mid-battle).
	- Monster abilities.
	- Create game patch system.
	- Ability to trigger fights via room object and a special award given at the end of the battle.

4. Release version 1. Post optional jump start XML import files so game admins can modify a basic existing game's content instead of starting from scratch.

5. Add player-to-player interactions such as giving items and player-vs-player combat.

6. Begin accepting pull requests.
