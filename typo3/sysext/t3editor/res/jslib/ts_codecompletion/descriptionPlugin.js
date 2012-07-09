/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
* A copy is found in the textfile GPL.txt and important notices to the license
* from the author is found in LICENSE.txt distributed with these scripts.
*
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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
			descriptionBox.innerHTML += '<div class="TSREF_description_label">TSREF-description:</div><div id="TSREF_description"><img src="../../../gfx/spinner.gif" border="0" alt="one moment please..."/></div>';
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