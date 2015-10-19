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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/Ajax/Ajax
 * Ajax object
 */
define(['jquery',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function($, Util) {

	/**
	 * Constructor method
	 *
	 * @param config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/Ajax/Ajax
	 */
	var Ajax = function (config) {
		Util.apply(this, config);
	};

	Ajax.prototype = {

		/**
		 * Load a Javascript file asynchronously
		 *
		 * @param string url: url of the file to load
		 * @param function callBack: the callBack function
		 * @param object scope: scope of the callbacks
		 * @param string dataType: expected data type
		 *
		 * @return boolean true on success of the request submission
		 */
		getJavascriptFile: function (url, callback, scope, dataType) {
			if (typeof dataType === 'undefined') {
				var dataType = 'script';
			}
			var success = false,
				self = this,
				options = {
					callback: callback,
					complete: function (response, status) {
						this.callback.call(scope, options, success, response);
					},
					dataType: dataType,
					error: function (response, status, error) {
						self.editor.inhibitKeyboardInput = false;
						self.editor.appendToLog('HTMLArea/Ajax/Ajax', 'getJavascriptFile', 'Unable to get ' + url + ' . Server reported ' + error, 'error');
					},
					success: function (data, status, response) {
						success = true;
					},
					scope: scope,
					type: 'GET',
					url: url
				};
			$.ajax(options);
			return success;
		},

		/**
		 * Post data to the server
		 *
		 * @param	string		url: url to post data to
		 * @param	object		data: data to be posted
		 * @param	function	callback: function that will handle the response returned by the server
		 * @param	object		scope: scope of the callbacks
		 *
		 * @return	boolean		true on success
		 */
		postData: function (url, data, callback, scope) {
			var success = false,
				self = this;
			data.charset = 'utf-8';
			var params = '';
			for (var parameter in data) {
				params += (params.length ? '&' : '') + parameter + '=' + encodeURIComponent(data[parameter]);
			}
			params += this.editor.config.RTEtsConfigParams;
			var options = {
				callback: typeof callback === 'function' ? callback : function (options, success, response) {
					if (!success) {
						self.editor.appendToLog('HTMLArea/Ajax/Ajax', 'postData', 'Post request to ' + url + ' failed. Server reported ' + response.status, 'error');
					}
				},
				complete: function (response, status) {
					this.callback.call(scope, options, success, response);
				},
				contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
				data: params,
				error: function (response) {
					self.editor.appendToLog('HTMLArea/Ajax/Ajax', 'postData', 'Unable to post ' + url + ' . Server reported ' + response.status, 'error');
				},
				success: function (response) {
					success = true;
				},
				scope: scope,
				type: 'POST',
				url: url
			};
			$.ajax(options);
			return success;
		}
	};

	return Ajax;

});
