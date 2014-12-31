<?php
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
 * Generates a thumbnail and returns an image stream, either GIF/PNG or JPG
 *
 * @author René Fritz <r.fritz@colorcube.de>
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8, use the corresponding Resource objects and Processing functionality
 */

require __DIR__ . '/init.php';

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'thumbs.php is no longer in use, please use the corresponding Resource objects to generate a preview functionality for thumbnails.'
);
$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\View\ThumbnailView::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();