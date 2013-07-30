=== Audio Player ===

Contributors: doryphores
Donate link: http://www.wpaudioplayer.com
Tags: media, audio, podcast, post, mp3, music, embed, flash, player, sound, media player, music player, mp3 player
Requires at least: 2.2
Tested up to: 2.9
Stable tag: 2.0.4.6

Audio Player is a highly configurable but simple mp3 player for all your audio needs. You can customise the player's colour scheme to match your blog theme, have it automatically show track information from the encoded ID3 tags and more.

== Description ==

Audio Player is a highly configurable but simple mp3 player for all your audio needs.

Features include:

* configurable colour scheme to match your blog theme
* volume control
* right-to-left layout switch for Arabic and Hebrew language sites
* many options such as autostart and loop
* ID3 tag support (custom track info also available)

Related links:

* [Usage](http://wpaudioplayer.com/usage)
* [FAQs](http://wpaudioplayer.com/frequently-asked-questions)
* [Troubleshooting](http://wpaudioplayer.com/support/troubleshooting)

== Installation ==

To install the plugin manually:

1. Extract the contents of the archive (zip file)
2. Upload the audio-player folder to your '/wp-content/plugins' folder
3. Activate the plugin through the Plugins section in your WordPress admin

IMPORTANT NOTE:

This plugin will only work if your theme allows inserting code in the HEAD and FOOTER sections of your blog. See [this page](http://wpaudioplayer.com/support/troubleshooting) for more details.

Upgrade - VERY IMPORTANT

Upgrading from 1.2.3 to 2.0: The plugins/audio-player.php file is no longer needed and MUST be deleted. The audio-player.php file now lives in plugins/audio-player/

== Changelog ==

= 2.0.4.6 =

Player swf fix

= 2.0.4.5 =

Fixed option page button styling

= 2.0.4.4 =

Fixed time display

= 2.0.4.3 =

Security update

= 2.0.4.1 =

* RTL layout was set as default (oops)

= 2.0.4 =

* Reverted RTL play button (again, needs more flexible options)
* Added "remove all enclosures" option
* Fixed PHP 4 compatibility (this caused players to not show in the Sermon Browser plugin)

= 2.0.3.1 =

* Fixed path to plugins folder (in case plugins are stored in a non-standard place)

= 2.0.3 =

* Play button now points to the left in RTL layout
* Alternate content is now hidden before player loads
* Fixed Spanish translation file problem
* Added Danish language file and fixed small errors in all others
* Ampersands in mp3 file names are now properly decoded
* Fixed an issue with migrating the transparent background option to version 2.x

= 2.0.2 =

* Updated SWFObject to version 2.2
* Added translation files for German and Spanish

= 2.0.1 =

* Fixed a bug where custom track titles were being ignored

== Frequently Asked Questions ==

View the complete FAQs [here](http://wpaudioplayer.com/frequently-asked-questions).
