<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
