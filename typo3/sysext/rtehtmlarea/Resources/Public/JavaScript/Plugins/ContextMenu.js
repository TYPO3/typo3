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
 * Context Menu Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (Plugin, UserAgent, Util, Dom, Event) {

	var ContextMenu = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(ContextMenu, Plugin);
	Util.apply(ContextMenu.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function(editor) {
			this.pageTSConfiguration = this.editorConfiguration.contextMenu;
			if (!this.pageTSConfiguration) {
				this.pageTSConfiguration = {};
			}
			if (this.pageTSConfiguration.showButtons) {
				this.showButtons = this.pageTSConfiguration.showButtons;
			}
			if (this.pageTSConfiguration.hideButtons) {
				this.hideButtons = this.pageTSConfiguration.hideButtons;
			}
			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '3.2',
				developer	: 'Mihai Bazon & Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'dynarch.com & Stanislas Rolland',
				sponsor		: 'American Bible Society & SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			return true;
		},

		/**
		 * This function gets called when the editor gets generated
		 */
		onGenerate: function() {
			var self = this;
			// Build the context menu
			this.menu = new Ext.menu.Menu(Util.applyIf({
				cls: 'htmlarea-context-menu',
				defaultType: 'menuitem',
				shadow: false,
				// maxHeight is guarded against becoming less than 150px tall by hardcoded boundary. Avoids case of
				// zero-pixel height when calculating the editor document height fails.
				maxHeight: Math.max(this.editor.iframe.height - this.editor.document.documentElement.clientHeight, 150),
				listeners: {
					itemClick: {
						fn: this.onItemClick,
						scope: this
					},
					show: {
						fn: this.onShow,
						scope: this
					},
					hide: {
						fn: this.onHide,
						scope: this
					}
				},
				items: this.buildItemsConfig()
			}, this.pageTSConfiguration));
			// Monitor contextmenu clicks on the iframe
			Event.on(this.editor.document.documentElement, 'contextmenu', function (event) { return self.show(event, event.target); });
			// Monitor editor being unloaded
			Event.one(this.editor.iframe.getIframeWindow(), 'unload', function (event) { self.onBeforeDestroy(event); return  true; });

			this.mousePosition = {
				x: 0,
				y: 0
			};
			var onMouseUpdate = function(e) {
				self.mousePosition.x = e.clientX;
				self.mousePosition.y = e.clientY;
			};
			Event.on(this.editor.document.documentElement, 'mousemove', onMouseUpdate);
			Event.on(this.editor.document.documentElement, 'mouseenter', onMouseUpdate);

			this.menu.constrainScroll = this.constrainScroll;
		},

		/**
		 * This overrides the constrainScroll method of Ext.menu.Menu. The only difference here is that the Y position
		 * and the height is NOT recalculated even if maxHeight is set.
		 *
		 * @param {Number} y
		 * @returns {Number}
		 */
		constrainScroll: function(y) {
			var max, full = this.ul.setHeight('auto').getHeight(),
				returnY = y, normalY, parentEl, scrollTop, viewHeight;
			if (this.floating){
				parentEl = Ext.fly(this.el.dom.parentNode);
				scrollTop = parentEl.getScroll().top;
				viewHeight = parentEl.getViewSize().height;

				normalY = y - scrollTop;
				max = this.maxHeight ? this.maxHeight : viewHeight - normalY;
			} else {
				max = this.getHeight();
			}

			if (this.maxHeight){
				max = Math.min(this.maxHeight, max);
			}
			if (full > max && max > 0){
				this.activeMax = max - this.scrollerHeight * 2 - this.el.getFrameWidth('tb') - Ext.num(this.el.shadowOffset, 0);
				this.ul.setHeight(this.activeMax);
				this.createScrollers();
				this.el.select('.x-menu-scroller').setDisplayed('');
			} else {
				this.ul.setHeight(full);
				this.el.select('.x-menu-scroller').setDisplayed('none');
			}
			this.ul.dom.scrollTop = 0;
			return returnY;
		},

		/**
		 * Create the menu items config
		 */
		buildItemsConfig: function () {
			var itemsConfig = [];
			// Walk through the editor toolbar configuration nested arrays: [ toolbar [ row [ group ] ] ]
			var firstInGroup = true, convertedItemId;
			var i, j ,k, n, m, p, row, group, itemId;
			for (i = 0, n = this.editor.config.toolbar.length; i < n; i++) {
				row = this.editor.config.toolbar[i];
				// Add the groups
				firstInGroup = true;
				for (j = 0, m = row.length; j < m; j++) {
					group = row[j];
					if (!firstInGroup) {
						// If a visible item was added to the line
						itemsConfig.push({
								xtype: 'menuseparator',
								cls: 'separator'
						});
					}
					firstInGroup = true;
					// Add each item
					for (k = 0, p = group.length; k < p; k++) {
						itemId = group[k];
						convertedItemId = this.editorConfiguration.convertButtonId[itemId];
						if ((!this.showButtons || this.showButtons.indexOf(convertedItemId) !== -1)
							&& (!this.hideButtons || this.hideButtons.indexOf(convertedItemId) === -1)) {
							var button = this.getButton(itemId);
							// xtype is set through applied button configuration
							if (button && button.xtype === 'htmlareabutton' && !button.hideInContextMenu) {
								itemId = button.getItemId();
								itemsConfig.push({
									itemId: itemId,
									cls: 'button',
									overCls: 'hover',
									text: (button.contextMenuTitle ? button.contextMenuTitle : button.tooltip),
									iconCls: button.iconCls,
									helpText: (button.helpText ? button.helpText : this.localize(itemId + '-tooltip')),
									hidden: true
								});
								firstInGroup = false;
							}
						}
					}
				}
			}
			// If a visible item was added
			if (!firstInGroup) {
				itemsConfig.push({
						xtype: 'menuseparator',
						cls: 'separator'
				});
			}
			 // Add special target delete item
			itemId = 'DeleteTarget';
			itemsConfig.push({
				itemId: itemId,
				cls: 'button',
				overCls: 'hover',
				iconCls: 'htmlarea-action-delete-item',
				helpText: this.localize('Remove this node from the document')
			});
			return itemsConfig;
		},

		/**
		 * Handler when the menu gets shown
		 */
		onShow: function () {
			var self = this;
			Event.one(this.editor.document.documentElement, 'mousedown.contextmeu', function (event) { Event.stopEvent(event); self.menu.hide(); return false; });
		},

		/**
		 * Handler when the menu gets hidden
		 */
		onHide: function () {
			var self = this;
			Event.off(this.editor.document.documentElement, 'mousedown.contextmeu');
		},

		/**
		 * Handler to show the context menu
		 */
		show: function (event, target) {
			Event.stopEvent(event);
			// Need to wait a while for the toolbar state to be updated
			var self = this;
			window.setTimeout(function () {
				self.showMenu(target);
			}, 150);
			return false;
		},

		/**
		 * Show the context menu
		 */
		showMenu: function (target) {
			this.showContextItems(target);
			this.ranges = this.editor.getSelection().getRanges();
			// Show the context menu
			var iframePosition = Dom.getPosition(this.editor.iframe.getEl());
			this.menu.showAt([
				iframePosition.x + this.mousePosition.x,
				document.body.scrollTop + iframePosition.y + this.mousePosition.y
			]);
		},

		/**
		 * Show items depending on context
		 */
		showContextItems: function (target) {
			var lastIsSeparator = false, lastIsButton = false, xtype, lastVisible;
			this.menu.cascade(function (menuItem) {
				xtype = menuItem.getXType();
				if (xtype === 'menuseparator') {
					menuItem.setVisible(lastIsButton);
					lastIsButton = false;
				} else if (xtype === 'menuitem') {
					var button = this.getButton(menuItem.getItemId());
					if (button) {
						var text = button.contextMenuTitle ? button.contextMenuTitle : button.tooltip;
						if (menuItem.text != text) {
							menuItem.setText(text);
						}
						menuItem.helpText = button.helpText ? button.helpText : menuItem.helpText;
						menuItem.setVisible(!button.disabled);
						lastIsButton = lastIsButton || !button.disabled;
					} else {
						// Special target delete item
						this.deleteTarget = target;
						if (/^(html|body)$/i.test(target.nodeName)) {
							this.deleteTarget = null;
						} else if (/^(table|thead|tbody|tr|td|th|tfoot)$/i.test(target.nodeName)) {
							this.deleteTarget = Dom.getFirstAncestorOfType(target, 'table');
						} else if (/^(ul|ol|dl|li|dd|dt)$/i.test(target.nodeName)) {
							this.deleteTarget = Dom.getFirstAncestorOfType(target, ['ul', 'ol', 'dl']);
						}
						if (this.deleteTarget) {
							menuItem.setVisible(true);
							menuItem.setText(this.localize('Remove the') + ' &lt;' + this.deleteTarget.nodeName.toLowerCase() + '&gt; ');
							lastIsButton = true;
						} else {
							menuItem.setVisible(false);
						}
					}
				}
				if (!menuItem.hidden) {
					lastVisible = menuItem;
				}
			}, this);
				// Hide the last item if it is a separator
			if (!lastIsButton) {
				lastVisible.setVisible(false);
			}
		},

		/**
		 * Handler invoked when a menu item is clicked on
		 */
		onItemClick: function (item, event) {
			this.editor.getSelection().setRanges(this.ranges);
			var button = this.getButton(item.getItemId());
			if (button) {
				/**
				 * @event HTMLAreaEventContextMenu
				 * Fires when the button is triggered from the context menu
				 */
				Event.trigger(button, 'HTMLAreaEventContextMenu', [button]);
			} else if (item.getItemId() === 'DeleteTarget') {
					// Do not leave a non-ie table cell empty
				var parent = this.deleteTarget.parentNode;
				parent.normalize();
				if (!UserAgent.isIE && /^(td|th)$/i.test(parent.nodeName) && parent.childNodes.length == 1) {
						// Do not leave a non-ie table cell empty
					parent.appendChild(this.editor.document.createElement('br'));
				}
					// Try to find a reasonable replacement selection
				var nextSibling = this.deleteTarget.nextSibling;
				var previousSibling = this.deleteTarget.previousSibling;
				if (nextSibling) {
					this.editor.getSelection().selectNode(nextSibling, true);
				} else if (previousSibling) {
					this.editor.getSelection().selectNode(previousSibling, false);
				}
				Dom.removeFromParent(this.deleteTarget);
				this.editor.updateToolbar();
			}
		},

		/**
		 * Handler invoked when the editor is about to be destroyed
		 */
		onBeforeDestroy: function (event) {
			this.menu.removeAll(true);
			this.menu.destroy();
		}
	});

	return ContextMenu;

});
