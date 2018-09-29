<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Updates;

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
 * UpgradeWizard Prerequisites
 */
interface PrerequisiteInterface
{
    /**
     * Get speaking name of this prerequisite
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Ensure this prerequisite is fulfilled
     *
     * Gets called if "isFulfilled" returns false
     * and should ensure the prerequisite
     *
     * Returns true on success, false on error
     *
     * @see isFulfilled
     * @return bool
     */
    public function ensure(): bool;

    /**
     * Is this prerequisite met?
     *
     * Checks whether this prerequisite is fulfilled. If it is not,
     * ensure should be called to fulfill it.
     *
     * @see ensure
     * @return bool
     */
    public function isFulfilled(): bool;
}
