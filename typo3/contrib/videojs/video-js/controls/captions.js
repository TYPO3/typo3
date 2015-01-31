/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Captions Toggle Behaviors for videoJS
 *
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
VideoJS.player.newBehavior("captionsToggle",
	function(element) {
		_V_.addListener(element, "click", this.onCaptionsToggleClick.context(this));
	},{
		// When the user clicks on the subtitles button, update subtitles setting
	onCaptionsToggleClick: function(event) {
			if (this.subtitlesDisplay.style.visibility != "hidden") {
				this.hideCaptions();
			} else {
				this.showCaptions();
			}
		}
	}
);
VideoJS.player.extend({
		// Override to use captions kind of track rather than subtitles
	getSubtitles: function(){
		var tracks = this.video.getElementsByTagName("TRACK");
		for (var i=0,j=tracks.length; i<j; i++) {
			if (tracks[i].getAttribute("kind") == "captions" && tracks[i].getAttribute("src")) {
				this.subtitlesSource = tracks[i].getAttribute("src");
				this.loadSubtitles();
				this.buildSubtitles();
			}
		}
	},
	showCaptions: function (event) {
		this.subtitlesDisplay.style.visibility = "visible";
	},
	hideCaptions: function (event) {
		this.subtitlesDisplay.style.visibility = "hidden";
	}	
});