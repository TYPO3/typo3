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

namespace TYPO3\CMS\Backend\Dto\Tree;

/**
 * @internal
 */
final readonly class PageTreeItem implements \JsonSerializable
{
    public function __construct(
        public TreeItem $item,
        public int $doktype,
        public string $nameSourceField,
        public int $workspaceId,
        public bool $locked,
        public bool $stopPageTree,
        public int $mountPoint,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'type' => 'PageTreeItem',
            ...$this->item->jsonSerialize(),
            'doktype' => $this->doktype,
            'nameSourceField' => $this->nameSourceField,
            'workspaceId' => $this->workspaceId,
            'locked' => $this->locked,
            'stopPageTree' => $this->stopPageTree,
            'mountPoint' => $this->mountPoint,
        ];
    }
}
