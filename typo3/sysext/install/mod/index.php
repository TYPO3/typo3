<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Steffen Gebert <steffen.gebert@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF, TRUE);
/*
 * @deprecated since 6.0, the classname tx_install_mod1 and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/install/Classes/Controller/InstallToolModuleController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Classes/Controller/InstallToolModuleController.php';
/**
 * @var $SOBE \TYPO3\CMS\Install\Controller\InstallToolModuleController
 */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Controller\\InstallToolModuleController');
$SOBE->init();
$SOBE->main();
?>