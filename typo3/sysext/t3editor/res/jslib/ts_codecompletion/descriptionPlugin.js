/**
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
 * @class Descriptionbox plugin for the t3editor-codecompletion which displays the datatype
 * and the desciption for each property displayed in the completionbox
 * @constructor
 * @return A new DescriptionPlugin instance
 **/

var DescriptionPlugin = function() {
	var outerdiv;
	var descriptionBox;
	var completionBox;
	var tsRef;
	var pluginMeta;

	this.init = function(pluginContext,plugin) {
		pluginMeta = plugin;
		outerdiv = pluginContext.outerdiv;
		completionBox = pluginContext.codeCompleteBox;
		tsRef = pluginContext.tsRef;
		descriptionBox = new Element("DIV", {"class": "t3e_descriptionBox"});
		descriptionBox.hide();
		outerdiv.appendChild(descriptionBox);
	}
	this.afterMouseOver = function(currWordObj,compResult) {
		refreshBox(currWordObj,compResult);
	}
	this.afterKeyDown = function(currWordObj,compResult) {
		refreshBox(currWordObj,compResult);
	}
	this.afterKeyUp = function(currWordObj,compResult) {
		refreshBox(currWordObj,compResult);
	}
	this.afterCCRefresh = function(currWordObj,compResult) {
		refreshBox(currWordObj,compResult);
	}
	function descriptionLoaded(desc) {
		$('TSREF_description').innerHTML = desc;
	}

	function refreshBox(proposalObj,compResult) {
		var type = compResult.getType();

		if (type && type.properties[proposalObj.word]) {
			// first a container has to be built
			descriptionBox.innerHTML  = '<div class="TSREF_type_label">Object-type: </div><div class="TSREF_type">'+type.typeId+'</div>';
			descriptionBox.innerHTML += '<div class="TSREF_type_label">Property-type: </div><div class="TSREF_type">'+type.properties[proposalObj.word].value+'</div><br/>';
			descriptionBox.innerHTML += '<div class="TSREF_description_label">TSREF-description:</div><div id="TSREF_description"><img src="gfx/spinner.gif" border="0" alt="one moment please..."/></div>';
			var prop = type.properties[proposalObj.word];
			// if there is another request for a description in the queue -> cancel it

			window.clearTimeout(this.lastTimeoutId);
			// add a request for a description onto the queue, but wait for 0.5 seconds
			// (look if user really wants to see the description of this property, if not -> don't load it)
			this.lastTimeoutId = prop.getDescription.bind(prop).delay(0.5,descriptionLoaded);
			descriptionBox.show();
		} else if (proposalObj.type) {
			descriptionBox.innerHTML = '<div class="TSREF_type_label">TSREF-type: </div><div class="TSREF_type">'+proposalObj.type+'</div><br/>';
			descriptionBox.show();
		} else {
			descriptionBox.innerHTML = '';
			descriptionBox.hide();
		}

		descriptionBox.scrollTop = 0;
		descriptionBox.style.overflowY = 'scroll';
		descriptionBox.addClassName('descriptionBox');

		var addX = 5;
		if (!Prototype.Browser.Gecko) { // not firefox
			addX = 18;
		}
		var leftOffset = parseInt(completionBox.getStyle('left').gsub('px','')) + parseInt(completionBox.getStyle('width').gsub('px','')) + addX;
		leftOffset += 'px';
		descriptionBox.setStyle({
			top: completionBox.getStyle('top'),
			left: leftOffset
		});
	}

	this.endCodeCompletion = function(){
		descriptionBox.hide();
	}
}