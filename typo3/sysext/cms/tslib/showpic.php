<?php
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
 * eID-Script for tx_cms_showpic
 *
 * Shows a picture from FAL in enlarged format in a separate window.
 * Picture file and settings is supplied by GET-parameters:
 *  - file = fileUid or Combined Identifier
 *  - encoded in an parameter Array (with weird format - see ContentObjectRenderer about ll. 1500)
 *  	- width, height = usual width an height, m/c supported
 *  	- sample = 0/1
 *  	- effects
 *  	- frame
 *  	- bodyTag
 *  	- title
 *  	- wrap
 *  - md5 = actually contains an hmac
 */

if (!defined('PATH_typo3conf')) {
	die('The configuration path was not properly defined!');
}

// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\ShowImageController');
$SOBE->execute();
