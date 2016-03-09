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
define('TYPO3/CMS/Styleguide/FindIcons', ['jquery'], function($) {
	$('#search-field').keyup(function() {
		var $typedQuery = TYPO3.jQuery(this).val();
		if ($typedQuery === '') {
			TYPO3.jQuery('#t3js-filter-container [data-icon-identifier]').show();
		} else {
			TYPO3.jQuery('#t3js-filter-container [data-icon-identifier]').hide();
			TYPO3.jQuery('#t3js-filter-container [data-icon-identifier*="' + $typedQuery + '"]').show();
		}
	});
});