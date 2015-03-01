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
 * AJAX dispatcher
 * main entry point for AJAX calls in the TYPO3 Backend. Based on $TYPO3_AJAX the Bootstrap let's the request
 * handled by TYPO3\CMS\Backend\AjaxRequestHandler and AjaxController.
 * See $TYPO3_CONF_VARS['BE']['AJAX'] and the Core APIs on how to register an AJAX call in the TYPO3 Backend.
 */
$TYPO3_AJAX = TRUE;
define('TYPO3_MODE', 'BE');

require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/')->shutdown();
