
var typo3pageModule = {
	placeholder: null,
	el: null,
	idCache: {},
	spinner: null,

	/**
	 * Initialization
	 */
	init: function(){
		typo3pageModule.enableHighlighting();
		typo3pageModule.registerDragAndDrop();
	},

	/**
	 * Each id param contains the id of the element and its language.
	 *
	 * Example: ttContent_4711_1
	 *
	 * To use them on the client side they need to be split. This method
	 * splits the string in to a id and language part and caches it.
	 */
	_parseIdString: function(elementPart,idString){
		if(typo3pageModule.idCache[idString] == null){
			idArray = idString.replace(elementPart,'').split('_');
			var res = {
				id: idArray[0],
				language: idArray[1]
			};

			typo3pageModule.idCache[idString] = res;
		}

		return typo3pageModule.idCache[idString];
	},

	/**
	 * Appends a loading spinner to the body.
	 *
	 */
	showSpinner: function(){
		typo3pageModule.spinner = new Element('div');
		Ext.get(typo3pageModule.spinner).addClass('loadingSpinner').fadeIn({ endOpacity: .75, duration: 1});

		Ext.select('body').appendChild(typo3pageModule.spinner);
	},
	/**
	 * Removes the loading spinner from the body.
	 */
	hideSpinner: function(){
		Ext.get(typo3pageModule.spinner).fadeOut({ endOpacity: 1.0, duration: 1}).remove();
	},
	/**
	 * This method is used as an event handler when the
	 * user hovers the headline of an content element.
	 */
	enableContentHeader: function(e,t){
		var parent = Ext.get(t).findParent('div.contentElement',null,true);
		parent.child('div.ttContentHeader').show();
		parent.addClass('active')
	},

	/**
	 * This method is used as event handler to hide the headline of
	 * an content element when the mouse of the user leaves the
	 * header of the content element.
	 */
	disableContentHeader: function(e,t){
		var parent = Ext.get(t).findParent('div.contentElement',null,true);
		parent.child('div.ttContentHeader').hide();
		parent.removeClass('active');

	},

	/**
	 * This method is used to bind the higlighting function "enableContentHeader"
	 * to the mouseenter event and the "disableContentHeader" to the mouselease event.
	 */
	enableHighlighting: function(){
		Ext.select('div.contentElement').on('mouseenter',typo3pageModule.enableContentHeader,typo3pageModule).on('mouseleave',typo3pageModule.disableContentHeader,typo3pageModule);
	},

	/**
	 * This method is used to unbind the higlighting function "enableContentHeader"
	 * from the mouseenter event and the "disableContentHeader" from the mouselease event.
	 */
	disableHighlighting: function(){
		Ext.select('div.contentElement').un('mouseenter',typo3pageModule.enableContentHeader,typo3pageModule).un('mouseleave',typo3pageModule.disableContentHeader,typo3pageModule);
	},

	/**
	 * This method is used to create the draggable items and the drop zones.
	 *
	 * The method additionaly add eventhandler methods and passes the to the
	 * draggable object.
	 */
	registerDragAndDrop: function(){
		var overrides = {
			// Called the instance the element is dragged.
			b4StartDrag: 	typo3pageModule.b4StartDrag,
			onInvalidDrop: 	typo3pageModule.invalidDrop,
			endDrag: 		typo3pageModule.moveToDropTargetAndEnrich,
			onDragDrop: 	typo3pageModule.handleElementDrop,
			onDragEnter: 	typo3pageModule.enableDropZone,
			onDragOut: 		typo3pageModule.disableDropZone
		};

		Ext.select('div.contentElement').each(function(el){
			var parsedId = typo3pageModule._parseIdString('ttContent_',el.dom.id);
			var dd = new Ext.dd.DD(el, 'contentelements_' + parsedId.language, { isTarget  : false});
		    Ext.apply(dd, overrides);
		});

		Ext.select('div.columnDropZone').each(function(el){
			var parsedId = typo3pageModule._parseIdString('contentElementGroup_',el.parent().id);
			var target = new Ext.dd.DDTarget(el,'contentelements_' + parsedId.language);
		});
		Ext.select('div.contentElementDropZoneAfter').each(function(el){
			var parsedId = typo3pageModule._parseIdString('contentElementGroup_',el.parent().parent().id);
			var target = new Ext.dd.DDTarget(el,'contentelements_' + parsedId.language);
		});
	},

	/**
	 * This method is used to higlight all drop zones for a certain
	 * language.
	 *
	 * @param int uid of the language
	 */
	highlightLanguageDropZones: function(languageId){
		Ext.select('div.dropZone.language_'+languageId).each(function(el){
			el.addClass('active');
		});

		Ext.select('table.typo3-page-ceFooter').each(
			function(el){	el.hide(); }
		);
	},

	/**
	 * This method unhighlights all drop zones.
	 */
	unhighlightAllDropZones: function(){
		Ext.select('div.dropZone').each(function(el){
			el.removeClass('active');
		});
		Ext.select('table.typo3-page-ceFooter').each(
			function(el){	el.show(); }
		);
	},

	/**
	 * When an element is dragged a few layers will be hidden
	 * and the z-index will be increased to bring the element
	 * to the front.
	 *
	 * @param Ext.Element element
	 */
	reduceDraggedElement: function(element){
		element.select('table.typo3-page-ceHeader').addClass('displayNone');
	    element.select('span.exampleContent').addClass('displayNone');
	    element.select('div.contentElementDropZoneAfter').addClass('displayNone');
	    element.addClass('onDrag');

	    var footerTable = element.select('table.typo3-page-ceFooter');
	    if(footerTable != null){
	    	footerTable.addClass('displayNone');
	    }

	    var colSubHeader = element.select('tr.colSubHeader td');
	    if(colSubHeader != null){
	    	colSubHeader.addClass('displayNone');
	    }

	    element.setStyle('z-index',999);
	},

	/**
	 * This method is used to display all hidden layers of a dragged
	 * element to bring it to the state before the dragging was started.
	 *
	 * @param Ext.Element element
	 */
	enrichDraggedElement: function(element){
		Ext.get(typo3pageModule.placeholder).remove();

		element.select('table.typo3-page-ceHeader').removeClass('displayNone');
	    element.select('span.exampleContent').removeClass('displayNone');
	    element.select('div.contentElementDropZoneAfter').removeClass('displayNone');
	    element.setStyle('z-index',null);
	    element.removeClass('onDrag');
	    var footerTable = element.select('table.typo3-page-ceFooter');
	    if(footerTable != null){ footerTable.removeClass('displayNone'); }
	},


	/**
	 * Handler method for invalid drops.
	 */
	invalidDrop: function() {
	    // Set a flag to invoke the animated repair
	    this.invalidDrop = true;
	},

	/**
	 * Handler method before dragging starts.
	 */
	b4StartDrag: function(x,y) {
		//disable the menubar highlighting during dragging
	    typo3pageModule.disableHighlighting();

	    // Cache the drag element
	    if (!this.el) { this.el = Ext.get(this.getEl()); }

	    //Cache the original XY Coordinates of the element, we'll use this later.
	    this.originalXY = this.el.getXY();

	    //move the draggable item to the mouse position
	    this.setDelta(0,0);

	    //highlight dropable targets
    	var parsedId = typo3pageModule._parseIdString('ttContent_',this.el.id);
	    typo3pageModule.highlightLanguageDropZones(parsedId.language);

	    //calculate width and height before reducing
	    var heightBefore = this.el.getHeight();
	    var widthBefore  = this.el.getWidth();

	    //make some layers of the draggable element invisible
	    typo3pageModule.reduceDraggedElement(this.el);

	    //caluclate height after reducing
	    var heightAfter = this.el.getHeight();
	    var widthAfter 	= this.el.getWidth();

	    //create a placeholder for the dragged element
	    typo3pageModule.placeholder = new Element('div');
	    typo3pageModule.placeholder.setStyle({position: 'relative', height: heightBefore - heightAfter+ 'px', width: widthBefore - widthAfter+ 'px'});

	    //insert placeholder before the dragged element
	    Ext.get(typo3pageModule.placeholder).insertAfter(this.el.prev());
	 },
	 /**
	  * Handler method for "endDrag" event.
	  * Used to move the element to the prev. position when
	  * the element has been droppen on an invalid location.
	  */
	moveToDropTargetAndEnrich: function() {
	    // Invoke the animation if the invalidDrop flag is set to true

		 if (this.invalidDrop === true) {
	        var animCfgObj = {
	        		easing   : null,
	        		duration : 0,
	                scope    : this,
	        		callback : function() {
	                    // Remove the position attribute
	                    this.el.dom.style.position = '';
	                    this.el.dom.style.left = 0;
	                    this.el.dom.style.top = 0;
	                }
	           };

	        // Apply the repair animation
	        this.el.moveTo(this.originalXY[0], this.originalXY[1],animCfgObj);
	    	typo3pageModule.enrichDraggedElement(this.el);
	        delete this.invalidDrop;
	    }
	    typo3pageModule.unhighlightAllDropZones();
	    typo3pageModule.enableHighlighting();
	},

	/**
	 * Event handler to process a valid drop.
	 */
	handleElementDrop: function(evtObj, targetElId) {
	    // Wrap the drop target element with Ext.Element
	    var dropEl = Ext.get(targetElId);

	    // Perform the node move only if the drag element's
	    // parent is not the same as the drop target
	    if (this.el.dom.parentNode.id != targetElId) {
	        // Move the element
	    	typo3pageModule.enrichDraggedElement(this.el);

	    	var ttContentParsedId = typo3pageModule._parseIdString('ttContent_',this.el.id);
    		var rowParsedId = typo3pageModule._parseIdString('contentElementGroup_',dropEl.findParent('div.contentElementGroup').id);

	    	if(dropEl.hasClass('contentElementDropZoneAfter')){
    			 var dropZoneAfterParsedId = typo3pageModule._parseIdString('contentElementDropZoneAfter_',dropEl.id);
    			 var idBehind = dropZoneAfterParsedId.id;
	    		 this.el.insertAfter(dropEl.parent());
	    	}
	    	if(dropEl.hasClass('columnDropZone')){
			 	var idBehind  = pageId;
	    		this.el.insertAfter(dropEl);
	    	}

	    	var url = pageModuleMoveUrl.replace(/###MOVE_AFTER###/,idBehind).replace(/###MOVE_ITEM###/, ttContentParsedId.id ).replace(/###COL_POS###/,rowParsedId.id);

	    	typo3pageModule.showSpinner();

	    	Ext.Ajax.request({
	    	   url: url,
	    	   success: function(response, opts) {
	    			typo3pageModule.hideSpinner();
	    			window.location.reload();
	    		},
	    	   failure: function(response, opts) { alert('server-side failure with status code ' + response.status); }
	    	});

	    	// Remove the drag invitation
	        this.onDragOut(evtObj, targetElId);

	        // Clear the styles
	        this.el.dom.style.position ='';
	        this.el.dom.style.top = '';
	        this.el.dom.style.left = '';
	    }
	    else {
	        // This was an invalid drop, initiate a repair
	        this.onInvalidDrop();
	    }
	},

	/**
	 * This method is called when an element has been drag over a valid drop zone.
	 * It highlights the drop zone to indicate to the user, that he can drop an item here.
	 *
	 * @param event
	 * @param string idString of the target element.
	 */
	enableDropZone: function(evtObj, targetElId) {
		if (targetElId != this.el.dom.parentNode.id) {
			var dropEl = Ext.get(targetElId);
			dropEl.addClass('validDrop');
		}
	},
	/**
	 * This method is called when an element is removed from a dropZone.
	 *
	 * @param event
	 * @param string idString of the target element.
	 */
	disableDropZone: function(evtObj, targetElId) {
		if (targetElId != this.el.dom.parentNode.id) {

			var dropEl = Ext.get(targetElId);
			dropEl.removeClass('validDrop');
		}
	}
}

Event.observe(window, 'load', function() {
	typo3pageModule.init();
});

var typo3pageModule = {
	placeholder: null,
	el: null,
	idCache: {},
	spinner: null,

	/**
	 * Initialization
	 */
	init: function(){
		typo3pageModule.enableHighlighting();
		typo3pageModule.registerDragAndDrop();
	},

	/**
	 * Each id param contains the id of the element and its language.
	 *
	 * Example: ttContent_4711_1
	 *
	 * To use them on the client side they need to be split. This method
	 * splits the string in to a id and language part and caches it.
	 */
	_parseIdString: function(elementPart,idString){
		if(typo3pageModule.idCache[idString] == null){
			idArray = idString.replace(elementPart,'').split('_');
			var res = {
				id: idArray[0],
				language: idArray[1]
			};

			typo3pageModule.idCache[idString] = res;
		}

		return typo3pageModule.idCache[idString];
	},

	/**
	 * Appends a loading spinner to the body.
	 *
	 */
	showSpinner: function(){
		typo3pageModule.spinner = new Element('div');
		Ext.get(typo3pageModule.spinner).addClass('loadingSpinner').fadeIn({ endOpacity: .75, duration: 1});

		Ext.select('body').appendChild(typo3pageModule.spinner);
	},
	/**
	 * Removes the loading spinner from the body.
	 */
	hideSpinner: function(){
		Ext.get(typo3pageModule.spinner).fadeOut({ endOpacity: 1.0, duration: 1}).remove();
	},
	/**
	 * This method is used as an event handler when the
	 * user hovers the headline of an content element.
	 */
	enableContentHeader: function(e,t){
		var parent = Ext.get(t).findParent('div.contentElement',null,true);
		parent.child('div.ttContentHeader').show();
		parent.addClass('active')
	},

	/**
	 * This method is used as event handler to hide the headline of
	 * an content element when the mouse of the user leaves the
	 * header of the content element.
	 */
	disableContentHeader: function(e,t){
		var parent = Ext.get(t).findParent('div.contentElement',null,true);
		parent.child('div.ttContentHeader').hide();
		parent.removeClass('active');

	},

	/**
	 * This method is used to bind the higlighting function "enableContentHeader"
	 * to the mouseenter event and the "disableContentHeader" to the mouselease event.
	 */
	enableHighlighting: function(){
		Ext.select('div.contentElement').on('mouseenter',typo3pageModule.enableContentHeader,typo3pageModule).on('mouseleave',typo3pageModule.disableContentHeader,typo3pageModule);
	},

	/**
	 * This method is used to unbind the higlighting function "enableContentHeader"
	 * from the mouseenter event and the "disableContentHeader" from the mouselease event.
	 */
	disableHighlighting: function(){
		Ext.select('div.contentElement').un('mouseenter',typo3pageModule.enableContentHeader,typo3pageModule).un('mouseleave',typo3pageModule.disableContentHeader,typo3pageModule);
	},

	/**
	 * This method is used to create the draggable items and the drop zones.
	 *
	 * The method additionaly add eventhandler methods and passes the to the
	 * draggable object.
	 */
	registerDragAndDrop: function(){
		var overrides = {
			// Called the instance the element is dragged.
			b4StartDrag: 	typo3pageModule.b4StartDrag,
			onInvalidDrop: 	typo3pageModule.invalidDrop,
			endDrag: 		typo3pageModule.moveToDropTargetAndEnrich,
			onDragDrop: 	typo3pageModule.handleElementDrop,
			onDragEnter: 	typo3pageModule.enableDropZone,
			onDragOut: 		typo3pageModule.disableDropZone
		};

		Ext.select('div.contentElement').each(function(el){
			var parsedId = typo3pageModule._parseIdString('ttContent_',el.dom.id);
			var dd = new Ext.dd.DD(el, 'contentelements_' + parsedId.language, { isTarget  : false});
		    Ext.apply(dd, overrides);
		});

		Ext.select('div.columnDropZone').each(function(el){
			var parsedId = typo3pageModule._parseIdString('contentElementGroup_',el.parent().id);
			var target = new Ext.dd.DDTarget(el,'contentelements_' + parsedId.language);
		});
		Ext.select('div.contentElementDropZoneAfter').each(function(el){
			var parsedId = typo3pageModule._parseIdString('contentElementGroup_',el.parent().parent().id);
			var target = new Ext.dd.DDTarget(el,'contentelements_' + parsedId.language);
		});
	},

	/**
	 * This method is used to higlight all drop zones for a certain
	 * language.
	 *
	 * @param int uid of the language
	 */
	highlightLanguageDropZones: function(languageId){
		Ext.select('div.dropZone.language_'+languageId).each(function(el){
			el.addClass('active');
		});

		Ext.select('table.typo3-page-ceFooter').each(
			function(el){	el.hide(); }
		);
	},

	/**
	 * This method unhighlights all drop zones.
	 */
	unhighlightAllDropZones: function(){
		Ext.select('div.dropZone').each(function(el){
			el.removeClass('active');
		});
		Ext.select('table.typo3-page-ceFooter').each(
			function(el){	el.show(); }
		);
	},

	/**
	 * When an element is dragged a few layers will be hidden
	 * and the z-index will be increased to bring the element
	 * to the front.
	 *
	 * @param Ext.Element element
	 */
	reduceDraggedElement: function(element){
		element.select('table.typo3-page-ceHeader').addClass('displayNone');
	    element.select('span.exampleContent').addClass('displayNone');
	    element.select('div.contentElementDropZoneAfter').addClass('displayNone');
	    element.addClass('onDrag');

	    var footerTable = element.select('table.typo3-page-ceFooter');
	    if(footerTable != null){
	    	footerTable.addClass('displayNone');
	    }

	    var colSubHeader = element.select('tr.colSubHeader td');
	    if(colSubHeader != null){
	    	colSubHeader.addClass('displayNone');
	    }

	    element.setStyle('z-index',999);
	},

	/**
	 * This method is used to display all hidden layers of a dragged
	 * element to bring it to the state before the dragging was started.
	 *
	 * @param Ext.Element element
	 */
	enrichDraggedElement: function(element){
		Ext.get(typo3pageModule.placeholder).remove();

		element.select('table.typo3-page-ceHeader').removeClass('displayNone');
	    element.select('span.exampleContent').removeClass('displayNone');
	    element.select('div.contentElementDropZoneAfter').removeClass('displayNone');
	    element.setStyle('z-index',null);
	    element.removeClass('onDrag');
	    var footerTable = element.select('table.typo3-page-ceFooter');
	    if(footerTable != null){ footerTable.removeClass('displayNone'); }
	},


	/**
	 * Handler method for invalid drops.
	 */
	invalidDrop: function() {
	    // Set a flag to invoke the animated repair
	    this.invalidDrop = true;
	},

	/**
	 * Handler method before dragging starts.
	 */
	b4StartDrag: function(x,y) {
		//disable the menubar highlighting during dragging
	    typo3pageModule.disableHighlighting();

	    // Cache the drag element
	    if (!this.el) { this.el = Ext.get(this.getEl()); }

	    //Cache the original XY Coordinates of the element, we'll use this later.
	    this.originalXY = this.el.getXY();

	    //move the draggable item to the mouse position
	    this.setDelta(0,0);

	    //highlight dropable targets
    	var parsedId = typo3pageModule._parseIdString('ttContent_',this.el.id);
	    typo3pageModule.highlightLanguageDropZones(parsedId.language);

	    //calculate width and height before reducing
	    var heightBefore = this.el.getHeight();
	    var widthBefore  = this.el.getWidth();

	    //make some layers of the draggable element invisible
	    typo3pageModule.reduceDraggedElement(this.el);

	    //caluclate height after reducing
	    var heightAfter = this.el.getHeight();
	    var widthAfter 	= this.el.getWidth();

	    //create a placeholder for the dragged element
	    typo3pageModule.placeholder = new Element('div');
	    typo3pageModule.placeholder.setStyle({position: 'relative', height: heightBefore - heightAfter+ 'px', width: widthBefore - widthAfter+ 'px'});

	    //insert placeholder before the dragged element
	    Ext.get(typo3pageModule.placeholder).insertAfter(this.el.prev());
	 },
	 /**
	  * Handler method for "endDrag" event.
	  * Used to move the element to the prev. position when
	  * the element has been droppen on an invalid location.
	  */
	moveToDropTargetAndEnrich: function() {
	    // Invoke the animation if the invalidDrop flag is set to true

		 if (this.invalidDrop === true) {
	        var animCfgObj = {
	        		easing   : null,
	        		duration : 0,
	                scope    : this,
	        		callback : function() {
	                    // Remove the position attribute
	                    this.el.dom.style.position = '';
	                    this.el.dom.style.left = 0;
	                    this.el.dom.style.top = 0;
	                }
	           };

	        // Apply the repair animation
	        this.el.moveTo(this.originalXY[0], this.originalXY[1],animCfgObj);
	    	typo3pageModule.enrichDraggedElement(this.el);
	        delete this.invalidDrop;
	    }
	    typo3pageModule.unhighlightAllDropZones();
	    typo3pageModule.enableHighlighting();
	},

	/**
	 * Event handler to process a valid drop.
	 */
	handleElementDrop: function(evtObj, targetElId) {
	    // Wrap the drop target element with Ext.Element
	    var dropEl = Ext.get(targetElId);

	    // Perform the node move only if the drag element's
	    // parent is not the same as the drop target
	    if (this.el.dom.parentNode.id != targetElId) {
	        // Move the element
	    	typo3pageModule.enrichDraggedElement(this.el);

	    	var ttContentParsedId = typo3pageModule._parseIdString('ttContent_',this.el.id);
    		var rowParsedId = typo3pageModule._parseIdString('contentElementGroup_',dropEl.findParent('div.contentElementGroup').id);

	    	if(dropEl.hasClass('contentElementDropZoneAfter')){
    			 var dropZoneAfterParsedId = typo3pageModule._parseIdString('contentElementDropZoneAfter_',dropEl.id);
    			 var idBehind = dropZoneAfterParsedId.id;
	    		 this.el.insertAfter(dropEl.parent());
	    	}
	    	if(dropEl.hasClass('columnDropZone')){
			 	var idBehind  = pageId;
	    		this.el.insertAfter(dropEl);
	    	}

	    	var url = pageModuleMoveUrl.replace(/###MOVE_AFTER###/,idBehind).replace(/###MOVE_ITEM###/, ttContentParsedId.id ).replace(/###COL_POS###/,rowParsedId.id);

	    	typo3pageModule.showSpinner();

	    	Ext.Ajax.request({
	    	   url: url,
	    	   success: function(response, opts) {
	    			typo3pageModule.hideSpinner();
	    			window.location.reload();
	    		},
	    	   failure: function(response, opts) { alert('server-side failure with status code ' + response.status); }
	    	});

	    	// Remove the drag invitation
	        this.onDragOut(evtObj, targetElId);

	        // Clear the styles
	        this.el.dom.style.position ='';
	        this.el.dom.style.top = '';
	        this.el.dom.style.left = '';
	    }
	    else {
	        // This was an invalid drop, initiate a repair
	        this.onInvalidDrop();
	    }
	},

	/**
	 * This method is called when an element has been drag over a valid drop zone.
	 * It highlights the drop zone to indicate to the user, that he can drop an item here.
	 *
	 * @param event
	 * @param string idString of the target element.
	 */
	enableDropZone: function(evtObj, targetElId) {
		if (targetElId != this.el.dom.parentNode.id) {
			var dropEl = Ext.get(targetElId);
			dropEl.addClass('validDrop');
		}
	},
	/**
	 * This method is called when an element is removed from a dropZone.
	 *
	 * @param event
	 * @param string idString of the target element.
	 */
	disableDropZone: function(evtObj, targetElId) {
		if (targetElId != this.el.dom.parentNode.id) {

			var dropEl = Ext.get(targetElId);
			dropEl.removeClass('validDrop');
		}
	}
}

Event.observe(window, 'load', function() {
	typo3pageModule.init();
});
