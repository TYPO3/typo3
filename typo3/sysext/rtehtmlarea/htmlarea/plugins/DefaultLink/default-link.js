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
 * Default Link Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
DefaultLink = HTMLArea.Plugin.extend({
	
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		
		this.baseURL = this.editorConfiguration.baseURL;
		this.pageTSConfiguration = this.editorConfiguration.buttons.link;
		this.stripBaseUrl = this.pageTSConfiguration && this.pageTSConfiguration.stripBaseUrl && this.pageTSConfiguration.stripBaseUrl;
		this.showTarget = !(this.pageTSConfiguration && this.pageTSConfiguration.targetSelector && this.pageTSConfiguration.targetSelector.disabled);
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
		 * Registering the button
		 */
		var buttonId = "CreateLink";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("createlink"),
			action		: "onButtonPress",
			hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
			context		: "a",
			selection	: true,
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
		
		if (buttonId === "UnLink") {
			this.unLink();
			return false;
		}
		
		var paramameters = null;
		this.editor.focusEditor();
		var link = this.editor.getParentElement();
		var el = HTMLArea.getElementObject(link, "a");
		if (el != null && /^a$/i.test(el.nodeName)) link = el;
		if (!link || !/^a$/i.test(link.nodeName)) {
			link = null;
			var selection = this.editor._getSelection();
			if (this.editor._selectionEmpty(selection)) {
				alert(this.localize("Select some text"));
				return;
			}
			paramameters = {
				f_href : "",
				f_title : "",
				f_target : ""
			};
		} else {
			paramameters = {
				f_href   : (HTMLArea.is_ie && this.stripBaseUrl) ? this.stripBaseURL(link.href) : link.getAttribute("href"),
				f_title  : link.title,
				f_target : link.target
			};
		}
		
		this.link = link;
		this.dialog = this.openDialog("CreateLink", this.makeUrlFromPopupName("link"), "createLink", paramameters, {width:570, height:150});
		return false;
	},
	
	/*
	 * Create the link
	 *
	 * @param	object		param: the returned values
	 *
	 * @return	boolean		false
	 */
	createLink : function(param) {
		if (typeof(param) != "undefined" && typeof(param.f_href) != "undefined") {
			var a = this.link;
			if(!a) {
				this.editor._doc.execCommand("CreateLink", false, param.f_href);
				a = this.editor.getParentElement();
				if (HTMLArea.is_gecko && !/^a$/i.test(a.nodeName)) {
					var selection = this.editor._getSelection();
					var range = this.editor._createRange(selection);
					try {
						a = range.startContainer.childNodes[range.startOffset];
					} catch(e) {}
				}
			} else {
				var href = param.f_href.trim();
				this.editor.selectNodeContents(a);
				if (href == "") {
					this.editor._doc.execCommand("Unlink", false, null);
					this.dialog.close();
					return false;
				} else {
					a.href = href;
				}
			}
			if (!(a && /^a$/i.test(a.nodeName))) {
				this.dialog.close();
				return false;
			}
			if (typeof(param.f_target) != "undefined") a.target = param.f_target.trim();
			if (typeof(param.f_title) != "undefined") a.title = param.f_title.trim();
			this.editor.selectNodeContents(a);
			this.dialog.close();
		}
		return false;
	},
	
	/*
	 * Unlink the selection.
	 *
	 * @param	object		link: the link element to unlink
	 *
	 * @return	boolean		false
	 */
	unLink : function () {
		this.editor.focusEditor();
		var node = this.editor.getParentElement();
		var el = HTMLArea.getElementObject(node, "a");
		if (el != null && /^a$/i.test(el.nodeName)) node = el;
		if (node != null && /^a$/i.test(node.nodeName)) this.editor.selectNode(node);
		this.editor._doc.execCommand("Unlink", false, "");
	},
	
	/*
	 * IE makes relative links absolute. This function reverts this conversion.
	 *
	 * @param	string		url: the url
	 *
	 * @return	string		the url stripped out of the baseurl
	 */
	stripBaseURL : function(url) {
		var baseurl = this.baseURL;
			// strip to last directory in case baseurl points to a file
		baseurl = baseurl.replace(/[^\/]+$/, '');
		var basere = new RegExp(baseurl);
		url = url.replace(basere, "");
			// strip host-part of URL which is added by MSIE to links relative to server root
		baseurl = baseurl.replace(/^(https?:\/\/[^\/]+)(.*)$/, '$1');
		basere = new RegExp(baseurl);
		return url.replace(basere, "");
	}
});

