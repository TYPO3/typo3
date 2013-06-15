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
/* Audio description Toggle Behaviors for videoJS */
VideoJS.player.newBehavior("audioDescriptionToggle", function(element) {
		_V_.addListener(element, "click", this.onAudioDescriptionToggleClick.context(this));
	},{
			// When the user clicks on the audioDescription button, update audioDescription state
		onAudioDescriptionToggleClick: function(event) {
			if (this.audioDescriptionEnabled) {
				this.disableAudioDescription();
			} else {
				this.enableAudioDescription();
			}
		}
	}
);
VideoJS.player.extend({
		// Audio description state variable
	audioDescriptionEnabled: false,
		// Reference to the audio description audio element
	audioDescription: null,
		// Enable audio description
	enableAudioDescription: function (event) {
			// Set reference if not yet done
		if (!this.audioDescription) {
			var id = this.video.id.replace("video_js", "audio_element");
			this.audioDescription = document.getElementById(id);
		}
		if (this.audioDescription && this.audioDescription.nodeName == 'AUDIO') {
			this.audioDescription.muted = false;
			this.audioDescriptionEnabled = true;
		}
	},
		// Disable audio description
	disableAudioDescription: function (event) {
		if (this.audioDescription && this.audioDescription.nodeName == 'AUDIO') {
			this.audioDescription.muted = true;
			this.audioDescriptionEnabled = false;
		}
	}
});