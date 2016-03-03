Ext.ns('Ext.ux.form');

/**
 * @class Ext.ux.form.FakeFormPanel
 * @extends Ext.form.FormPanel
 *
 * @xtype typo3-form-wizard-fakeformpanel
 */
Ext.ux.form.FakeFormPanel = Ext.extend(Ext.form.FormPanel, {

    initComponent : function(){
        this.form = this.createForm();
        Ext.FormPanel.superclass.initComponent.call(this);

        this.bodyCfg = {
            tag: 'div',
            cls: this.baseCls + '-body',
            method : this.method || 'POST',
            id : this.formId || Ext.id()
        };
        if(this.fileUpload) {
            this.bodyCfg.enctype = 'multipart/form-data';
        }
        this.initItems();

        this.addEvents(
            'clientvalidation'
        );

        this.relayEvents(this.form, ['beforeaction', 'actionfailed', 'actioncomplete']);
    }

});

Ext.reg('typo3-form-wizard-fakeformpanel', Ext.ux.form.FakeFormPanel);
