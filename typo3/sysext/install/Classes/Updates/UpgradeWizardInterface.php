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

namespace TYPO3\CMS\Install\Updates;

/**
 * Interface UpgradeWizardInterface
 */
interface UpgradeWizardInterface
{
    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string;

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string;

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     */
    public function executeUpdate(): bool;

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     */
    public function updateNecessary(): bool;

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array;
}
