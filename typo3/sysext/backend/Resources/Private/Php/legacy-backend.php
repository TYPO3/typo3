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

// Legacy wrapper for typo3/index.php
// @deprecated will be removed in TYPO3 v14, /index.php entrypoint should be used directly

// defer deprecation log message
$_SERVER['TYPO3_DEPRECATED_ENTRYPOINT'] = 1;

array_map(
    static function (string $var): void {
        $setenv = static function (string $name, ?string $value = null): void {
            // If PHP is running as an Apache module and an existing
            // Apache environment variable exists, overwrite it
            if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
                apache_setenv($name, $value);
            }

            if (function_exists('putenv')) {
                putenv("$name=$value");
            }

            $_ENV[$name] = $value;
        };

        if (isset($_SERVER[$var])) {
            $_SERVER[$var] = str_replace('/typo3/index.php', '/index.php', $_SERVER[$var]);
        }
        if (isset($_ENV[$var])) {
            $setenv($var, str_replace('/typo3/index.php', '/index.php', $_ENV[$var]));
        }
    },
    ['SCRIPT_NAME', 'SCRIPT_FILENAME', 'SCRIPT_URI', 'SCRIPT_URL', 'PHP_SELF'],
);

require '../index.php';
