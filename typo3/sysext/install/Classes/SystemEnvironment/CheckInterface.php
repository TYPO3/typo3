<?php

namespace TYPO3\CMS\Install\SystemEnvironment;

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
 * Check system environment status
 *
 * This interface needs to be implemented by hardcoded requirement
 * checks of the underlying server and PHP system.
 *
 * The status messages and title *must not* include HTML, use
 * plain text only. The return values of this class can be used
 * in different scopes (eg. as json array).
 */
interface CheckInterface
{
    /**
     * Get all status information as array with status objects
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     */
    public function getStatus(): array;
}
