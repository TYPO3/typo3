/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Image Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
DefaultImage = HTMLArea.Plugin.extend({
	
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		
		this.baseURL = this.editorConfiguration.baseURL;
		this.pageTSConfiguration = this.editorConfiguration.buttons.image;
		if (this.pageTSConfiguration && this.pageTSConfiguration.properties && this.pageTSConfiguration.properties.removeItems) {
			this.removeItems = this.pageTSConfiguration.properties.removeItems.split(",");
				var layout = 0;
				var padding = 0;
				for (var i = 0, length = this.removeItems.length; i < length; ++i) {
					this.removeItems[i] = this.removeItems[i].replace(/(?:^\s+|\s+$)/g, "");
					if (/^(align|border|float)$/i.test(this.removeItems[i])) ++layout;
					if (/^(paddingTop|paddingRight|paddingBottom|paddingLeft)$/i.test(this.removeItems[i])) ++padding;
				}
				if (layout == 3) this.removeItems[this.removeItems.length] = "layout";
				if (layout == 4) this.removeItems[this.removeItems.length] = "padding";
		}
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.ajbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the button
		 */
		var buttonId = "InsertImage";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("insertimage"),
			action		: "onButtonPress",
			hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
			dialog		: true
		};
		this.registerButton(buttonConfiguration);
		
		return true;
	 },
	 
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function(editor, id, target) {
		
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		
		var image, outparam = null;
		this.editor.focusEditor();
		
		if (typeof(target) !== "undefined") {
			image = target;
		} else {
			image = this.editor.getParentElement();
		}
		if (image && !/^img$/i.test(image.nodeName)) {
			image = null;
		}
		if (image) {
			outparam = {
				f_base		: this.baseURL,
				f_url		: image.getAttribute("src"),
				f_alt		: image.alt,
				f_border	: isNaN(parseInt(image.style.borderWidth)) ? "" : parseInt(image.style.borderWidth),
				f_align 	: image.style.verticalAlign,
				f_top		: isNaN(parseInt(image.style.paddingTop)) ? "" : parseInt(image.style.paddingTop),
				f_right		: isNaN(parseInt(image.style.paddingRight)) ? "" : parseInt(image.style.paddingRight),
				f_bottom	: isNaN(parseInt(image.style.paddingBottom)) ? "" : parseInt(image.style.paddingBottom),
				f_left	 	: isNaN(parseInt(image.style.paddingLeft)) ? "" : parseInt(image.style.paddingLeft),
				f_float 	: HTMLArea.is_ie ? image.style.styleFloat : image.style.cssFloat
			};
		}
		this.image = image;
		
		this.dialog = this.openDialog("InsertImage", this.makeUrlFromPopupName("insert_image"), "insertImage", outparam, {width:600, height:610});
		return false;
	},
	
	/*
	 * Insert the image
	 *
	 * @param	object		param: the returned values
	 *
	 * @return	boolean		false
	 */
	insertImage : function(param) {
		if (typeof(param) != "undefined" && typeof(param.f_url) != "undefined") {
			this.editor.focusEditor();
			var image = this.image;
			if (!image) {
				var selection = this.editor._getSelection();
				var range = this.editor._createRange(selection);
				this.editor._doc.execCommand("InsertImage", false, param.f_url);
				if (HTMLArea.is_ie) {
					image = range.parentElement();
					if (!/^img$/i.test(image.nodeName)) {
						image = image.previousSibling;
					}
				} else {
					var selection = this.editor._getSelection();
					var range = this.editor._createRange(selection);
					image = range.startContainer;
					if (HTMLArea.is_opera) {
						image = image.parentNode;
					}
					image = image.lastChild;
					while(image && !/^img$/i.test(image.nodeName)) {
						image = image.previousSibling;
					}
				}
			} else {
				image.src = param.f_url;
			}
			
			for (var field in param) {
				if (param.hasOwnProperty(field)) {
					var value = param[field];
					switch (field) {
						case "f_alt"    :
							image.alt = value;
							break;
						case "f_border" :
							if (parseInt(value)) {
								image.style.borderWidth = parseInt(value)+"px";
								image.style.borderStyle = "solid";
							} else {
								image.style.borderWidth = "";
								image.style.borderStyle = "none";
							}
							break;
						case "f_align"  :
							image.style.verticalAlign = value;
							break;
						case "f_top"   :
							if (parseInt(value)) {
								image.style.paddingTop = parseInt(value)+"px";
							} else {
								image.style.paddingTop = "";
							}
							break;
						case "f_right"  :
							if (parseInt(value)) {
								image.style.paddingRight = parseInt(value)+"px";
							} else {
								image.style.paddingRight = "";
							}
							break;
						case "f_bottom"   :
							if (parseInt(value)) {
								image.style.paddingBottom = parseInt(value)+"px";
							} else {
								image.style.paddingBottom = "";
							}
							break;
						case "f_left"  :
							if (parseInt(value)) {
								image.style.paddingLeft = parseInt(value)+"px";
							} else {
								image.style.paddingLeft = "";
							}
							break;
						case "f_float"  :
							if (HTMLArea.is_ie) {
								image.style.styleFloat = value;
							} else {
								image.style.cssFloat = value;
							}
							break;
					}
				}
			}
			this.dialog.close();
		}
		return false;
	}
});

