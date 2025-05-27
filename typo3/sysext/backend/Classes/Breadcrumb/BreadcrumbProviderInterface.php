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

namespace TYPO3\CMS\Backend\Breadcrumb;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;

/**
 * Interface for breadcrumb providers that can generate breadcrumb trails
 * for different types of contexts (records, resources, etc.).
 *
 * Providers are responsible for:
 * - Determining if they can handle a given context
 * - Generating the appropriate breadcrumb node hierarchy
 * - Providing the target module identifier for navigation
 *
 * @internal Subject to change until v15 LTS
 */
interface BreadcrumbProviderInterface
{
    /**
     * Determines whether this provider can handle the given context.
     *
     * @param BreadcrumbContext|null $context The breadcrumb context (can be null for virtual pages)
     */
    public function supports(?BreadcrumbContext $context): bool;

    /**
     * Generates breadcrumb nodes for the given context.
     *
     * @param BreadcrumbContext|null $context The breadcrumb context (can be null for virtual pages)
     * @param ServerRequestInterface|null $request The current request for module detection
     * @return BreadcrumbNode[] Array of breadcrumb nodes ordered from root to current
     */
    public function generate(?BreadcrumbContext $context, ?ServerRequestInterface $request): array;

    /**
     * Returns the priority of this provider.
     *
     * Higher priority providers are checked first. Use this to override
     * default providers or to establish a specific order.
     *
     * @return int Priority (higher = checked first)
     */
    public function getPriority(): int;
}
