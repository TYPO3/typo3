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

/**
 * Module: TYPO3/CMS/Beuser/Permissons
 * Javascript functions regarding the permissions module
 */
define(['jquery'], function($) {

	/**
	 *
	 * @type {{options: {containerSelector: string}}}
	 * @exports TYPO3/CMS/Beuser/Permissons
	 */
	var Permissions = {
		options: {
			containerSelector: '#typo3-permissionList'
		}
	};
	var ajaxUrl = TYPO3.settings.ajaxUrls['user_access_permissions'];

	/**
	 * Changes the value of the permissions in the form
	 *
	 * @param {String} checknames
	 * @param {String} varname
	 */
	Permissions.setCheck = function(checknames, varname) {
		if (document.editform[varname]) {
			var res = document.editform[varname].value;
			for (var a = 1; a <= 5; a++) {
				document.editform[checknames + '[' + a + ']'].checked = (res & Math.pow(2, a-1));
			}
		}
	};

	/**
	 * checks for a change of the permissions in the form
	 *
	 * @param {String} checknames
	 * @param {String} varname
	 */
	Permissions.checkChange = function(checknames, varname) {
		var res = 0;
		for (var a = 1; a <= 5; a++) {
			if (document.editform[checknames + '[' + a + ']'].checked) {
				res |= Math.pow(2,a-1);
			}
		}
		document.editform[varname].value = res | (checknames === 'tx_beuser_system_beusertxpermission[check][perms_user]' ? 1 : 0);
		Permissions.setCheck(checknames, varname);
	};

	/**
	 * wrapper function to call a URL in the current frame
	 */
	Permissions.jumpToUrl = function(url) {
		window.location.href = url;
	};

	/**
	 * changes permissions by sending an AJAX request to the server
	 *
	 * @param {Object} $element
	 */
	Permissions.setPermissions = function($element) {
		var page = $element.data('page');
		var who = $element.data('who');

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'page': page,
				'who': who,
				'permissions': $element.data('permissions'),
				'mode': $element.data('mode'),
				'bits': $element.data('bits')
			}
		}).done(function(data) {
			// Replace content
			$('#' + page + '_' + who).replaceWith(data);
		});
	};

	/**
	 * changes the flag to lock the editing on a page by sending an AJAX request
	 *
	 * @param {Object} $element
	 */
	Permissions.toggleEditLock = function($element) {
		var page = $element.data('page');

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'toggle_edit_lock',
				'page': page,
				'editLockState': $element.data('lockstate')
			}
		}).done(function(data) {
			// Replace content
			$('#el_' + page).replaceWith(data);
		});
	};

	/**
	 * Owner-related: Set the new owner of a page by executing an ajax call
	 *
	 * @param {Object} $element
	 */
	Permissions.changeOwner = function($element) {
		var page = $element.data('page');

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'change_owner',
				'page': page,
				'ownerUid': $element.data('owner'),
				'newOwnerUid': $('#new_page_owner').val()
			}
		}).done(function(data) {
			// Replace content
			$('#o_' + page).replaceWith(data);
		});
	};

	/**
	 * Owner-related: load the selector for selecting
	 * the owner of a page by executing an ajax call
	 *
	 * @param {Object} $element
	 */
	Permissions.showChangeOwnerSelector = function($element) {
		var page = $element.data('page');

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'show_change_owner_selector',
				'page': page,
				'ownerUid': $element.data('ownerUid'),
				'username': $element.data('username')
			}
		}).done(function(data) {
			// Replace content
			$('#o_' + page).replaceWith(data);
		});
	};

	/**
	 * Owner-related: Update the HTML view and show the original owner
	 *
	 * @param {Object} $element
	 */
	Permissions.restoreOwner = function($element) {
		var page = $element.data('page');
		var username = $element.data('username');
		var usernameHtml = username;
		if (typeof username === 'undefined') {
			username = $('<span>', {
				'class': 'not_set',
				'text': '[not set]'
			});
			usernameHtml = username.html();
			username = username.text();
		}

		var html = $('<span/>', {
			'id': 'o_' + page
		});
		var aSelector = $('<a/>', {
			'class': 'ug_selector changeowner',
			'data-page': page,
			'data-owner': $element.data('owner'),
			'data-username': usernameHtml,
			'text': username
		});
		html.append(aSelector);

		// Replace content
		$('#o_' + page).replaceWith(html);
	};

	/**
	 * Group-related: Set the new group by executing an ajax call
	 *
	 * @param {Object} $element
	 */
	Permissions.changeGroup = function($element) {
		var page = $element.data('page');

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'change_group',
				'page': page,
				'groupUid': $element.data('group'),
				'newGroupUid': $('#new_page_group').val()
			}
		}).done(function(data) {
			// Replace content
			$('#g_' + page).replaceWith(data);
		});
	};

	/**
	 * Group-related: Load the selector by executing an ajax call
	 *
	 * @param {Object} $element
	 */
	Permissions.showChangeGroupSelector = function($element) {
		var page = $element.data('page');

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			dataType: 'html',
			cache: false,
			data: {
				'action': 'show_change_group_selector',
				'page': page,
				'groupUid': $element.data('lockstate'),
				'groupname': $element.data('groupname')
			}
		}).done(function(data) {
			// Replace content
			$('#g_' + page).replaceWith(data);
		});
	};

	/**
	 * Group-related: Update the HTML view and show the original group
	 *
	 * @param {Object} $element
	 */
	Permissions.restoreGroup = function($element) {
		var page = $element.data('page');
		var groupname = $element.data('groupname');
		var groupnameHtml = groupname;
		if (typeof groupname === 'undefined') {
			groupname = $('<span>', {
				'class': 'not_set',
				'text': '[not set]'
			});
			groupnameHtml = groupname.html();
			groupname = groupname.text();
		}
		var html = $('<span/>', {
			'id': 'g_' + page
		});
		var aSelector = $('<a/>', {
			'class': 'ug_selector changegroup',
			'data-page': page,
			'data-group': $element.data('group'),
			'data-groupname': groupnameHtml,
			'text': groupname
		});
		html.append(aSelector);

		// Replace content
		$('#g_' + page).replaceWith(html);
	};

	/**
	 * initializes events using deferred bound to document
	 * so AJAX reloads are no problem
	 */
	Permissions.initializeEvents = function() {

		// Click event to change permissions
		$(Permissions.options.containerSelector).on('click', '.change-permission', function(evt) {
			evt.preventDefault();
			Permissions.setPermissions($(this));
		}).on('click', '.editlock', function(evt) {
			// Click event for lock state
			evt.preventDefault();
			Permissions.toggleEditLock($(this));
		}).on('click', '.changeowner', function(evt) {
			// Click event to change owner
			evt.preventDefault();
			Permissions.showChangeOwnerSelector($(this));
		}).on('click', '.changegroup', function(evt) {
			// click event to change group
			evt.preventDefault();
			Permissions.showChangeGroupSelector($(this));
		}).on('click', '.restoreowner', function(evt) {
			// Add click handler for restoring previous owner
			evt.preventDefault();
			Permissions.restoreOwner($(this));
		}).on('click', '.saveowner', function(evt) {
			// Add click handler for saving owner
			evt.preventDefault();
			Permissions.changeOwner($(this));
		}).on('click', '.restoregroup', function(evt) {
			// Add click handler for restoring previous group
			evt.preventDefault();
			Permissions.restoreGroup($(this));
		}).on('click', '.savegroup', function(evt) {
			// Add click handler for saving group
			evt.preventDefault();
			Permissions.changeGroup($(this));
		});
	};

	$(Permissions.initializeEvents);

	// expose to global
	TYPO3.Permissions = Permissions;

	return Permissions;
});
