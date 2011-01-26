/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ns('Ext.ux.form');

Ext.ux.form.SearchField = Ext.extend(Ext.form.TwinTriggerField, {
	enableKeyEvents: true,
	specialKeyOnly: false,

	initComponent : function(){

		Ext.ux.form.SearchField.superclass.initComponent.call(this);
		this.on('specialkey', function(f, e){
			if(e.getKey() == e.ENTER){
				this.onTrigger2Click();
			}
		}, this);
		if (!this.specialKeyOnly) {
			this.on('keyup', function(f, e){
				var value = this.getRawValue();
				this.onTrigger2Click();
			}, this);
		}
	},
	onRender : function(ct, position){
		this.doc = Ext.isIE ? Ext.getBody() : Ext.getDoc();
		Ext.form.TriggerField.superclass.onRender.call(this, ct, position);

		this.wrap = this.el.wrap({cls: 'x-form-field-wrap x-form-field-trigger-wrap'});
		this.trigger = this.wrap.createChild(this.triggerConfig ||
				{tag: "img", src: Ext.BLANK_IMAGE_URL, alt: "", cls: "x-form-trigger " + this.triggerClass});
		this.initTrigger();
		if(!this.width){
			this.wrap.setWidth(this.el.getWidth()+this.trigger.getWidth());
		}
		this.resizeEl = this.positionEl = this.wrap;
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
	},

	refreshTrigger: function() {
		if (this.getRawValue().length > 0) {
			this.hasSearch = true;
			this.triggers[0].show();
		} else {
			this.hasSearch = false;
			this.triggers[0].hide();
		}
	}
});
