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

namespace TYPO3\CMS\Core\Service;

use TYPO3\CMS\Core\Attribute\AsRowUpdater;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterInterface;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterRegistry;

/**
 * Service providing TYPO3 internal row updater based on {@see RowUpdaterInterface}
 * registered with internal {@see AsRowUpdater} php attribute.
 *
 * @internal to be used in `EXT:core` only and not part of public API.
 */
final readonly class RowUpdaterService
{
    public function __construct(
        private RowUpdaterRegistry $rowUpdaterRegistry,
        private Registry $registry,
    ) {}

    /**
     * Get registered row updater by identifier.
     */
    public function getRowUpdater(string $identifier): RowUpdaterInterface
    {
        return $this->rowUpdaterRegistry->getRowUpdater($identifier);
    }

    /**
     * Return an array of class names that are not yet marked as done.
     *
     * @return string[] Row updater identifiers
     */
    public function getRowUpdatersToExecute(): array
    {
        $doneRowUpdater = $this->registry->get('installUpdateRows', 'rowUpdatersDone', []);
        return array_diff($this->rowUpdaterRegistry->getRowUpdaterIdentifiers(), $doneRowUpdater);
    }

    public function setRowUpdaterExecuted(string $identifier): void
    {
        $doneRowUpdater = $this->registry->get('installUpdateRows', 'rowUpdatersDone', []);
        $doneRowUpdater[] = $identifier;
        $this->registry->set('installUpdateRows', 'rowUpdatersDone', $doneRowUpdater);
    }
}
