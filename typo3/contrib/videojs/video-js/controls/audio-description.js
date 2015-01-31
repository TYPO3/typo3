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
 * Audio description Toggle Behaviors for videoJS
 *
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
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