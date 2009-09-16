/***************************************************************
*  Copyright notice
*
*  Copyright (c) 2003 dynarch.com. Authored by Mihai Bazon. Sponsored by www.americanbible.org.
*  Copyright (c) 2004-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Context Menu Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */

ContextMenu = HTMLArea.Plugin.extend({
	
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {
		this.currentMenu = null;
		this.keys = [];
		this.eventHandlers = {};
		
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "2.0",
			developer	: "Mihai Bazon & Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "dynarch.com & Stanislas Rolland",
			sponsor		: "American Bible Society & SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);
		
		return true;
	},
	
	/*
	 * This function gets called when the editor gets generated
	 */
	onGenerate : function() {
		this.editor.eventHandlers["contextMenu"] = this.makeFunctionReference("popupMenu");
		HTMLArea._addEvent((HTMLArea.is_ie ? this.editor._doc.body : this.editor._doc), (HTMLArea.is_opera ? "mousedown" : "contextmenu"), this.editor.eventHandlers["contextMenu"]);
	},
	
	popupMenu : function(ev, target) {
		if (HTMLArea.is_opera && ev.button < 2) {
			return false;
		}
		var editor = this.editor;
		if (!ev) var ev = window.event;
		if (!target) var target = (ev.target) ? ev.target : ev.srcElement;
		if (this.currentMenu) this.currentMenu.parentNode.removeChild(this.currentMenu);
		this.keys = [];
		var ifpos = ContextMenu.getPos(this.editor._iframe);
		var x = ev.clientX + ifpos.x;
		var y = ev.clientY + ifpos.y;
	
		var doc, list, separator = false;
	
		if (!HTMLArea.is_ie) {
			doc = document;
		} else {
			var popup = this.iePopup = window.createPopup();
			doc = popup.document;
			var head = doc.getElementsByTagName("head")[0];
			var link = doc.createElement("link");
			link.rel = "stylesheet";
			link.type = "text/css";
			if ( _editor_CSS.indexOf("http") == -1 ) link.href = _typo3_host_url + _editor_CSS;
				else link.href = _editor_CSS;
			head.appendChild(link);
		}
	
		list = doc.createElement("ul");
		list.className = "htmlarea-context-menu";
		doc.body.appendChild(list);
	
		var options = this.getContextMenu(target);
		var n = options.length;
		for (var i=0; i < n; ++i) {
			var option = options[i];
			if (!option){
				separator = true;
			} else {
				var item = doc.createElement("li");
				list.appendChild(item);
				var label = option[0];
				if (separator) {
					HTMLArea._addClass(item, "separator");
					separator = false;
				}
				item.__msh = {
					item:		item,
					label:		label,
					action:		option[1],
					tooltip:	option[2] || null,
					icon:		option[3] || null,
					activate:	ContextMenu.activateHandler(item, this),
					cmd:		option[4] || null,
					dialog:		option[5] || null
				};
				label = label.replace(/_([a-zA-Z0-9])/, "<u>$1</u>");
				if (label != option[0]) this.keys.push([ RegExp.$1, item ]);
				label = label.replace(/__/, "_");
				var button = doc.createElement("button");
				HTMLArea._addClass(button,  "button");
				if (item.__msh.cmd) {
					HTMLArea._addClass(button,  item.__msh.cmd);
					if (editor._toolbarObjects[item.__msh.cmd]  && editor._toolbarObjects[item.__msh.cmd].active) {
						HTMLArea._addClass(button,  "buttonActive");
					}
				} else if (item.__msh.icon) {
					button.innerHTML = "<img src='" + item.__msh.icon + "' />";
				}
				item.appendChild(button);
				item.innerHTML = item.innerHTML + label;
					// Setting event handlers on the menu items
				item.__msh.mouseover = ContextMenu.mouseOverHandler(editor, item);
				HTMLArea._addEvent(item, "mouseover", item.__msh.mouseover);
				item.__msh.mouseout = ContextMenu.mouseOutHandler(item);
				HTMLArea._addEvent(item, "mouseout", item.__msh.mouseout);
				item.__msh.contextmenu = ContextMenu.itemContextMenuHandler(item);
				HTMLArea._addEvent(item, "contextmenu", item.__msh.contextmenu);
				if (!HTMLArea.is_ie) {
					item.__msh.mousedown = ContextMenu.mouseDownHandler(item);
					HTMLArea._addEvent(item, "mousedown", item.__msh.mousedown);
				}
				item.__msh.mouseup = ContextMenu.mouseUpHandler(item, this);
				HTMLArea._addEvent(item, "mouseup", item.__msh.mouseup);
			}
		}
		if (n) {
			if(!HTMLArea.is_ie) {
				var dx = x + list.offsetWidth - window.innerWidth - window.pageXOffset + 4;
				var dy = y + list.offsetHeight - window.innerHeight - window.pageYOffset + 4;
				if(dx > 0) x -= dx;
				if(dy > 0) y -= dy;
				list.style.left = x + "px";
				list.style.top = y + "px";
			} else {
					// determine the size
				list.style.left = "0px";
				list.style.top = "0px";
				var foobar = document.createElement("ul");
				foobar.className = "htmlarea-context-menu";
				foobar.innerHTML = list.innerHTML;
				editor._iframe.contentWindow.parent.document.body.appendChild(foobar);
				this.iePopup.show(ev.screenX, ev.screenY, foobar.clientWidth+2, foobar.clientHeight+2);
				editor._iframe.contentWindow.parent.document.body.removeChild(foobar);
			}
			this.currentMenu = list;
			this.timeStamp = (new Date()).getTime();
			this.eventHandlers["documentClick"] = this.makeFunctionReference("documentClickHandler");
			HTMLArea._addEvent((HTMLArea.is_ie ? document.body : document), "mousedown", this.eventHandlers["documentClick"]);
			HTMLArea._addEvent((HTMLArea.is_ie ? editor._doc.body : editor._doc), "mousedown", this.eventHandlers["documentClick"]);
			if (this.keys.length > 0) {
				this.eventHandlers["keyPress"] = ContextMenu.keyPressHandler(this);
				HTMLArea._addEvents((HTMLArea.is_ie ? editor._doc.body : editor._doc), ["keypress", "keydown"], this.eventHandlers["keyPress"]);
			}
		}
		HTMLArea._stopEvent(ev);
		return false;
	},
	
	pushOperations : function (opcodes, elmenus, pluginId) {
		var editor = this.editor;
		var pluginInstance = this.editor.plugins[pluginId];
		if (pluginInstance) {
			pluginInstance = pluginInstance.instance;
		}
		var toolbarObjects = editor._toolbarObjects;
		var btnList = editor.config.btnList;
		var enabled = false, opcode, opEnabled = [], i = opcodes.length;
		for (i; i > 0;) {
			opcode = opcodes[--i];
			opEnabled[opcode] = toolbarObjects[opcode]  && toolbarObjects[opcode].enabled;
			enabled = enabled || opEnabled[opcode];
		}
		if (enabled && elmenus.length) {
			elmenus.push(null);
		}
		for (i = opcodes.length; i > 0;) {
			opcode = opcodes[--i];
			if (opEnabled[opcode]) {
				switch (pluginId) {
					case "TableOperations" :
						elmenus.push([
							this.localize(opcode + "-title"),
							ContextMenu.tableOperationsHandler(editor, pluginInstance, opcode),
							this.localize(opcode + "-tooltip"),
							btnList[opcode][1],
							opcode,
							btnList[opcode][7]
							]);
						break;
					case "BlockElements" :
						elmenus.push([this.localize(opcode + "-title"),
							ContextMenu.blockElementsHandler(editor, null, opcode),
							this.localize(opcode + "-tooltip"),
							btnList[opcode][1], opcode]);
						break;
					default :
						elmenus.push([this.localize(opcode + "-title"),
							ContextMenu.execCommandHandler(editor, opcode),
							this.localize(opcode + "-tooltip"),
							btnList[opcode][1], opcode]);
						break;
				}
			}
		}
	},
	
	getContextMenu : function(target) {
		var editor = this.editor;
		var toolbarObjects = editor._toolbarObjects;
		var config = editor.config;
		var btnList = config.btnList;
		var menu = [], opcode;
		var tbo = this.editor.plugins["TableOperations"];
		if (tbo) tbo = "TableOperations";
		
		var selection = editor.hasSelectedText();
		if(selection) {
			if (toolbarObjects['Cut'] && toolbarObjects['Cut'].enabled)  {
				opcode = "Cut";
				menu.push([this.localize(opcode), ContextMenu.execCommandHandler(editor, opcode), null, btnList[opcode][1], opcode]);
			}
			if (toolbarObjects['Copy'] && toolbarObjects['Copy'].enabled) {
				opcode = "Copy";
				menu.push([this.localize(opcode), ContextMenu.execCommandHandler(editor, opcode), null, btnList[opcode][1], opcode]);
			}
		}
		if (toolbarObjects['Paste'] && toolbarObjects['Paste'].enabled) {
			opcode = "Paste";
			menu.push([this.localize(opcode), ContextMenu.execCommandHandler(editor, opcode), null, btnList[opcode][1], opcode]);
		}
		
		var currentTarget = target,
			tmp, tag, link = false,
			table = null, tr = null, td = null, img = null, list = null, div = null;
		
		for(; target; target = target.parentNode) {
			tag = target.nodeName;
			if(!tag) continue;
			tag = tag.toLowerCase();
			switch (tag) {
			    case "img":
				img = target;
				if (toolbarObjects["InsertImage"] && toolbarObjects["InsertImage"].enabled)  {
					if (menu.length) menu.push(null);
					menu.push(
						[this.localize("Image Properties"),
							ContextMenu.imageHandler(editor, img),
							this.localize("Show the image properties dialog"),
							btnList["InsertImage"][1], "InsertImage"]
					);
				}
				break;
			    case "a":
				link = target;
				if (toolbarObjects["CreateLink"])  {
					if (menu.length) menu.push(null);
					menu.push(
						[this.localize("Modify Link"),
							this.linkHandler(link, "ModifyLink"),
							this.localize("Current URL is") + ': ' + link.href,
							btnList["CreateLink"][1], "CreateLink"],
						[this.localize("Check Link"),
							this.linkHandler(link, "CheckLink"),
							this.localize("Opens this link in a new window"),
							null, null],
						[this.localize("Remove Link"),
							this.linkHandler(link, "RemoveLink"),
							this.localize("Unlink the current element"),
							editor.imgURL("ed_unlink.gif"), "UnLink"]
					);
				}
				break;
			    case "td":
			    case "th":
				td = target;
				if(!tbo) break;
				this.pushOperations(["TO-cell-split", "TO-cell-delete", "TO-cell-insert-after", "TO-cell-insert-before", "TO-cell-prop"], menu, tbo);
				break;
			    case "tr":
				tr = target;
				if(!tbo) break;
				opcode = "TO-cell-merge";
				if (toolbarObjects[opcode]  && toolbarObjects[opcode].enabled) {
					menu.push([this.localize(opcode + "-title"),
					ContextMenu.tableOperationsHandler(editor, this.editor.plugins.TableOperations.instance, opcode),
					this.localize(opcode + "-tooltip"),
					btnList[opcode][1], opcode]);
				}
				this.pushOperations(["TO-row-split", "TO-row-delete", "TO-row-insert-under", "TO-row-insert-above", "TO-row-prop"], menu, tbo);
				break;
			    case "table":
				table = target;
				if(!tbo) break;
				this.pushOperations(["TO-col-split", "TO-col-delete", "TO-col-insert-after", "TO-col-insert-before", "TO-col-prop"], menu, tbo);
				this.pushOperations(["TO-toggle-borders", "TO-table-restyle", "TO-table-prop"], menu, tbo);
				break;
			    case "ol":
			    case "ul":
			    case "dl":
				list = target;
				break;
			    case "div":
				div = target;
				break;
			    case "body":
				this.pushOperations(["JustifyFull", "JustifyRight", "JustifyCenter", "JustifyLeft"], menu, "BlockElements");
				break;
			}
		}
		
		if (selection && !link && btnList.CreateLink) {
			if (menu.length) menu.push(null);
			menu.push([this.localize("Make link"),
				this.linkHandler(link, "MakeLink"),
				this.localize("Create a link"),
				btnList["CreateLink"][1],"CreateLink"]);
		}
		
		if (!/^(html|body)$/i.test(currentTarget.nodeName)) {
			if (/^(table|thead|tbody|tr|td|th|tfoot)$/i.test(currentTarget.nodeName)) {
				tmp = table;
				table = null;
			} else if(list) {
				tmp = list;
				list = null;
			} else {
				tmp = currentTarget;
			}
			if (menu.length) menu.push(null);
			menu.push(
			  [this.localize("Remove the") + " &lt;" + tmp.tagName.toLowerCase() + "&gt; " + this.localize("Element"),
				this.deleteElementHandler(tmp, table), this.localize("Remove this node from the document")],
			  [this.localize("Insert paragraph before"),
				ContextMenu.blockElementsHandler(editor, tmp, "InsertParagraphBefore"), this.localize("Insert a paragraph before the current node"), null, "InsertParagraphBefore"],
			  [this.localize("Insert paragraph after"),
				ContextMenu.blockElementsHandler(editor, tmp, "InsertParagraphAfter"), this.localize("Insert a paragraph after the current node"), null, "InsertParagraphAfter"]
			);
		}
		return menu;
	},
	
	closeMenu : function() {
		HTMLArea._removeEvent((HTMLArea.is_ie ? document.body : document), "mousedown", this.eventHandlers["documentClick"]);
		HTMLArea._removeEvent((HTMLArea.is_ie ? this.editor._doc.body : this.editor._doc), "mousedown", this.eventHandlers["documentClick"]);
		if (this.keys.length > 0) HTMLArea._removeEvent((HTMLArea.is_ie ? this.editor._doc.body : this.editor._doc), "keypress", this.eventHandlers["keyPress"]);
		for (var handler in this.eventHandlers) this.eventHandlers[handler] = null;
		var e, items = document.getElementsByTagName("li");
		if (HTMLArea.is_ie) items = this.iePopup.document.getElementsByTagName("li");;
		for (var i = items.length; --i >= 0 ;) {
			e = items[i];
			if ( e.__msh ) {
				HTMLArea._removeEvent(e, "mouseover", e.__msh.mouseover);
				e.__msh.mouseover = null;
				HTMLArea._removeEvent(e, "mouseout", e.__msh.mouseout);
				e.__msh.mouseout = null;
				HTMLArea._removeEvent(e, "contextmenu", e.__msh.contextmenu);
				e.__msh.contextmenu = null;
				if (!HTMLArea.is_ie) HTMLArea._removeEvent(e, "mousedown", e.__msh.mousedown);
				e.__msh.mousedown = null;
				HTMLArea._removeEvent(e, "mouseup", e.__msh.mouseup);
				e.__msh.mouseup = null;
				e.__msh.action = null;
				e.__msh.activate = null;
				e.__msh = null;
			}
		}
		this.currentMenu.parentNode.removeChild(this.currentMenu);
		this.currentMenu = null;
		this.keys = [];
		if (HTMLArea.is_ie) this.iePopup.hide();
	},
	
	linkHandler : function (link, opcode) {
		var editor = this.editor;
		var self = this;
		switch (opcode) {
			case "MakeLink":
			case "ModifyLink":
				return (function() {
					var obj = editor._toolbarObjects.CreateLink;
					obj.cmd(editor, "CreateLink", link);
				});
			case "CheckLink":
				return (function() {
					window.open(link.href);
				});
			case "RemoveLink":
				return (function() {
					if (confirm(self.localize("Please confirm unlink") + "\n" +
						self.localize("Link points to:") + " " + link.href)) {
							var obj = editor._toolbarObjects.CreateLink;
							obj.cmd(editor, "UnLink", link);
					}
				});
		}
	},
	
	deleteElementHandler : function (tmp,table) {
		var editor = this.editor;
		var self = this;
		return (function() {
			if(confirm(self.localize("Please confirm remove") + " " + tmp.tagName.toLowerCase())) {
				var el = tmp;
				var p = el.parentNode;
				p.removeChild(el);
				if(HTMLArea.is_gecko) {
					if(p.tagName.toLowerCase() == "td" && !p.hasChildNodes()) p.appendChild(editor._doc.createElement("br"));
					editor.forceRedraw();
					editor.focusEditor();
					editor.updateToolbar();
					if(table) {
						var save_collapse = table.style.borderCollapse;
						table.style.borderCollapse = "collapse";
						table.style.borderCollapse = "separate";
						table.style.borderCollapse = save_collapse;
					}
				}
			}
		});
	},
	
	documentClickHandler : function (ev) {
		if (!ev) var ev = window.event;
		if (!this.currentMenu) {
			alert(this.localize("How did you get here? (Please report!)"));
			return false;
		}
		var el = (ev.target) ? ev.target : ev.srcElement;
		for (; el != null && el != this.currentMenu; el = el.parentNode);
		if (el == null) {
			this.closeMenu();
			this.editor.updateToolbar();
		}
	}
});

ContextMenu.tableOperationsHandler = function(editor,tbo,opcode) {
	return (function() {
		tbo.onButtonPress(editor,opcode);
	});
};

ContextMenu.imageHandler = function(editor, currentTarget) {
	return (function() {
		var obj = editor._toolbarObjects.InsertImage;
		obj.cmd(editor, obj.name, currentTarget);
		if (HTMLArea.is_opera) {
			editor._iframe.focus();
		}
		if (!editor.config.btnList[obj.name][7]) {
			editor.updateToolbar();
		}
	});
};

ContextMenu.execCommandHandler = function(editor,opcode) {
	return (function() {
		editor.execCommand(opcode);
	});
};

ContextMenu.blockElementsHandler = function(editor, currentTarget, buttonId) {
	return (function() {
		var blockElements = editor.plugins.BlockElements;
		if (blockElements) {
			blockElements = blockElements.instance;
			blockElements.onButtonPress(editor, buttonId, currentTarget);
		} else {
			var el = currentTarget;
			var par = el.parentNode;
			var p = editor._doc.createElement("p");
			var after = (buttonId === "InsertParagraphAfter");
			p.appendChild(editor._doc.createElement("br"));
			par.insertBefore(p, after ? el.nextSibling : el);
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			editor.selectNodeContents(p, true);
		}
	});
};

ContextMenu.mouseOverHandler = function(editor,item) {
	return (function() {
		item.className += " hover";
		if (editor.getPluginInstance("StatusBar")) {
			editor.getPluginInstance("StatusBar").setText(item.__msh.tooltip || "&nbsp;");
		}
	});
};

ContextMenu.mouseOutHandler = function(item) {
	return (function() {
		item.className = item.className.replace(/hover/,"");
	});
};

ContextMenu.itemContextMenuHandler = function(item) {
	return (function(ev) {
		item.__msh.activate();
		if(!HTMLArea.is_ie) HTMLArea._stopEvent(ev);
		return false;
	});
};

ContextMenu.mouseDownHandler = function(item) {
	return (function(ev) {
		HTMLArea._stopEvent(ev);
		return false;
	});
};

ContextMenu.mouseUpHandler = function(item,instance) {
	return (function(ev) {
		var timeStamp = (new Date()).getTime();
		if (timeStamp - instance.timeStamp > 500) {
			item.__msh.activate();
		}
		if (!HTMLArea.is_ie) {
			HTMLArea._stopEvent(ev);
		}
		return false;
	});
};

ContextMenu.activateHandler = function(item,instance) {
	return (function() {
		item.__msh.action();
		if (!item.__msh.dialog) {
			instance.editor.updateToolbar();
		}
		instance.closeMenu();
	});
};

ContextMenu.keyPressHandler = function(instance) {
	return (function(ev) {
		if (!ev) var ev = window.event;
		if (ev.keyCode == 27) {
			instance.closeMenu();
			return false;
		}
		if(ev.altKey && !ev.ctrlKey) {
			var key = String.fromCharCode(HTMLArea.is_ie ? ev.keyCode : ev.charCode).toLowerCase();
			var keys = instance.keys;
			for (var i = keys.length; --i >= 0;) {
				var k = keys[i];
				if (k[0].toLowerCase() == key) k[1].__msh.activate();
			}
			HTMLArea._stopEvent(ev);
			return false;
		}
	});
};

ContextMenu.getPos = function(el) {
	var r = { x: el.offsetLeft, y: el.offsetTop };
	if (el.offsetParent) {
		var tmp = ContextMenu.getPos(el.offsetParent);
		r.x += tmp.x;
		r.y += tmp.y;
	}
	return r;
};

