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

use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Backend\Dto\Tree\Status\StatusInformation;

/**
 * @internal
 */
final readonly class TreeItem implements \JsonSerializable
{
    /**
     * @param StatusInformation[] $statusInformation
     * @param Label[] $labels
     **/
    public function __construct(
        public string $identifier,
        public string $parentIdentifier,
        public string $recordType,
        public string $name,
        public string $prefix,
        public string $suffix,
        public string $tooltip,
        public int $depth,
        public bool $hasChildren,
        public bool $loaded,
        public string $icon,
        public string $overlayIcon,
        public string $note = '',
        public array $statusInformation = [],
        public array $labels = [],
        public bool $editable = false,
        public bool $deletable = false,
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
