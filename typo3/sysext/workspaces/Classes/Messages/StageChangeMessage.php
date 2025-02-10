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

namespace TYPO3\CMS\Workspaces\Messages;

/**
 * Simple object with all relevant information when a stage within a workspace was changed.
 *
 * @internal
 */
final readonly class StageChangeMessage
{
    public function __construct(
        public array $workspaceRecord,
        public int $stageId,
        public array $affectedElements,
        public string $comment,
        public array $recipients,
        public array $currentUserRecord
    ) {}
}
