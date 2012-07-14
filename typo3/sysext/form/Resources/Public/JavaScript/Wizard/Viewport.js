Ext.namespace('TYPO3.Form', 'TYPO3.Form.Wizard');

/**
 * The viewport
 *
 * @class TYPO3.Form.Wizard.Viewport
 * @extends Ext.Container
 */
TYPO3.Form.Wizard.Viewport = Ext.extend(Ext.Container, {
	/**
	 * @cfg {String} id
	 * The unique id of this component (defaults to an auto-assigned id).
	 * You should assign an id if you need to be able to access the component
	 * later and you do not have an object reference available
	 * (e.g., using Ext.getCmp).
	 *
	 * Note that this id will also be used as the element id for the containing
	 * HTML element that is rendered to the page for this component.
	 * This allows you to write id-based CSS rules to style the specific
	 * instance of this component uniquely, and also to select sub-elements
	 * using this component's id as the parent.
	 */
	id: 'formwizard',

	/**
	 * @cfg {Boolean} border
	 * True to display the borders of the panel's body element, false to hide
	 * them (defaults to true). By default, the border is a 2px wide inset
	 * border, but this can be further altered by setting bodyBorder to false.
	 */
	border: false,

	/**
	 * @cfg {Mixed} renderTo
	 * Specify the id of the element, a DOM element or an existing Element that
	 * this component will be rendered into.
	 */
	renderTo: 'typo3-inner-docbody',

	/**
	 * @cfg {String} layout
	 * In order for child items to be correctly sized and positioned, typically
	 * a layout manager must be specified through the layout configuration option.
	 *
	 * The sizing and positioning of child items is the responsibility of the
	 * Container's layout manager which creates and manages the type of layout
	 * you have in mind.
	 */
	layout: 'border',

	/**
	 * Constructor
	 *
	 * Add the left and right part to the viewport
	 * Add the history buttons
	 * TODO Move the buttons to the docheader
	 */
	initComponent: function() {
		var config = {
			items: [
				{
					xtype: 'typo3-form-wizard-viewport-left'
				},{
					xtype: 'typo3-form-wizard-viewport-right'
				}
			]
		};

			// Add the buttons to the docheader
		this.addButtonsToDocHeader();

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Add the buttons to the docheader
	 *
	 * All buttons except close will be handled by the form wizard javascript
	 * The save and history buttons are put into separate buttongroups, click
	 * event listeners are added.
	 */
	addButtonsToDocHeader: function() {
		var docHeaderRow1 = Ext.get('typo3-docheader');
		var docHeaderButtonsBar = docHeaderRow1.first('.typo3-docheader-buttons');
		var docHeaderRow1ButtonsLeft = docHeaderButtonsBar.first('.left');

		var saveButtonGroup = Ext.DomHelper.append(docHeaderRow1ButtonsLeft, {
			tag: 'div',
			cls: 'buttongroup'
		});

		var save = new Ext.Element(
			Ext.DomHelper.append(saveButtonGroup, {
				tag: 'span',
				cls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-save',
				id: 'formwizard-save',
				title: TYPO3.l10n.localize('save')
			})
		);

		var saveAndClose = new Ext.Element(
				Ext.DomHelper.append(saveButtonGroup, {
					tag: 'span',
					cls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-save-close',
					id: 'formwizard-saveandclose',
					title: TYPO3.l10n.localize('saveAndClose')
				})
			);

		save.on('click', this.save, this);
		saveAndClose.on('click', this.saveAndClose, this);

		var historyButtonGroup = Ext.DomHelper.append(docHeaderRow1ButtonsLeft, {
			tag: 'div',
			cls: 'buttongroup'
		});

		var undo = new Ext.Element(
			Ext.DomHelper.append(historyButtonGroup, {
				tag: 'span',
				cls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-view-go-back',
				id: 'formwizard-history-undo',
				title: TYPO3.l10n.localize('history_undo')
			})
		);

		var redo = new Ext.Element(
			Ext.DomHelper.append(historyButtonGroup, {
				tag: 'span',
				cls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-view-go-forward',
				id: 'formwizard-history-redo',
				title: TYPO3.l10n.localize('history_redo')
			})
		);

		undo.hide();
		undo.on('click', this.undo, this);

		redo.hide();
		redo.on('click', this.redo, this);
	},

	/**
	 * Save the form
	 *
	 * @param event
	 * @param element
	 * @param object
	 */
	save: function(event, element, object) {
		var configuration = Ext.getCmp('formwizard-right').getConfiguration();

		Ext.Ajax.request({
			url: document.location.href,
			method: 'POST',
			params: {
				action: 'save',
				configuration: Ext.encode(configuration)
			},
			success: function(response, opts) {
				var responseObject = Ext.decode(response.responseText);
				Ext.MessageBox.alert(
					TYPO3.l10n.localize('action_save'),
					responseObject.message
				);
			},
			failure: function(response, opts) {
				var responseObject = Ext.decode(response.responseText);
				Ext.MessageBox.alert(
					TYPO3.l10n.localize('action_save'),
					TYPO3.l10n.localize('action_save_error') + ' ' + response.status
				);
			},
			scope: this
		});
	},

	/**
	 * Save the form and close the wizard
	 *
	 * @param event
	 * @param element
	 * @param object
	 */
	saveAndClose: function(event, element, object) {
		var configuration = Ext.getCmp('formwizard-right').getConfiguration();

		Ext.Ajax.request({
			url: document.location.href,
			method: 'POST',
			params: {
				action: 'save',
				configuration: Ext.encode(configuration)
			},
			success: function(response, opts) {
				var urlParameters = Ext.urlDecode(document.location.search.substring(1));
				document.location = urlParameters['P[returnUrl]'];
			},
			failure: function(response, opts) {
				var responseObject = Ext.decode(response.responseText);
				Ext.MessageBox.alert(
					TYPO3.l10n.localize('action_save'),
					TYPO3.l10n.localize('action_save_error') + ' ' + response.status
				);
			},
			scope: this
		});
	},

	/**
	 * Get the previous snapshot from the history if available
	 *
	 * @param event
	 * @param element
	 * @param object
	 */
	undo: function(event, element, object) {
		TYPO3.Form.Wizard.Helpers.History.undo();
	},

	/**
	 * Get the next snapshot from the history if available
	 *
	 * @param event
	 * @param element
	 * @param object
	 */
	redo: function(event, element, object) {
		TYPO3.Form.Wizard.Helpers.History.redo();
	}
});