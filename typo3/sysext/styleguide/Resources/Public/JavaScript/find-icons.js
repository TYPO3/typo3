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
 * Javascript functions regarding the FindIcons module
 */
import $ from 'jquery';

$('.t3js-filter-buttons button').click(function(e) {
	e.preventDefault();
	$('#search-field').val($(this).data('filter')).trigger('keyup');
});
$('#search-field').keyup(function() {
	var typedQuery = $(this).val();
	if (typedQuery === '') {
		$('#t3js-filter-container [data-icon-identifier]').show();
	} else {
		if (typedQuery.indexOf('type:') !== -1) {
			var parts = typedQuery.split(':');
			var type = parts[1];
			switch (type.toLowerCase()) {
				case 'bitmap':
					$('#t3js-filter-container [data-icon-identifier]').hide();
					$('#t3js-filter-container img:not([src$=".svg"])').closest('[data-icon-identifier]').show();
					break;
				case 'font':
					$('#t3js-filter-container [data-icon-identifier]').hide();
					$('#t3js-filter-container i.fa').closest('[data-icon-identifier]').show();
					break;
				case 'vector':
					$('#t3js-filter-container [data-icon-identifier]').hide();
					$('#t3js-filter-container img[src$=".svg"]').closest('[data-icon-identifier]').show();
					break;
			}
		} else {
			$('#t3js-filter-container [data-icon-identifier]').hide();
			$('#t3js-filter-container [data-icon-identifier*="' + typedQuery + '"]').show();
		}
	}
});
