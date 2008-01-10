/***************************************************************
*
*  javascript functions regarding the TYPO3 wrapper
*  for the javascript library "prototype".
*
*  Copyright notice
*
*  (c) 2008 Benjamin Mack <www.xnos.org>
*  All rights reserved
*
*  This script is part of the TYPO3 backend provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in /typo3/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
*
***************************************************************/

// Please make sure that prototype.js is loaded before loading this
// file in your script, the responder is only added if prototype was loaded

if (Prototype) {
	// adding generic a responder to use when a AJAX request is done
	Ajax.Responders.register({
		onCreate: function(request, transport) {

			// if the TYPO3 AJAX backend is used, 
			// the onSuccess & onComplete callbacks are hooked
			if (request.url.indexOf("ajax.php") == -1) {
				return;
			}

			var origSuccess = request.options.onSuccess, origComplete = request.options.onComplete;

			// hooking "onSuccess"
			if (origSuccess) {
				request.options.onSuccess = function(xhr, json) {
					if (!json) {
						T3AJAX.showError(xhr, json);	
					} else {
						origSuccess(xhr, json);
					}
				}
			}

			// hooking "onComplete", using T3Error handler if available
			if (origComplete) {
				request.options.onComplete = function(xhr, json) {
					if (!json && request.options.onT3Error) {
						request.options.onT3Error(xhr, json);
					} else if (!json) {
						T3AJAX.showError(xhr, json);
					} else {
						origComplete(xhr, json);
					}
				}
			}
		}
	});
}

var T3AJAX = new Object();
T3AJAX.showError = function(xhr, json) {
	alert(xhr.responseText);
}
