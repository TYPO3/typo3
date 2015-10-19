/***************************************************
 *  TIPS ON FORM FIELDS AND MENU ITEMS
 ***************************************************/
/*
 * Intercept Ext.form.Field.afterRender in order to provide tips on form fields and menu items
 * Adapted from: http://www.extjs.com/forum/showthread.php?t=36642
 */
/**
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Tips
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent'],
	function (UserAgent) {

	/**
	 *
	 * @type {{tipsOnFormFields: Function, tipsOnMenuItems: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Tips
	 */
	Tips = {
		tipsOnFormFields: function () {
			if (this.helpText || this.helpTitle) {
				if (!this.helpDisplay) {
					this.helpDisplay = 'both';
				}
				var label = this.label;
					// IE has problems with img inside label tag
				if (label && this.helpIcon && !UserAgent.isIE) {
					var helpImage = label.insertFirst({
						tag: 'img',
						src: HTMLArea.editorSkin + 'images/system-help-open.png',
						style: 'vertical-align: middle; padding-right: 2px;'
					});
					if (this.helpDisplay == 'image' || this.helpDisplay == 'both'){
						Ext.QuickTips.register({
							target: helpImage,
							title: this.helpTitle,
							text: this.helpText
						});
					}
				}
				if (this.helpDisplay == 'field' || this.helpDisplay == 'both'){
					Ext.QuickTips.register({
						target: this,
						title: this.helpTitle,
						text: this.helpText
					});
				}
			}
		},
		tipsOnMenuItems: function () {
			if (this.helpText || this.helpTitle) {
				Ext.QuickTips.register({
					target: this,
					title: this.helpTitle,
					text: this.helpText
				});
			}
		}
	};

	Ext.form.Field.prototype.afterRender = Ext.form.Field.prototype.afterRender.createInterceptor(Tips.tipsOnFormFields);
	Ext.menu.BaseItem.prototype.afterRender = Ext.menu.BaseItem.prototype.afterRender.createInterceptor(Tips.tipsOnMenuItems);

	return Tips;
});
