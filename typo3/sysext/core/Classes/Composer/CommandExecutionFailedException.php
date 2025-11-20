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

namespace TYPO3\CMS\Core\Composer;

class CommandExecutionFailedException extends \Exception
{
    public function __construct(
        public array $typo3Command,
        public string $errorOutput = '',
        int $code = 1765277390,
    ) {
        $message = sprintf('Failed to run command %s', implode(' ', $this->typo3Command));
        parent::__construct($message, $code);
    }
}
