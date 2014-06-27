/**
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