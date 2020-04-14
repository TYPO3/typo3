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

namespace TYPO3\CMS\Core\DataHandling\Model;

/**
 * Represents the context of an entity
 *
 * A context defines a "variant" of an entity, currently by its language and workspace assignment. The EntityContext
 * is bound to a RecordState.
 */
class EntityContext
{
    /**
     * @var int
     */
    protected $workspaceId = 0;

    /**
     * @var int
     */
    protected $languageId = 0;

    /**
     * @return int
     */
    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    /**
     * @param int $workspaceId
     * @return static
     */
    public function withWorkspaceId(int $workspaceId): self
    {
        if ($this->workspaceId === $workspaceId) {
            return $this;
        }
        $target = clone $this;
        $target->workspaceId = $workspaceId;
        return $target;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    /**
     * @param int $languageId
     * @return static
     */
    public function withLanguageId(int $languageId): self
    {
        if ($this->languageId === $languageId) {
            return $this;
        }
        $target = clone $this;
        $target->languageId = $languageId;
        return $target;
    }
}
