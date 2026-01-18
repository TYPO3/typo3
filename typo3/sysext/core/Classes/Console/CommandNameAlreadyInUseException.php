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

namespace TYPO3\CMS\Core\Console;

use TYPO3\CMS\Core\Exception;

/**
 * Exception that was thrown when a command was registered with a name
 * that is already taken. This exception is currently unused.
 *
 * @deprecated since TYPO3 v14.1, will be removed in TYPO3 v15.0.
 */
class CommandNameAlreadyInUseException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        trigger_error('The class ' . __CLASS__ . ' is unused and deprecated, and will be removed in TYPO3 v15.0.', E_USER_DEPRECATED);
        parent::__construct($message, $code, $previous);
    }
}
