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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains information about the currently accessed workspace.
 *
 * Allowed properties:
 * - id
 * - isLive
 * - isOffline
 */
final readonly class WorkspaceAspect implements AspectInterface
{
    public function __construct(
        private int $workspaceId = 0,
    ) {}

    /**
     * Fetch the workspace ID, or evaluated the state if it's 'online' or 'offline'
     *
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name): int|bool
    {
        switch ($name) {
            case 'id':
                return $this->workspaceId;
            case 'isLive':
                return $this->isLive();
            case 'isOffline':
                return !$this->isLive();
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1527779447);
    }

    /**
     * Return the workspace ID
     */
    public function getId(): int
    {
        return $this->workspaceId;
    }

    /**
     * Return whether this is live workspace or in a custom offline workspace
     */
    public function isLive(): bool
    {
        return $this->workspaceId === 0;
    }
}
