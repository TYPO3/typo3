<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains the class for the Install Tool
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Ingmar Schlecht <ingmar@typo3.org>
 */
// include requirements definition:
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'requirements.php';
// include session handling
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'mod/class.tx_install_session.php';
// include update classes
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_charsetdefaults.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_compatversion.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_cscsplit.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_notinmenu.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_mergeadvanced.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_installsysexts.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_imagescols.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_installnewsysexts.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_statictemplates.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_t3skin.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_compressionlevel.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_migrateworkspaces.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_flagsfromsprite.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_addflexformstoacl.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_imagelink.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_mediaflexform.php';
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'updates/class.tx_coreupdates_localconfiguration.php';
/*
 * @deprecated since 6.0, the classname tx_install and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/install/Classes/Installer.php
 */
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('install') . 'Classes/Installer.php';
?>