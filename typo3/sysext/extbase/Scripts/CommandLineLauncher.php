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
 * Launcher for the bootstrap when executing via cli.
 *
 * ONLY USED INTERNALLY, MIGHT CHANGE WITHOUT NOTICE!
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase', 'Classes/Core/Bootstrap.php');
$bootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
echo $bootstrap->run('', array());
