<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Context;

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

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains information about the currently accessed workspace.
 *
 * Allowed properties:
 * - id
 * - isLive
 * - isOffline
 */
class WorkspaceAspect implements AspectInterface
{
    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @param int $workspaceId
     */
    public function __construct(int $workspaceId = 0)
    {
        $this->workspaceId = $workspaceId;
    }

    /**
     * Fetch the workspace ID, or evaluated the state if it's 'online' or 'offline'
     *
     * @param string $name
     * @return int|bool
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
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
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->workspaceId;
    }

    /**
     * Return whether this is live workspace or in a custom offline workspace
     *
     * @return bool
     */
    public function isLive(): bool
    {
        return $this->workspaceId === 0;
    }
}
