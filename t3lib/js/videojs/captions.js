/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Stanislas Rolland <typo3@sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/* Captions Toggle Behaviors for videoJS */
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