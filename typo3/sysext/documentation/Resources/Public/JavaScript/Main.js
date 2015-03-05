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
 * JavaScript module for ext:documentation
 */
define('TYPO3/CMS/Documentation/Main', ['jquery', 'datatables', 'jquery/jquery.clearable'], function($) {

	var Documentation = {
		datatable: null
	};

	// Initializes the data table, depending on the current view
	Documentation.initializeView = function() {
		var getVars = Documentation.getUrlVars();
		// getVars[2] contains the name of the action key
		// List view is the default view
		if (getVars[getVars[2]] === 'download') {
			Documentation.documentationDownloadView(getVars);
		} else {
			Documentation.documentationListView(getVars);
		}
	};

	// Initializes the list view
	Documentation.documentationListView = function(getVars) {
		Documentation.datatable = $('#typo3-documentation-list').DataTable({
			'paging': false,
			'jQueryUI': true,
			'lengthChange': false,
			'pageLength': 15,
			'stateSave': true
		});

		// restore filter
		if (Documentation.datatable.length && getVars['search']) {
			Documentation.datatable.search(getVars['search']);
		}
	};

	// Initializes the download view
	Documentation.documentationDownloadView = function(getVars) {
		Documentation.datatable = $('#typo3-documentation-download').DataTable({
			'paging': false,
			'jQueryUI': true,
			'lengthChange': false,
			'pageLength': 15,
			'stateSave': true,
			'order': [[ 1, 'asc' ]]
		});

		// restore filter
		if (Documentation.datatable.length && getVars['search']) {
			Documentation.datatable.search(getVars['search']);
		}
	};

	// Utility method to retrieve query parameters
	Documentation.getUrlVars = function getUrlVars() {
		var vars = [], hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for (var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		return vars;
	};

	$(document).ready(function() {
		// Initialize the view
		Documentation.initializeView();

		// Make the data table filter react to the clearing of the filter field
		$('.dataTables_wrapper .dataTables_filter input').clearable({
			onClear: function() {
				Documentation.datatable.search('');
			}
		});
	});
});