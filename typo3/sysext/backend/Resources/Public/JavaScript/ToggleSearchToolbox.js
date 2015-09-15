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
 * Toggle the search toolbox
 */
define('TYPO3/CMS/Backend/ToggleSearchToolbox', ['jquery', 'TYPO3/CMS/Backend/DocumentHeader', 'TYPO3/CMS/Backend/jquery.clearable'], function($, DocumentHeader) {
	"use strict";

	$(function() {
		var $toolbar = $('#db_list-searchbox-toolbar');
		$('.t3js-toggle-search-toolbox').on('click', function() {
			$toolbar.toggle();
			DocumentHeader.reposition();
			if ($toolbar.is(':visible')) {
				$('#search_field').focus();
			}
		});

		var $searchFields = $('#search_field');
		var searchResultShown = ('' !== $searchFields.val());

		// make search field clearable
		$searchFields.clearable({
			onClear: function() {
				if (searchResultShown) {
					$(this).closest('form').submit();
				}
			}
		});
	});
});
