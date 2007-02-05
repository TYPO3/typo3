/***************************************************************
*
*  javascript functions regarding the page & folder tree
*  relies on the javascript library "prototype"
*
*
*  Copyright notice
*
*  (c) 2006	Benjamin Mack <bmack@xnos.org>
*  All rights reserved
*
*  This script is part of the TYPO3 t3lib/ library provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in tslib/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
*
*  TYPO3 SVN ID: $Id$
*
***************************************************************/

	// Call this function, refresh_nav(), from another script in the backend if you want
	// to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
	//		See t3lib_BEfunc::getSetUpdateSignal()
function refresh_nav() { window.setTimeout('_refresh_nav();',0); }
function _refresh_nav()	{
	var r = new Date();
	// randNum is useful so pagetree does not get cached in browser cache when refreshing
	window.location.href = Tree.thisScript + '?randNum=' + r.getTime();
}


var Tree = {
	thisScript: null,
	frameSetModule: null,
	activateDragDrop: true,

	// reloads a part of the page tree (useful when "expand" / "collapse")
	load: function(params, isExpand, obj) {
			// fallback if AJAX is not possible (e.g. IE < 6)
		if (!Ajax.getTransport()) {
			window.location.href = this.thisScript + '?PM=' + params;
			return;
		}

		// immediately collapse the subtree and change the plus to a minus when collapsing
		// without waiting for the response
		if (!isExpand) {
			$(obj.parentNode.getElementsByTagName('ul')[0]).remove();
			var pm = $(obj.parentNode).getElementsByClassName('pm')[0];
			pm.onclick = function() {};
			pm.firstChild.src = pm.firstChild.src.replace('minus', 'plus');
		} else {
			obj.style.cursor = 'wait';
		}

		new Ajax.Request(this.thisScript, {
			method: 'get',
			parameters: 'ajax=1&PM=' + params,
			onComplete: function(xhr) {
					// the parent node needs to be overwritten, not the object
				$(obj.parentNode).replace(xhr.responseText);
				this.registerDragDropHandlers();
				this.reSelectActiveItem();
			}.bind(this)
		});
	},

	// attaches the events to the elements needed for the drag and drop (for the titles and the icons)
	registerDragDropHandlers: function() {
		if (!this.activateDragDrop) return;
		this._registerDragDropHandlers('dragTitle');
		this._registerDragDropHandlers('dragIcon');
	},

	_registerDragDropHandlers: function(className) {
		var elements = document.getElementsByClassName(className, $('tree'));
		for (var i = 0; i < elements.length; i++) {
			Event.observe(elements[i], 'mousedown', function(event) { DragDrop.dragElement(event); }, true);
			Event.observe(elements[i], 'dragstart', function(event) { DragDrop.dragElement(event); }, false);
			Event.observe(elements[i], 'mouseup',   function(event) { DragDrop.dropElement(event); }, false);
		}
	},

	// selects the activated item again, in case it collapsed and got expanded again
	reSelectActiveItem: function() {
		obj = $(top.fsMod.navFrameHighlightedID[this.frameSetModule]);
		if (obj) Element.addClassName(obj, 'active');
	},

	// highlights an active list item in the page tree and registers it to the top-frame
	// used when loading the page for the first time
	highlightActiveItem: function(frameSetModule, highlightID) {
		this.frameSetModule = frameSetModule;

		// Remove all items that are already highlighted
		obj = $(top.fsMod.navFrameHighlightedID[frameSetModule]);
		if (obj) Element.removeClassName(obj, 'active');

		// Set the new item
		top.fsMod.navFrameHighlightedID[frameSetModule] = highlightID;
		if ($(highlightID)) Element.addClassName(highlightID, 'active');
	}

}



// new object-oriented drag and drop - code, 
// tested in IE 6, Firefox 2, Opera 9
var DragDrop = {
	dragID: null,
	dragIconCSS: null,

	// options needed for doing the changes when dropping
	table: null, // can be "pages" or "folders"
	changeURL: null,
	backPath: null,


	dragElement: function(event, elementID) {
		Event.stop(event); // stop bubbling
		this.dragID = this.getIdFromEvent(event);
		if (this.dragID == 0) return false;

		if (!elementID) elementID = this.dragID;
		if (!$('dragIcon')) this.addDragIcon();

		$('dragIcon').innerHTML = $('dragIconID_'+elementID).innerHTML
								+ $('dragTitleID_'+elementID).firstChild.innerHTML;

		this.dragIconCSS = new GL_getObjCss('dragIcon');
		this.dragIconCSS.whiteSpace = 'nowrap';
		document.onmouseup   = function(event) { DragDrop.cancelDragEvent(event); };
		document.onmousemove = function(event) { DragDrop.mouseMoveEvent(event); };
		return false;
	},

	dropElement: function(event) {
		var dropID = this.getIdFromEvent(event);
		if ((this.dragID) && (this.dragID != dropID)) {
			var url = this.changeURL
				    + '?dragDrop=' + this.table
					+ '&srcId=' + this.dragID
					+ '&dstId=' + dropID;
					+ '&backPath=' + this.backPath;
			showClickmenu_raw(url);
		}
		this.cancelDragEvent();
		return false;
	},


	cancelDragEvent: function(event) {
		this.dragID = null;
		if (this.dragIconCSS && this.dragIconCSS.visibility) { this.dragIconCSS.visibility = 'hidden'; }
		document.onmouseup = null;
		document.onmousemove = null;
	},

	mouseMoveEvent: function(event) {
		this.dragIconCSS.left = (GLV_x + 5) + 'px';
		this.dragIconCSS.top  = (GLV_y - 5) + 'px';
		this.dragIconCSS.visibility = 'visible';
		return false;
	},


	// -- helper functions --
	getIdFromEvent: function(event) {
		var obj = Event.element(event);
		while (obj.id == false && obj.parentNode) { obj = obj.parentNode; }
		return obj.id.substring(obj.id.indexOf('_')+1);
	},

	// dynamically manipulates the DOM to add the div needed for drag&drop at the bottom of the <body>-tag
	addDragIcon: function() {
		var code = '<div id="dragIcon" style="visibility: hidden;">&nbsp;</div>';
		new Insertion.Bottom(document.getElementsByTagName('body')[0], code);
	}
}
