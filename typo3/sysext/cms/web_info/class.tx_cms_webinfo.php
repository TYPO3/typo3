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
 * Contains a class with functions for page related statistics added to the backend Info module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cms') . 'layout/class.tx_cms_layout.php';
/*
 * @deprecated since 6.0, the classname tx_cms_webinfo_page and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/frontend/Classes/Controller/PageInformationController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('frontend') . 'Classes/Controller/PageInformationController.php';
