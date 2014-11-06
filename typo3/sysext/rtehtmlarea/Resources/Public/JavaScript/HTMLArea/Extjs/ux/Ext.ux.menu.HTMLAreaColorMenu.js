/**
 * Color menu
 */
Ext.ux.menu.HTMLAreaColorMenu = Ext.extend(Ext.menu.Menu, {
	enableScrolling: false,
	hideOnClick: true,
	cls: 'x-color-menu',
	colorPaletteValue: '',
	customColorsValue: '',
	plain: true,
	showSeparator: false,
	initComponent: function () {
		var paletteItems = [];
		var width = 'auto';
		if (this.colorsConfiguration) {
			paletteItems.push({
				xtype: 'container',
				layout: 'anchor',
				width: 160,
				style: { float: 'right' },
				items: {
					xtype: 'colorpalette',
					itemId: 'custom-colors',
					cls: 'htmlarea-custom-colors',
					colors: this.colorsConfiguration,
					value: this.value,
					allowReselect: true,
					tpl: new Ext.XTemplate(
						'<tpl for="."><a href="#" class="color-{1}" hidefocus="on"><em><span style="background:#{1}" unselectable="on">&#160;</span></em><span unselectable="on">{0}</span></a></tpl>'
					)
				}
			});
		}
		if (this.colors.length) {
			paletteItems.push({
				xtype: 'container',
				layout: 'anchor',
				items: {
					xtype: 'colorpalette',
					itemId: 'color-palette',
					cls: 'color-palette',
					colors: this.colors,
					value: this.value,
					allowReselect: true
				}
			});
		}
		if (this.colorsConfiguration && this.colors.length) {
			width = 350;
		}
		Ext.apply(this, {
			layout: 'menu',
			width: width,
			items: paletteItems
		});
		Ext.ux.menu.HTMLAreaColorMenu.superclass.initComponent.call(this);
		this.standardPalette = this.find('itemId', 'color-palette')[0];
		this.customPalette = this.find('itemId', 'custom-colors')[0];
		if (this.standardPalette) {
			this.standardPalette.purgeListeners();
			this.relayEvents(this.standardPalette, ['select']);
		}
		if (this.customPalette) {
			this.customPalette.purgeListeners();
			this.relayEvents(this.customPalette, ['select']);
		}
		this.on('select', this.menuHide, this);
		if (this.handler){
			this.on('select', this.handler, this.scope || this);
		}
	},
	menuHide: function() {
		if (this.hideOnClick){
			this.hide(true);
		}
	}
});
Ext.reg('htmlareacolormenu', Ext.ux.menu.HTMLAreaColorMenu);
