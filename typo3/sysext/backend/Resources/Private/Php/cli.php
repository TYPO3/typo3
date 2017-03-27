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

// Exit early if php requirement is not satisfied.
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('This version of TYPO3 CMS requires PHP 7.0 or above');
}

/**
 * --------------------------------------------------------------------------------
 * NOTE: This entry-point is deprecated since TYPO3 v8 and will be removed in
 * TYPO3 v9. Use the binary located typo3/sysext/core/bin/typo3 instead.
 * --------------------------------------------------------------------------------
 * Command Line Interface module dispatcher
 *
 * This script takes a "cliKey" as first argument and uses that to dispatch
 * the call to a registered script with that key.
 * Valid cliKeys must be registered in
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'].
 */
call_user_func(function () {
    $classLoader = require __DIR__ . '/../../../../../../vendor/autoload.php';

    (new \TYPO3\CMS\Backend\Console\Application($classLoader))->run();
});
