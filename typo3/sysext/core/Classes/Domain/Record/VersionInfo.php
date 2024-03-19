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

namespace TYPO3\CMS\Core\Domain\Record;

use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Contains all information about versioning for a workspace-aware record.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class VersionInfo
{
    public function __construct(
        protected int $workspaceId,
        protected int $liveId,
        protected VersionState $state,
        protected int $stage,
    ) {}

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function getLiveId(): int
    {
        return $this->liveId;
    }

    public function getState(): VersionState
    {
        return $this->state;
    }

    public function getStageId(): int
    {
        return $this->stage;
    }
}
