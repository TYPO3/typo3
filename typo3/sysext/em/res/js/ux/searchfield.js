/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ns('Ext.ux.form');

Ext.ux.form.SearchField = Ext.extend(Ext.form.TwinTriggerField, {
	charCountTrigger: 0,
	enableKeyEvents: true,

	initComponent : function(){

		Ext.ux.form.SearchField.superclass.initComponent.call(this);
		this.on('specialkey', function(f, e){
			if(e.getKey() == e.ENTER){
				this.onTrigger2Click();
			}
		}, this);
		if (this.charCountTrigger > 0) {
			this.on('keyup', function(f, e){
				var value = this.getRawValue();
				if (value.length > this.charCountTrigger) {
					this.onTrigger2Click();
				}
			}, this);
		}
	},

	validationEvent:false,
	validateOnBlur:false,
	trigger1Class:'x-form-clear-trigger',
	trigger2Class:'x-form-search-trigger',
	hideTrigger1:true,
	width:180,
	hasSearch : false,
	paramName : 'query',
	filterFunction: null,

	onTrigger1Click : function(){
		if(this.hasSearch){
			this.el.dom.value = '';
			var o = {start: 0};
			this.store.baseParams = this.store.baseParams || {};
			this.store.baseParams[this.paramName] = '';
			if (typeof this.filterFunction == "function") {
				this.filterFunction.call();
			} else {
				this.store.reload({params:o});
			}
			this.triggers[0].hide();
			this.hasSearch = false;
		}
	},

	onTrigger2Click : function(){
		var v = this.getRawValue();
		if(v.length < 1){
			this.onTrigger1Click();
			return;
		}
		var o = {start: 0};
		this.store.baseParams = this.store.baseParams || {};
		this.store.baseParams[this.paramName] = v;
		if (typeof this.filterFunction == "function") {
			this.filterFunction.call();
		} else {
			this.store.reload({params:o});
		}
		this.hasSearch = true;
		this.triggers[0].show();
	}
});
