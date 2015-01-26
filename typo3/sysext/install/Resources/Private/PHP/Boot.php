<?php
namespace TYPO3\CMS\Install;

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

defined('TYPO3_MODE') or die();

// Bootstrap bare minimum: class loader, LocalConfiguration, but no extensions and such
require __DIR__ . '/../../../../core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->baseSetup('typo3/sysext/install/Start/')
	->startOutputBuffering()
	->loadConfigurationAndInitialize(FALSE, \TYPO3\CMS\Core\Package\FailsafePackageManager::class);

// Execute 'tool' or 'step' controller depending on install[controller] GET/POST parameter
$getPost = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('install');
$controllerClassName = \TYPO3\CMS\Install\Controller\StepController::class;
if (isset($getPost['controller'])) {
	switch ($getPost['controller']) {
		case 'tool':
			$controllerClassName = \TYPO3\CMS\Install\Controller\ToolController::class;
			break;
		case 'ajax':
			$controllerClassName = \TYPO3\CMS\Install\Controller\AjaxController::class;
			break;
	}
}
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($controllerClassName)->execute();