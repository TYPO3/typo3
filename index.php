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
 * This is the MAIN DOCUMENT of the TypoScript driven standard frontend.
 * Basically this is the "index.php" script which all requests for TYPO3
 * delivered pages goes to in the frontend (the website)
 */

/*
 * This check avoids a parse error "unexpected 'class'" for users installing TYPO3 CMS 7
 * on PHP 5.4. Without it the redirect to the install tool does not work.
 * @see https://forge.typo3.org/issues/64504
 */
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    die('TYPO3 CMS requires PHP 5.5 or above');
}

// Set up the application for the Frontend
call_user_func(function () {
    $classLoader = require rtrim(realpath(__DIR__ . '/typo3'), '\\/') . '/../vendor/autoload.php';
    (new \TYPO3\CMS\Frontend\Http\Application($classLoader))->run();
});
