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

namespace TYPO3\CMS\Scheduler\Exception;

use TYPO3\CMS\Scheduler\Exception;

/**
 * Thrown if a Task could not be successfully unserialized, the unserialized
 * Task is not an instance of AbstractTask or is not registered at all.
 */
class InvalidTaskException extends Exception {}
