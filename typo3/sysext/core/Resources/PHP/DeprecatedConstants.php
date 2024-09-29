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
 * @internal only for TYPO3 internal work and not part of public API. Remove usage in custom extension code to mitigate
 *           PHP deprecated constant E_STRICT.
 * @deprecated since v13, will be removed in v14. This constant is only for TYPO3 internal usage to replace the
 *             deprecated `E_STRICT` constant of PHP since PHP 8.4.0 RC1 to avoid E_DEPRECATED messages within
 *             automatic testing.
 */
define('E_STRICT_DEPRECATED', 2048);
