/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
Ext.ns('Ext.ux.form');

/**
 * @class Ext.ux.form.TextFieldSubmit
 * @extends Ext.form.TriggerField
 * Creates a text field with a submit trigger button
 * @xtype textfieldsubmit
 */
Ext.ux.form.TextFieldSubmit = Ext.extend(Ext.form.TriggerField, {
	hideTrigger: true,

	triggerClass: 'x-form-submit-trigger',

	enableKeyEvents: true,

	onTriggerClick: function() {
		this.setHideTrigger(true);
		if (this.isValid()) {
			this.fireEvent('triggerclick', this);
		} else {
			this.setValue(this.startValue);
		}
	},

	initEvents: function() {
		Ext.ux.form.TextFieldSubmit.superclass.initEvents.call(this);
		this.on('keyup', function(field, event) {
			if (event.getKey() != event.ENTER && this.isValid()) {
				this.setHideTrigger(false);
			} else {
				this.setHideTrigger(true);
			}
		});
		this.on('keypress', function(field, event) {
			if (event.getKey() == event.ENTER) {
				event.stopEvent();
				this.onTriggerClick();
			}
		}, this);
	}
});

Ext.reg('textfieldsubmit', Ext.ux.form.TextFieldSubmit);

//backwards compat
Ext.form.TextFieldSubmit = Ext.ux.form.TextFieldSubmit;
