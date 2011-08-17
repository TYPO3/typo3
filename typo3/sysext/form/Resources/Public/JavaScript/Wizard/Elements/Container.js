Ext.namespace('TYPO3.Form.Wizard');

/**
 * Container abstract
 *
 * There are only two containers in a form, the form itself and fieldsets.
 *
 * @class TYPO3.Form.Wizard.Elements.Container
 * @extends Ext.Container
 */
TYPO3.Form.Wizard.Container = Ext.extend(Ext.Container, {
	/**
	 * @cfg {Mixed} autoEl
	 * A tag name or DomHelper spec used to create the Element which will
	 * encapsulate this Component.
	 */
	autoEl: 'ol',

	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'formwizard-container',

	/**
	 * @cfg {Object|Function} defaults
	 * This option is a means of applying default settings to all added items
	 * whether added through the items config or via the add or insert methods.
	 */
	defaults: {
		autoHeight: true
	},

	/**
	 * Constructor
	 *
	 * Add the dummy to the container
	 */
	constructor: function(config) {
		Ext.apply(this, {
			items: [
				{
					xtype: 'typo3-form-wizard-elements-dummy'
				}
			]
		});
		TYPO3.Form.Wizard.Container.superclass.constructor.apply(this, arguments);
	},


	/**
	 * Constructor
	 */
	initComponent: function() {
		var config = {};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Container.superclass.initComponent.apply(this, arguments);

			// Initialize the drag and drop zone after rendering
		if (this.hasDragAndDrop) {
			this.on('render', this.initializeDragAndDrop, this);
		}

		this.on('render', this.checkOnEmpty, this);

			// Initialize the remove event, which will be fired when a component is removed from this container
		this.on('remove', this.checkOnEmpty, this);
	},

	/**
	 * Initialize the drag and drop zones
	 *
	 * @param container
	 */
	initializeDragAndDrop: function(container) {
		/**
		 * Initialize the drag zone
		 *
		 * A container can contain elements which can be moved within this and
		 * other (nested) containers.
		 */
		container.dragZone = new Ext.dd.DragZone(container.getEl(), {
			/**
			 * Called when a mousedown occurs in this container. Looks in Ext.dd.Registry
			 * for a valid target to drag based on the mouse down. Override this method
			 * to provide your own lookup logic (e.g. finding a child by class name). Make sure your returned
			 * object has a "ddel" attribute (with an HTML Element) for other functions to work.
			 * @param {EventObject} element The mouse down event element
			 * @return {Object} The dragData
			 */
			getDragData: function(element) {
				var sourceElement = element.getTarget('.formwizard-element');
				var sourceComponent = Ext.getCmp(sourceElement.id);
				if (sourceElement && sourceComponent.isEditable) {
					clonedElement = sourceElement.cloneNode(true);
					clonedElement.id = Ext.id();
					return container.dragData = {
						sourceEl: sourceElement,
						repairXY: Ext.fly(sourceElement).getXY(),
						ddel: clonedElement
					};
				}
			},

			onStartDrag: function(x, y) {
				Ext.getCmp('formwizard').addClass('hover-move');
			},

			endDrag: function(event) {
				Ext.getCmp('formwizard').removeClass('hover-move');
			},

			/**
			 * Called before a repair of an invalid drop to get the XY to animate to.
			 * By default returns the XY of this.dragData.ddel
			 * @param {EventObject} e The mouse up event
			 * @return {Array} The xy location (e.g. [100, 200])
			 */
			getRepairXY: function(e) {
				return container.dragData.repairXY;
			}
		});

		/**
		 * Initialize the drop zone
		 *
		 * A container can receive other form elements or other (nested) containers.
		 */
		container.dropZone = new Ext.dd.DropZone(container.getEl(), {
			/**
			 * Returns a custom data object associated with the DOM node that is the target of the event.  By default
			 * this looks up the event target in the Ext.dd.Registry, although you can override this method to
			 * provide your own custom lookup.
			 *
			 * The override has been done here to define if we are having this event on the container or a form element.
			 *
			 * @param {Event} e The event
			 * @return {Object} data The custom data
			 */
			getTargetFromEvent: function(event) {

				var containerElement = container.getEl();
				var formElementTarget = event.getTarget('.formwizard-element', 10, true);
				var formContainerTarget = event.getTarget('.formwizard-container', 10, true);
				var placeholderTarget = event.getTarget('#element-placeholder', 10, false);

				if (placeholderTarget) {
					formElementTarget = Ext.DomQuery.selectNode('.target-hover');
				}

				if (
					container.hasDragAndDrop &&
					formContainerTarget &&
					formElementTarget &&
					formContainerTarget.findParentNode('li', 10, true) == formElementTarget &&
					formContainerTarget == containerElement
				) {
					return null;
					// We are having this event on a form element
				} else if (
					container.hasDragAndDrop &&
					formElementTarget
				) {
					if (placeholderTarget) {
						return formElementTarget;
					}
					return event.getTarget('.formwizard-element');
					// We are having this event on a container
				} else {
					return null;
				}
			},

			/**
			 * Called while the DropZone determines that a Ext.dd.DragSource is being dragged over it,
			 * but not over any of its registered drop nodes.  The default implementation returns this.dropNotAllowed, so
			 * it should be overridden to provide the proper feedback if necessary.
			 *
			 * And so we did ;-) We are not using containers which can receive different elements, so we always return
			 * Ext.dd.DropZone.prototype.dropAllowed CSS class.
			 *
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @param {Event} e The event
			 * @param {Object} data An object containing arbitrary data supplied by the drag source
			 * @return {String} status The CSS class that communicates the drop status back to the source so that the
			 * underlying Ext.dd.StatusProxy can be updated
			 */
			onContainerOver: function(dd, e, data) {
				if (Ext.get('element-placeholder')) {
					Ext.get('element-placeholder').remove();
				}
				return Ext.dd.DropZone.prototype.dropAllowed;
			},

			/**
			 * Called when the DropZone determines that a Ext.dd.DragSource has been dropped on it,
			 * but not on any of its registered drop nodes.  The default implementation returns false, so it should be
			 * overridden to provide the appropriate processing of the drop event if you need the drop zone itself to
			 * be able to accept drops.  It should return true when valid so that the drag source's repair action does not run.
			 *
			 * This is a tricky part. Because we are using multiple dropzones which are on top of each other, the event will
			 * be called multiple times, for each group one time. We cannot prevent this by disabling event bubbling and we
			 * dont't want to override the core of ExtJS. To prevent multiple creation of the same object, we add the variable
			 * 'processed' to the 'data' object. If it has been processed on drop, it will not be done a second time.
			 *
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @param {Event} e The event
			 * @param {Object} data An object containing arbitrary data supplied by the drag source
			 * @return {Boolean} True if the drop was valid, else false
			 */
			onContainerDrop: function(dd, e, data) {
				if (
					container.hasDragAndDrop &&
					!data.processed
				) {
					var dropComponent = Ext.getCmp(data.sourceEl.id);
					container.dropElement(dropComponent, 'container');
					data.processed = true;
				}
				return true;
			},

			/**
			 * Called when the DropZone determines that a Ext.dd.DragSource has entered a drop node
			 * that has either been registered or detected by a configured implementation of getTargetFromEvent.
			 * This method has no default implementation and should be overridden to provide
			 * node-specific processing if necessary.
			 *
			 * Our implementation adds a dummy placeholder before or after the element the user is hovering over.
			 * This placeholder will show the user where the dragged element will be dropped in the form.
			 *
			 * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
			 * getTargetFromEvent for this node)
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @param {Event} e The event
			 * @param {Object} data An object containing arbitrary data supplied by the drag source
			 */
			onNodeEnter : function(target, dd, e, data) {
				if (
					Ext.get(data.sourceEl).hasClass('formwizard-element') &&
					target.id != data.sourceEl.id
				) {
					var dropPosition = this.getDropPosition(target, dd);
					if (dropPosition == 'above') {
						Ext.DomHelper.insertBefore(target, {
							tag: 'li',
							id: 'element-placeholder',
							html: '&nbsp;'
						});
					} else {
						Ext.DomHelper.insertAfter(target, {
							tag: 'li',
							id: 'element-placeholder',
							html: '&nbsp;'
						});
					}
					Ext.fly(target).addClass('target-hover');
				}
			},

			/**
			 * Called when the DropZone determines that a Ext.dd.DragSource has been dragged out of
			 * the drop node without dropping.  This method has no default implementation and should be overridden to provide
			 * node-specific processing if necessary.
			 *
			 * Removes the temporary placeholder and the hover class from the element
			 *
			 * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
			 * getTargetFromEvent for this node)
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @param {Event} e The event
			 * @param {Object} data An object containing arbitrary data supplied by the drag source
			 */
			onNodeOut : function(target, dd, e, data) {
				if (
						Ext.get(data.sourceEl).hasClass('formwizard-element') &&
						target.id != data.sourceEl.id
					) {
					if (e.type != 'mouseup') {
						if (Ext.get('element-placeholder')) {
							Ext.get('element-placeholder').remove();
						}
						Ext.fly(target).removeClass('target-hover');
					}
				}
			},

			/**
			 * Called while the DropZone determines that a Ext.dd.DragSource is over a drop node
			 * that has either been registered or detected by a configured implementation of getTargetFromEvent.
			 * The default implementation returns this.dropNotAllowed, so it should be
			 * overridden to provide the proper feedback.
			 *
			 * Based on the cursor position on the node we are hovering over, the temporary placeholder will be put
			 * above or below this node. If the position changes, the placeholder will be removed and put at the
			 * right spot.
			 *
			 * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
			 * getTargetFromEvent for this node)
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @param {Event} e The event
			 * @param {Object} data An object containing arbitrary data supplied by the drag source
			 * @return {String} status The CSS class that communicates the drop status back to the source so that the
			 * underlying Ext.dd.StatusProxy can be updated
			 */
			onNodeOver: function(target, dd, e, data) {
				if (
						Ext.get(data.sourceEl).hasClass('formwizard-element') &&
						target.id != data.sourceEl.id
				) {
					var dropPosition = this.getDropPosition(target, dd);
						// The position of the target moved to the top
					if (
						dropPosition == 'above' &&
						target.nextElementSibling &&
						target.nextElementSibling.id == 'element-placeholder'
					) {
						Ext.get('element-placeholder').remove();
						Ext.DomHelper.insertBefore(target, {
							tag: 'li',
							id: 'element-placeholder',
							html: '&nbsp;'
						});
					} else if (
						dropPosition == 'below' &&
						target.previousElementSibling &&
						target.previousElementSibling.id == 'element-placeholder'
					) {
						Ext.get('element-placeholder').remove();
						Ext.DomHelper.insertAfter(target, {
							tag: 'li',
							id: 'element-placeholder',
							html: '&nbsp;'
						});
					}
					return Ext.dd.DropZone.prototype.dropAllowed;
				} else {
					return Ext.dd.DropZone.prototype.dropNotAllowed;
				}
			},

			/**
			 * Called when the DropZone determines that a Ext.dd.DragSource has been dropped onto
			 * the drop node.  The default implementation returns false, so it should be overridden to provide the
			 * appropriate processing of the drop event and return true so that the drag source's repair action does not run.
			 *
			 * Like onContainerDrop this is a tricky part. Because we are using multiple dropzones which are on top of each other, the event will
			 * be called multiple times, for each group one time. We cannot prevent this by disabling event bubbling and we
			 * dont't want to override the core of ExtJS. To prevent multiple creation of the same object, we add the variable
			 * 'processed' to the 'data' object. If it has been processed on drop, it will not be done a second time.
			 *
			 *
			 * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
			 * getTargetFromEvent for this node)
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @param {Event} e The event
			 * @param {Object} data An object containing arbitrary data supplied by the drag source
			 * @return {Boolean} True if the drop was valid, else false
			 */
			onNodeDrop : function(target, dd, e, data) {
				if (
					Ext.get(data.sourceEl).hasClass('formwizard-element') &&
					target.id != data.sourceEl.id &&
					!data.processed
				) {

					var dropPosition = this.getDropPosition(target, dd);
					var dropComponent = Ext.getCmp(data.sourceEl.id);
					container.dropElement(dropComponent, dropPosition, target);
					data.processed = true;
					return true;
				}
			},
			/**
			 * Defines whether we are hovering at the top or bottom half of a node
			 *
			 * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
			 * getTargetFromEvent for this node)
			 * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
			 * @return {String} above when hovering over the top half, below if at the bottom half.
			 */
			getDropPosition: function(target, dd) {
				var top = Ext.lib.Dom.getY(target);
				var bottom = top + target.offsetHeight;
				var center = ((bottom - top) / 2) + top;
				var yPosition = dd.lastPageY + dd.deltaY;
				if (yPosition < center) {
					return 'above';
				} else if (yPosition >= center) {
					return 'below';
				}
			}
		});
	},

	/**
	 * Called by the dropzones onContainerDrop or onNodeDrop.
	 * Adds the component to the container.
	 *
	 * This function will look if it is a new element from the left buttons, if
	 * it is an existing element which is moved within this or from another
	 * container. It also decides if it is dropped within an empty container or
	 * if it needs a position within the existing elements of this container.
	 *
	 * @param component
	 * @param position
	 * @param target
	 */
	dropElement: function(component, position, target) {
			// Check if there are errors in the current active element
		var optionsTabIsValid = Ext.getCmp('formwizard-left-options').tabIsValid();

		var id = component.id;
		var droppedElement = {};

		if (Ext.get('element-placeholder')) {
			Ext.get('element-placeholder').remove();
		}
			// Only add or move an element when there is no error in the current active element
		if (optionsTabIsValid) {
				// New element in container
			if (position == 'container') {
					// Check if the dummy is present, which means there are no elements
				var dummy = this.findById('dummy');
				if (dummy) {
					this.remove(dummy, true);
				}
					// Add the new element to the container
				if (component.xtype != 'button') {
					droppedElement = this.add(
						component
					);
				} else {
					droppedElement = this.add({
						xtype: 'typo3-form-wizard-elements-' + id
					});
				}

				// Moved an element within this container
			} else if (this.findById(id)) {
				droppedElement = this.findById(id);
				var movedElementIndex = 0;
				var targetIndex = this.items.findIndex('id', target.id);

				if (position == 'above') {
					movedElementIndex = targetIndex;
				} else {
					movedElementIndex = targetIndex + 1;
				}

					// Tricky part, because this.remove does not remove the DOM element
					// See http://www.sencha.com/forum/showthread.php?102190
					// 1. remove component from container w/o destroying (2nd argument false)
					// 2. remove component's element from container and append it to body
					// 3. add/insert the component to the correct place back in the container
					// 4. call doLayout() on the container
				this.remove(droppedElement, false);
				var element = Ext.get(droppedElement.id);
				element.appendTo(Ext.getBody());

				this.insert(
					movedElementIndex,
					droppedElement
				);

				// New element for this container coming from another one
			} else {
				var index = 0;
				var targetIndex = this.items.findIndex('id', target.id);

				if (position == 'above') {
					index = targetIndex;
				} else {
					index = targetIndex + 1;
				}

					// Element moved
				if (component.xtype != 'button') {
					droppedElement = this.insert(
						index,
						component
					);
					// Coming from buttons
				} else {
					droppedElement = this.insert(
						index,
						{
							xtype: 'typo3-form-wizard-elements-' + id
						}
					);
				}
			}
			this.doLayout();
			TYPO3.Form.Wizard.Helpers.History.setHistory();
			TYPO3.Form.Wizard.Helpers.Element.setActive(droppedElement);

			// The current active element has errors, show it!
		} else {
			Ext.MessageBox.show({
				title: TYPO3.l10n.localize('options_error'),
				msg: TYPO3.l10n.localize('options_error_message'),
				icon: Ext.MessageBox.ERROR,
				buttons: Ext.MessageBox.OK
			});
		}
	},

	/**
	 * Remove the element from this container
	 *
	 * @param element
	 */
	removeElement: function(element) {
		this.remove(element);
		TYPO3.Form.Wizard.Helpers.History.setHistory();
	},

	/**
	 * Called by the 'remove' event of this container.
	 *
	 * If an item has been removed from this container, except for the dummy
	 * element, it will look if there are other items existing. If not, it will
	 * put the dummy in this container to tell the user the container needs items.
	 *
	 * @param container
	 * @param component
	 */
	checkOnEmpty: function(container, component) {
		if (component && component.id != 'dummy' || !component) {
			if (this.items.getCount() == 0) {
				this.add({
					xtype: 'typo3-form-wizard-elements-dummy'
				});
				this.doLayout();
			}
		}
	},

	/**
	 * Called by the parent of this component when a change has been made in the
	 * form.
	 *
	 * Constructs an array out of this component and the children to add it to
	 * the history or to use when saving the form
	 *
	 * @returns {Array}
	 */
	getConfiguration: function() {
		var historyConfiguration = {
			hasDragAndDrop: this.hasDragAndDrop
		};

		if (this.items) {
			historyConfiguration.items = [];
			this.items.each(function(item, index, length) {
				historyConfiguration.items.push(item.getConfiguration());
			}, this);
		}
		return historyConfiguration;
	}
});

Ext.reg('typo3-form-wizard-container', TYPO3.Form.Wizard.Container);