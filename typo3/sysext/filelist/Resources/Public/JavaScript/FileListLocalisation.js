/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
 *  All rights reserved
 *
 *  Released under GNU/GPL2+ (see license file in the main directory)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 ***************************************************************/
/**
 * JavaScript RequireJS module called "TYPO3/CMS/Backend/FileListLocalisation"
 *
 */
define('TYPO3/CMS/Filelist/FileListLocalisation', ['jquery'], function($) {

	$('a.filelist-translationToggler').click(function(event) {
		var id = $(this).attr('data-fileid');
		$('div[data-fileid="' + id + '"]').toggle();
	});

	return null;
});