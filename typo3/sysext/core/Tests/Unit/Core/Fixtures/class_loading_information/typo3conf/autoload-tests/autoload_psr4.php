<?php

declare(strict_types=1);

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

// Simulates the PSR-4 information TYPO3 dumps for the active extensions of an
// instance. The directories do not have to exist, they are registered as strings.

return [
    'TYPO3\\CMS\\TestExtension\\' => ['/fixture/typo3conf/ext/test_extension/Classes'],
];
