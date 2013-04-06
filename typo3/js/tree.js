/***************************************************************
*
*  javascript functions regarding the page & folder tree
*  relies on the javascript library "prototype"
*
*
*  Copyright notice
*
*  (c) 2006-2011 Benjamin Mack <benni@typo3.org>
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
***************************************************************/



/**
 *
 * @author	Benjamin Mack
 */
 
// new object-oriented drag and drop - code,
// tested in IE 6, Firefox 2, Opera 9
var DragDrop = {
	dragID: null,

	// options needed for doing the changes when dropping
	table: null,	// can be "pages" or "folders"
	changeURL: null,
	backPath: null,


	dragElement: function(event, elementID) {
		Event.stop(event); // stop bubbling
		this.dragID = this.getIdFromEvent(event);
		if (!this.dragID) {
			return false;
		}

		if (!elementID) {
			elementID = this.dragID;
		}
		if (!$('dragIcon')) {
			this.addDragIcon();
		}

		$('dragIcon').innerHTML = $('dragIconID_'+elementID).innerHTML +
								  $('dragTitleID_'+elementID).firstChild.innerHTML;

		document.onmouseup   = function(event) { DragDrop.cancelDragEvent(event); };
		document.onmousemove = function(event) { DragDrop.mouseMoveEvent(event); };
		return false;
	},

	dropElement: function(event) {
		var dropID = this.getIdFromEvent(event);
		if ((this.dragID) && (this.dragID !== dropID)) {
			var url = this.changeURL +
					'?dragDrop=' + this.table +
					'&srcId=' + this.dragID +
					'&dstId=' + dropID +
					'&backPath=' + this.backPath;
			showClickmenu_raw(url);
		}
		this.cancelDragEvent();
		return false;
	},


	cancelDragEvent: function(event) {
		this.dragID = null;
		if ($('dragIcon') && $('dragIcon').style.visibility === 'visible') {
			$('dragIcon').style.visibility = 'hidden';
		}

		document.onmouseup = null;
		document.onmousemove = null;
	},

	mouseMoveEvent: function(event) {
		if (!event) {
			event = window.event;
		}
		$('dragIcon').style.left = (Event.pointerX(event) + 5) + 'px';
		$('dragIcon').style.top  = (Event.pointerY(event) - 5) + 'px';
		$('dragIcon').style.visibility = 'visible';
		return false;
	},


	// -- helper functions --
	getIdFromEvent: function(event) {
		var obj = Event.element(event);
		while (obj.id == false && obj.parentNode) { 
			obj = obj.parentNode;
		}
		return obj.id.substring(obj.id.indexOf('_') + 1);
	},

	// dynamically manipulates the DOM to add the div needed for drag&drop at the bottom of the <body>-tag
	addDragIcon: function() {
		var code = '<div id="dragIcon" style="visibility: hidden;">&nbsp;</div>';
		var insert = new Insertion.Bottom(document.getElementsByTagName('body')[0], code);
	}
};


var Tree = {
	thisScript: 'ajax.php',
	ajaxID: 'SC_alt_db_navframe::expandCollapse',	// has to be either "SC_alt_db_navframe::expandCollapse" or "SC_alt_file_navframe::expandCollapse"
	frameSetModule: null,
	activateDragDrop: true,
	highlightClass: 'active',
	pageID: 0,

	// reloads a part of the page tree (useful when "expand" / "collapse")
	load: function(params, isExpand, obj) {
			// fallback if AJAX is not possible (e.g. IE < 6)
		if (typeof Ajax.getTransport() !== 'object') {
			window.location.href = this.thisScript + '?ajaxID=' + this.ajaxID + '&PM=' + encodeURIComponent(params);
			return;
		}

		// immediately collapse the subtree and change the plus to a minus when collapsing
		// without waiting for the response
		if (!isExpand) {
			var ul = obj.parentNode.parentNode.getElementsByTagName('ul')[0];
			if (ul) {
				obj.parentNode.parentNode.removeChild(ul); // no remove() directly because of IE 5.5
			}
			var pm = Selector.findChildElements(obj.parentNode, ['.pm'])[0]; // Getting pm object by CSS selector (because document.getElementsByClassName() doesn't seem to work on Konqueror)
			if (pm) {
				pm.onclick = null;
				Element.cleanWhitespace(pm);
				pm.firstChild.src = pm.firstChild.src.replace('minus', 'plus');
			}
		} else {
			obj.style.cursor = 'wait';
		}
		
		var call = new Ajax.Request(this.thisScript, {
			method: 'get',
			parameters: 'ajaxID=' + this.ajaxID + '&PM=' + encodeURIComponent(params),
			onComplete: function(xhr) {
				// the parent node needs to be overwritten, not the object
				$(obj.parentNode.parentNode).replace(xhr.responseText);
				TYPO3PageTreeFilter.filter();
				this.registerDragDropHandlers();
				this.reSelectActiveItem();
			}.bind(this),
			onT3Error: function(xhr) {
				// if this is not a valid ajax response, the whole page gets refreshed
				this.refresh();
			}.bind(this)
		});
	},

	// does the complete page refresh (previously known as "_refresh_nav()")
	refresh: function() {
		var r = new Date();
		// randNum is useful so pagetree does not get cached in browser cache when refreshing
		var loc = window.location.href.replace(/&randNum=\d+|#.*/g, '');
		var addSign = loc.indexOf('?') > 0 ? '&' : '?';
		window.location = loc + addSign + 'randNum=' + r.getTime();
	},

	// attaches the events to the elements needed for the drag and drop (for the titles and the icons)
	registerDragDropHandlers: function() {
		if (!this.activateDragDrop) {
			return;
		}
		this._registerDragDropHandlers('dragTitle');
		this._registerDragDropHandlers('dragIcon');
	},

	_registerDragDropHandlers: function(className) {
		var elements = Selector.findChildElements($('tree'), ['.'+className]); // using Selector because document.getElementsByClassName() doesn't seem to work on Konqueror
		for (var i = 0; i < elements.length; i++) {
			Event.observe(elements[i], 'mousedown', function(event) { DragDrop.dragElement(event); }, true);
			Event.observe(elements[i], 'dragstart', function(event) { DragDrop.dragElement(event); }, false);
			Event.observe(elements[i], 'mouseup',   function(event) { DragDrop.dropElement(event); }, false);
		}
	},

	// selects the activated item again, in case it collapsed and got expanded again
	reSelectActiveItem: function() {
		var obj = $(top.fsMod.navFrameHighlightedID[this.frameSetModule]);
		if (obj) {
			Element.addClassName(obj, this.highlightClass);
			this.extractPageIdFromTreeItem(obj.id);
		}
	},

	// highlights an active list item in the page tree and registers it to the top-frame
	// used when loading the page for the first time
	highlightActiveItem: function(frameSetModule, highlightID) {
		this.frameSetModule = frameSetModule;
		this.extractPageIdFromTreeItem(highlightID);

		// Remove all items that are already highlighted
		var obj = $(top.fsMod.navFrameHighlightedID[frameSetModule]);
		if (obj) {
			var classes = $w(this.highlightClass);
			for (var i = 0; i < classes.length; i++)
				Element.removeClassName(obj, classes[i]);
		}

		// Set the new item
		top.fsMod.navFrameHighlightedID[frameSetModule] = highlightID;
		if ($(highlightID)) {
			Element.addClassName(highlightID, this.highlightClass);
		}
	},

	//extract pageID from the given id (pagesxxx_y_z where xxx is the ID)
	extractPageIdFromTreeItem: function(highlightID) {
		if(highlightID) {
			this.pageID = highlightID.split('_')[0].substring(5);
		}
	}
};




/**
 *
 * @author	Ingo Renner <ingo@typo3.org>
 **/
var PageTreeFilter = Class.create({
	field: 'treeFilter',
	resetfield: 'treeFilterReset',

	/**
	 * constructor with event listener
	 */
	initialize: function() {
			// event listener
		Event.observe(document, 'dom:loaded', function(){
			Event.observe(this.field, "keyup", this.filter.bindAsEventListener(this));
			Event.observe(this.resetfield, "click", this.resetSearchField.bindAsEventListener(this) );
		}.bind(this));
	},

	/**
	 * Filters the tree by setting a class on items not matching search input string
	 * tested in FF2, S3, IE6, IE7
	 */
	filter: function() {
		var searchString = $F(this.field).toLowerCase();

		var pages = $$('#treeRoot .dragTitle a');
		pages.each(function(el) {
			if (el.innerHTML.toLowerCase().include(searchString)) {
				el.up().removeClassName('not-found');
			} else {
				el.up().addClassName('not-found');
			}
		});
		this.toggleReset();

	},

	/**
	 * toggles the visibility of the reset button
	 */
	toggleReset: function() {
		var searchFieldContent = $F(this.field);
		var resetButton = $(this.resetfield);

		if (searchFieldContent != '' && resetButton.getStyle('visibility') == 'hidden') {
			resetButton.setStyle({ visibility: 'visible'} );
		} else if (searchFieldContent == '' && resetButton.getStyle('visibility') == 'visible') {
			resetButton.setStyle( {visibility: 'hidden'} );
		}
	},

	/**
	 * resets the search field
	 */
	resetSearchField: function() {
		if ($(this.resetfield).getStyle('visibility') == 'visible') {
			$(this.field).value = '';
			this.filter('');
		}
	}
});


// Call this function, refresh_nav(), from another script in the backend if you want
// to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
// please use the function in the "Tree" object for future implementations
function refresh_nav() {
	window.setTimeout('Tree.refresh();',0);
}
