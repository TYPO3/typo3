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

namespace TYPO3\CMS\Core\Page;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;

/**
 * @internal
 */
final class ResolveContentAreasEvent
{
    /** @var ContentAreaClosure[]|ContentArea[] */
    private array $contentAreas = [];

    public function __construct(private readonly BackendLayout $layout) {}

    public function getBackendLayout(): BackendLayout
    {
        return $this->layout;
    }

    public function setContentAreas(array $contentAreas): void
    {
        $this->contentAreas = $contentAreas;
    }

    public function getContentAreas(): array
    {
        return $this->contentAreas;
    }
}
