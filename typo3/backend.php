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
define('TYPO3_MODE', 'BE');

require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/');
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');

// Document generation
$TYPO3backend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\BackendController::class);
$TYPO3backend->render();
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->shutdown();
