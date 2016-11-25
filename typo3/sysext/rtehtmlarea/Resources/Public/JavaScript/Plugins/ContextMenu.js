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
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'jquery'
], function (Plugin, UserAgent, Util, Dom, Event, $) {

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
			var self = this,
				$iframeDocument = $(this.editor.document.documentElement);

			this.menu =
				'<div id="contentMenu0" class="context-menu htmlarea-context-menu"></div>'
				+ '<div id="contentMenu1" class="context-menu htmlarea-context-menu" style="display: block;"></div>'
			;
			this.menuItems = {};

			// Build the context menu
			this.buildContextMenu();

			$('body').on('click', '#contentMenu1 [data-type="menuitem"]', function(e) {
				e.preventDefault();

				self.onItemClick(self.menuItems[$(this).data('itemId')]);
				$('#contentMenu1').hide();
			}).on('click', '.t3js-ctx-menu-direction', function(e) {
				e.stopPropagation();

				var $me = $(this),
					$firstGroupItem = $me.parent().find('.list-group-item:first'),
					direction = $me.data('direction'),
					// itemHeight is guarded against becoming less than 150px tall by hardcoded boundary. Avoids case of
					// zero-pixel height when calculating the editor document height fails.
					itemHeight = Math.max($firstGroupItem.outerHeight(), 150),
					listGroup = $firstGroupItem.parent(),
					scrollTop = direction === 'down'
						? listGroup.scrollTop() + itemHeight
						: listGroup.scrollTop() - itemHeight;

				listGroup.scrollTop(scrollTop);
			});

			Event.on(this.editor.document.documentElement, 'click', function () {
				$('#contentMenu1').hide();
			});
			Event.on('body', 'click', function () {
				$('#contentMenu1').hide();
			});
			// Monitor contextmenu clicks on the iframe
			Event.on(this.editor.document.documentElement, 'contextmenu', function (event) {
				return self.show(event, event.target);
			});
			// Monitor editor being unloaded
			Event.one(this.editor.iframe.getIframeWindow(), 'unload', function () {
				$('#contentMenu1').remove();
				return true;
			});

			this.mousePosition = {
				x: 0,
				y: 0
			};
			var onMouseUpdate = function(e) {
				self.mousePosition.x = e.pageX;
				self.mousePosition.y = e.pageY;
			};
			$iframeDocument.on('mousemove mouseenter', this.editor.document.documentElement, onMouseUpdate);
		},

		/**
		 * Create the menu items config
		 */
		buildContextMenu: function () {
			// Initialize click menu container
			if ($('#contentMenu0').length === 0) {
				$('body').append(this.menu);
			}

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
						this.menuItems['___' + j] = {
							type: 'menuseparator'
						};
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
								this.menuItems[itemId] = {
									type: 'menuitem',
									itemId: itemId,
									text: (button.contextMenuTitle ? button.contextMenuTitle : button.tooltip),
									iconCls: button.iconCls,
									helpText: (button.helpText ? button.helpText : this.localize(itemId + '-tooltip')),
									hidden: true
								};
								firstInGroup = false;
							}
						}
					}
				}
			}
			// If a visible item was added
			if (!firstInGroup) {
				this.menuItems['___9999'] = {
					type: 'menuseparator'
				};
			}
			 // Add special target delete item
			this.menuItems['DeleteTarget'] = {
				type: 'menuitem',
				itemId: 'DeleteTarget',
				iconCls: 'htmlarea-action-delete-item',
				helpText: this.localize('Remove this node from the document')
			};
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
			$('#contentMenu1').css({
				left: iframePosition.x + this.mousePosition.x + 'px',
				top: document.body.scrollTop + iframePosition.y + this.mousePosition.y + 'px'
			}).show();
		},

		/**
		 * Show items depending on context
		 */
		showContextItems: function (target) {
			var self = this,
				lastIsButton = false,
				lastVisible;

			$.each(this.menuItems, function(itemId, item) {
				if (item.type === 'menuseparator') {
					item.hidden = !lastIsButton;
					lastIsButton = false;
				} else if (item.type === 'menuitem') {
					var button = self.getButton(itemId);
					if (button) {
						var text = button.contextMenuTitle ? button.contextMenuTitle : button.tooltip;
						if (item.text !== text) {
							item.text = text;
						}
						item.helpText = button.helpText ? button.helpText : item.helpText;
						item.hidden = button.disabled;
						lastIsButton = lastIsButton || !button.disabled;
					} else {
						// Special target delete item
						self.deleteTarget = target;
						if (/^(html|body)$/i.test(target.nodeName)) {
							self.deleteTarget = null;
						} else if (/^(table|thead|tbody|tr|td|th|tfoot)$/i.test(target.nodeName)) {
							self.deleteTarget = Dom.getFirstAncestorOfType(target, 'table');
						} else if (/^(ul|ol|dl|li|dd|dt)$/i.test(target.nodeName)) {
							self.deleteTarget = Dom.getFirstAncestorOfType(target, ['ul', 'ol', 'dl']);
						}
						if (self.deleteTarget) {
							item.hidden = false;
							item.text = 'Remove the <' + self.deleteTarget.nodeName.toLowerCase() + '>';
							lastIsButton = true;
						} else {
							item.hidden = true;
						}
					}
				}

				if (!item.hidden) {
					lastVisible = item;
				}
			});

			if (!lastIsButton && typeof lastVisible) {
				lastVisible.hidden = true;
			}

			// Render context menu items
			var $contentMenu = $('#contentMenu1'),
				maxHeight = Math.max(160, this.editor.iframe.height - this.editor.document.documentElement.clientHeight),
				$menuItems = $('<div />', {'class': 'list-group', style: 'max-height: ' + maxHeight + 'px; overflow: hidden;'});
			$.each(this.menuItems, function(_, item) {
				if (typeof item.hidden !== 'undefined' && item.hidden) {
					return true;
				}

				if (item.type === 'menuseparator') {
					$menuItems.append($('<span />', {'class': 'list-group-item list-group-item-divider', 'data-type': item.type}));
				} else if (item.type === 'menuitem') {
					$menuItems.append(
					$('<a />', {href: '#', 'class': 'list-group-item', 'data-type': item.type, 'data-item-id': item.itemId})
						.text(item.text)
						.prepend(
							$('<img />', {
								src: '/typo3/sysext/backend/Resources/Public/Images/clear.gif', 'class': 'ctx-menu-item-icon ' + item.iconCls
							})
						)
					);
				}
			});

			$contentMenu.empty().append(
				$('<button />', {
					'class': 'btn-block ctx-menu-direction ctx-menu-direction-top t3js-ctx-menu-direction',
					type: 'button',
					'data-direction': 'up'
				}),
				$menuItems,
				$('<button />', {
					'class': 'btn-block ctx-menu-direction ctx-menu-direction-bottom t3js-ctx-menu-direction',
					type: 'button',
					'data-direction': 'down'
				})
			);
		},

		/**
		 * Handler invoked when a menu item is clicked on
		 */
		onItemClick: function (item) {
			this.editor.getSelection().setRanges(this.ranges);
			var button = this.getButton(item.itemId);
			if (button) {
				/**
				 * @event HTMLAreaEventContextMenu
				 * Fires when the button is triggered from the context menu
				 */
				Event.trigger(button, 'HTMLAreaEventContextMenu', [button]);
			} else if (item.itemId === 'DeleteTarget') {
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
		}
	});

	return ContextMenu;
});