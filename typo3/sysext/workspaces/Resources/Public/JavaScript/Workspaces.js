/*
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
 * RequireJS module for Workspaces
 */
define([
	'jquery',
	'TYPO3/CMS/Backend/Severity',
	'TYPO3/CMS/Backend/Modal'
], function($, Severity, Modal) {
	'use strict';

	var Workspaces = {
		tid: 0
	};

	/**
	 * Renders the send to stage window
	 * @param {Object} response
	 * @return {$}
	 */
	Workspaces.renderSendToStageWindow = function(response) {
		var result = response[0].result,
			$form = $('<form />');

		if (typeof result.sendMailTo !== 'undefined' && result.sendMailTo.length > 0) {
			$form.append(
				$('<label />', {class: 'control-label'}).text(TYPO3.lang['window.sendToNextStageWindow.itemsWillBeSentTo'])
			);

			for (var i = 0; i < result.sendMailTo.length; ++i) {
				var recipient = result.sendMailTo[i];

				$form.append(
					$('<div />', {class: 'checkbox'}).append(
						$('<label />').text(recipient.label).prepend(
							$('<input />', {type: 'checkbox', name: 'recipients', id: recipient.name, value: recipient.value}).prop('checked', recipient.checked).prop('disabled', recipient.disabled)
						)
					)
				);
			}
		}

		if (typeof result.additional !== 'undefined') {
			$form.append(
				$('<div />', {class: 'form-group'}).append(
					$('<label />', {class: 'control-label', 'for': 'additional'}).text(TYPO3.lang['window.sendToNextStageWindow.additionalRecipients']),
					$('<textarea />', {class: 'form-control', name: 'additional', id: 'additional'}).text(result.additional.value),
					$('<span />', {class: 'help-block'}).text(TYPO3.lang['window.sendToNextStageWindow.additionalRecipients.hint'])
				)
			);
		}

		$form.append(
			$('<div />', {class: 'form-group'}).append(
				$('<label />', {class: 'control-label', 'for': 'comments'}).text(TYPO3.lang['window.sendToNextStageWindow.comments']),
				$('<textarea />', {class: 'form-control', name: 'comments', id: 'comments'}).text(result.comments.value)
			)
		);

		var $modal = Modal.show(
			TYPO3.lang['actionSendToStage'],
			$form,
			Severity.info,
			[
				{
					text: TYPO3.lang['cancel'],
					active: true,
					btnClass: 'btn-default',
					name: 'cancel',
					trigger: function() {
						$modal.modal('hide');
					}
				}, {
				text: TYPO3.lang['ok'],
				btnClass: 'btn-info',
				name: 'ok'
			}
			]
		);

		return $modal;
	};

	/**
	 * Checks the integrity of a record
	 *
	 * @param {Array} payload
	 * @return {$}
	 */
	Workspaces.checkIntegrity = function(payload) {
		return Workspaces.sendRemoteRequest(
			Workspaces.generateRemotePayload('checkIntegrity', payload)
		);
	};

	/**
	 * Sends an AJAX request
	 *
	 * @param {Object} payload
	 * @return {$}
	 */
	Workspaces.sendRemoteRequest = function(payload) {
		return $.ajax({
			url: TYPO3.settings.ajaxUrls['workspace_dispatch'],
			method: 'POST',
			contentType: 'application/json; charset=utf-8',
			dataType: 'json',
			data: JSON.stringify(payload)
		});
	};

	/**
	 * Generates the payload for a remote call
	 *
	 * @param {String} method
	 * @param {Object} data
	 * @return {{action, data, method, type}}
	 */
	Workspaces.generateRemotePayload = function(method, data) {
		if (typeof data === 'undefined') {
			data = {};
		}
		return Workspaces.generateRemotePayloadBody('RemoteServer', method, data);
	};

	/**
	 * Generates the payload for MassActions
	 *
	 * @param {String} method
	 * @param {Object} data
	 * @return {{action, data, method, type}}
	 */
	Workspaces.generateRemoteMassActionsPayload = function(method, data) {
		if (typeof data === 'undefined') {
			data = {};
		}
		return Workspaces.generateRemotePayloadBody('MassActions', method, data);
	};

	/**
	 * Generates the payload for Actions
	 *
	 * @param {String} method
	 * @param {Object} data
	 * @return {{action, data, method, type}}
	 */
	Workspaces.generateRemoteActionsPayload = function(method, data) {
		if (typeof data === 'undefined') {
			data = [];
		}
		return Workspaces.generateRemotePayloadBody('Actions', method, data);
	};

	/**
	 * Generates the payload body
	 *
	 * @param {String} action
	 * @param {String} method
	 * @param {Object} data
	 * @return {{action: String, data: Object, method: String, type: string}}
	 */
	Workspaces.generateRemotePayloadBody = function(action, method, data) {
		if (data instanceof Array) {
			data.push(TYPO3.settings.Workspaces.token);
		} else {
			data = [
				data,
				TYPO3.settings.Workspaces.token
			];
		}
		return {
			action: action,
			data: data,
			method: method,
			type: 'rpc',
			tid: Workspaces.tid++
		};
	};

	/**
	 * Serialize a form to a JavaScript object
	 *
	 * @see http://stackoverflow.com/a/1186309/4828813
	 * @return {Object}
	 */
	$.fn.serializeObject = function() {
		var o = {};
		var a = this.serializeArray();
		$.each(a, function() {
			if (typeof o[this.name] !== 'undefined') {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	return Workspaces;
});
