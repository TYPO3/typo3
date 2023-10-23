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

namespace TYPO3\CMS\Workspaces\Event;

use TYPO3\CMS\Workspaces\Service\GridDataService;

/**
 * Used in the workspaces module to find all data of versions of a workspace.
 */
final class AfterDataGeneratedForWorkspaceEvent
{
    public function __construct(
        private readonly GridDataService $gridService,
        private array $data,
        private array $versions
    ) {}

    public function getGridService(): GridDataService
    {
        return $this->gridService;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getVersions(): array
    {
        return $this->versions;
    }

    public function setVersions(array $versions): void
    {
        $this->versions = $versions;
    }
}
