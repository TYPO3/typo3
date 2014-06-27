/**
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

/**
 * Javascript functions regarding the permissions module
 * Relies on the javascript library "prototype"
 */

function checkChange(checknames, varname) {
	var res = 0;
	for (var a=1; a<=5; a++)	{
		if (document.editform[checknames+'['+a+']'].checked) {
			res|=Math.pow(2,a-1);
		}
	}
	document.editform[varname].value = res | (checknames == 'check[perms_user]' ? 1 : 0);
	setCheck(checknames, varname);
}

function setCheck(checknames, varname) {
	if (document.editform[varname])	{
		var res = document.editform[varname].value;
		for (var a=1; a<=5; a++) {
			document.editform[checknames+'['+a+']'].checked = (res & Math.pow(2,a-1));
		}
	}
}

function jumpToUrl(URL)	{ window.location.href = URL; }

// Methods for AJAX permission manipulation
var WebPermissions = {

    thisScript: TYPO3.settings.ajaxUrls['PermissionAjaxController::dispatch'],

		// set the permission bits through an ajax call
	setPermissions: function(page, bits, mode, who, permissions) {
		new Ajax.Updater($(page + '_' + who), this.thisScript, {
			parameters: { page: page, permissions: permissions, mode: mode, who: who, bits: bits }
		});
	},

		// load the selector for selecting the owner of a page by executing an ajax call
	showChangeOwnerSelector: function(page, ownerUid, elementID, username) {
		new Ajax.Updater($(elementID), this.thisScript, {
			parameters: { action: 'show_change_owner_selector', page: page, ownerUid: ownerUid, username: username }
		});
	},

		// Set the new owner of a page by executing an ajax call
	changeOwner: function(page, ownerUid, elementID) {
		new Ajax.Updater($(elementID), this.thisScript, {
			parameters: { action: 'change_owner', page: page, ownerUid: ownerUid, newOwnerUid: $('new_page_owner').value }
		});
	},

		// Update the HTML view and show the original owner
	restoreOwner: function(page, ownerUid, username, elementID) {
		var idName = 'o_' + page;
		$(elementID).innerHTML = '<a class="ug_selector" onclick="WebPermissions.showChangeOwnerSelector(' + page + ', ' + ownerUid + ', \'' + idName + '\', \'' + username + '\');">' + username + '</a>';
	},

		// Load the selector by executing an ajax call
	showChangeGroupSelector: function(page, groupUid, elementID, groupname) {
		new Ajax.Updater($(elementID), this.thisScript, {
			parameters: { action: 'show_change_group_selector', page: page, groupUid: groupUid, groupname: groupname }
		});
	},

		// Set the new group by executing an ajax call
	changeGroup: function(page, groupUid, elementID) {
		new Ajax.Updater($(elementID), this.thisScript, {
			parameters: { action: 'change_group', page: page, groupUid: groupUid, newGroupUid: $('new_page_group').value }
		});
	},

		// Update the HTML view and show the original group
	restoreGroup: function(page, groupUid, groupname, elementID) {
		var idName = 'g_' + page;
		$(elementID).innerHTML = '<a class="ug_selector" onclick="WebPermissions.showChangeGroupSelector(' + page + ', ' + groupUid + ', \'' + idName + '\', \'' + groupname + '\');">' + groupname + '</a>';
	},

		// set or remove the edit lock by executing an ajax call
	toggleEditLock: function(page, editLockState) {
		new Ajax.Updater($('el_' + page), this.thisScript, {
			parameters: { action: 'toggle_edit_lock', page: page, editLockState: editLockState }
		});
	}
};
