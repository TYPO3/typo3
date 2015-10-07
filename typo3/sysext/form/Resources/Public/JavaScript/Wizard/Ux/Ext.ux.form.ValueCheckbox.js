Ext.ns('Ext.ux.form');

/**
 * @class Ext.ux.form.ValueCheckbox
 * @extends Ext.form.Checkbox
 * getValue returns inputValue when checked
 *
 * @see TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes.initComponent
 * @xtype typo3-form-wizard-valuecheckbox
 */
Ext.ux.form.ValueCheckbox = Ext.extend(Ext.form.Checkbox, {

	getValue : function(){
		var checked = Ext.ux.form.ValueCheckbox.superclass.getValue.call(this);
		if(this.inputValue !== undefined && checked)
			return this.inputValue;
		return checked;
	}
});

Ext.reg('typo3-form-wizard-valuecheckbox', Ext.ux.form.ValueCheckbox);
