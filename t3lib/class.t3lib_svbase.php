<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Parent class for "Services" classes
 *
 * TODO: temp files are not removed
 *
 * @author René Fritz <r.fritz@colorcube.de>
 */
// General error - something went wrong
define('T3_ERR_SV_GENERAL', -1);
// During execution it showed that the service is not available and should be ignored. The service itself should call $this->setNonAvailable()
define('T3_ERR_SV_NOT_AVAIL', -2);
// Passed subtype is not possible with this service
define('T3_ERR_SV_WRONG_SUBTYPE', -3);
// Passed subtype is not possible with this service
define('T3_ERR_SV_NO_INPUT', -4);
// File not found which the service should process
define('T3_ERR_SV_FILE_NOT_FOUND', -20);
// File not readable
define('T3_ERR_SV_FILE_READ', -21);
// File not writable
define('T3_ERR_SV_FILE_WRITE', -22);
// Passed subtype is not possible with this service
define('T3_ERR_SV_PROG_NOT_FOUND', -40);
// Passed subtype is not possible with this service
define('T3_ERR_SV_PROG_FAILED', -41);
/*
 * @deprecated since 6.0, the classname t3lib_svbase and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/core/Classes/Service/AbstractService.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('core') . 'Classes/Service/AbstractService.php';
?>