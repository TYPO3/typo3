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

namespace TYPO3\CMS\Backend\Dto\Breadcrumb;

/**
 * Represents a single breadcrumb node in the backend document header.
 *
 * Breadcrumb nodes are used to build hierarchical navigation trails that help users
 * understand their current location within the TYPO3 backend and navigate back through
 * parent items.
 *
 * @internal Subject to change until v15 LTS
 */
final readonly class BreadcrumbNode implements \JsonSerializable
{
    /**
     * @param string $identifier Unique identifier for this node (e.g., record UID, page ID, storage identifier)
     * @param string $label Display text shown to the user
     * @param string|null $icon Icon identifier from the icon registry (e.g., 'actions-page-open', 'apps-pagetree-root')
     * @param string|null $iconOverlay Overlay icon identifier for additional visual context (e.g., 'overlay-new', 'overlay-hidden')
     * @param BreadcrumbNodeRoute|null $route Navigation target when clicking the node. Null means node is not clickable (typically the current item)
     * @param bool|null $forceShowIcon Forces icon display even in contexts where icons are normally hidden (e.g., module nodes)
     */
    public function __construct(
        public string $identifier,
        public string $label,
        public ?string $icon = null,
        public ?string $iconOverlay = null,
        public ?BreadcrumbNodeRoute $route = null,
        public ?bool $forceShowIcon = false,
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
