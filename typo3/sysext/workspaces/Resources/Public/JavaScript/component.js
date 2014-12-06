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


Ext.ns('TYPO3.Workspaces');

Ext.override(Ext.grid.GroupingView, {
	constructId : function(value, field, idx) {
		var cfg = this.cm.config[idx],
			groupRenderer = cfg.groupRenderer || cfg.renderer,
			val = (this.groupMode == 'value') ? value : this.getGroup(value, {data:{}}, groupRenderer, 0, idx, this.ds);

		var id = this.getPrefix(field) + val;
		id = id.replace(/[^a-zA-Z0-9_]/g, '');
		return id;
	}
});

Ext.ns('Ext.ux.TYPO3.Workspace');
Ext.ux.TYPO3.Workspace.RowPanel = Ext.extend(Ext.Panel, {
	constructor: function(config) {
		config = config || {
			frame:true,
			width:'100%',
			autoHeight:true,
			layout:'fit',
			title: TYPO3.l10n.localize('rowDetails')
		};
		Ext.apply(this, config);
		Ext.ux.TYPO3.Workspace.RowPanel.superclass.constructor.call(this, config);
	}
});

TYPO3.Workspaces.RowExpander = new TYPO3.Workspaces.Component.RowExpander();
