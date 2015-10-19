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
 * Module: TYPO3/CMS/Documentation/Main
 * JavaScript module for ext:documentation
 */
define(['jquery', 'datatables', 'TYPO3/CMS/Backend/jquery.clearable'], function($) {
	'use strict';

	/**
	 *
	 * @type {{dataTable: null, searchField: null, identifier: {documentationList: string, searchField: string}}}
	 * @exports TYPO3/CMS/Documentation/Main
	 */
	var Documentation = {
		dataTable: null,
		searchField: null,
		identifier: {
			documentationList: '.t3js-documentation-list',
			searchField: '.t3js-documentation-searchfield'
		}
	};

	/**
	 *  Initializes the data table, depending on the current view
	 */
	Documentation.initializeView = function() {
		var getVars = Documentation.getUrlVars();
		// init datatable
		this.dataTable = $(this.identifier.documentationList).DataTable({
			paging: false,
			dom: 'lrtip',
			lengthChange: false,
			pageLength: 15,
			stateSave: true,
			order: [[ 1, 'asc' ]]
		});
		// search field
		this.searchField = $(this.identifier.searchField);
		if (this.dataTable && this.searchField.length) {
			this.searchField.parents('form').on('submit', function() {
				return false;
			});
			var currentSearch = (getVars['search'] ? getVars['search'] : this.dataTable.search());
			this.searchField.val(currentSearch);
			this.searchField.on('input', function(e) {
				Documentation.dataTable.search($(this).val()).draw();
			});
		}
	};

	/**
	 * Utility method to retrieve query parameters
	 *
	 * @returns {Array}
	 */
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

	$(function() {
		// Initialize the view
		Documentation.initializeView();

		// Make the data table filter react to the clearing of the filter field
		$(Documentation.identifier.searchField).clearable({
			onClear: function() {
				Documentation.dataTable.search('').draw();
			}
		});
	});

	return Documentation;
});
