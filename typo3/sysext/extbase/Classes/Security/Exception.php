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

namespace TYPO3\CMS\Extbase\Security;

use TYPO3\CMS\Extbase\Exception as ExtbaseException;

/**
 * A hash service which should be used to generate and validate hashes.
 *
 * It will use some salt / encryption key in the future.
 */
class Exception extends ExtbaseException
{
}
