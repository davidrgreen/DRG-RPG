# README #

DRG RPG allows you to easily setup a RPG using WordPress. Explore a world (fantasy, science fiction, or whatever you want), fight enemies, join guilds, earn achievements, and more.

It began as (and still is) the testing/demonstrating of my PHP and JS skills to get a full-time job and stop freelancing. If you have any job openings for front or back end developers, please [visit my personal site](http://davidrg.com).

**Warning:** This plugin is still a prototype. Do not use it on a production website. At this point there will be breaking changing in between versions.

### Contribution Guidelines ###

Feel free to fork the plugin, but I'm not accepting pull requests currently. I have a fairly concrete plan for the next few phases of this project and am wanting to do them myself. Once the plugin hits phase 5 then I will open it to pull requests. If you find a bug, however, please open an issue.

### Phases of Development ###

1. **[DONE]** Create the plugin with whatever base functionality can be done in 5 weeks.

2.  Refactoring. Probably move out of user meta into a custom database table. Reevaluate class structure to reduce duplicate code. Improve performance. Make method and property names more consistent.

3. Continue adding features: player levels, stat advancement, settings page, item crafting and modifying, pets, more types of skills, status effects (poison, regen), battle types (boss, massive), monster abilities, create game patch system. Add ability to tie animations to game actions. Ability to trigger fights via room object and a special award given at the end of the battle.

4. Squash bugs. Ensure cross-browser compatibility. Probably change from using post meta to custom tables. Add inline documentation(DocBlock) for all functions.

5. Release version 1. Release optional jump start XML import files so game admins can modify a basic existing game's content instead of starting from scratch.

6. Add player-to-player interactions such as giving items and player-vs-player combat.

7. Begin accepting pull requests.