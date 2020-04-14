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

namespace TYPO3\CMS\Backend\Preview;

/**
 * Interface PreviewRendererResolverInterface
 *
 * Contract for classes capable of resolving PreviewRenderInterface
 * implementations based on table and record.
 */
interface PreviewRendererResolverInterface
{
    /**
     * @param string $table The name of the table the returned PreviewRenderer must work with
     * @param array $row A record from $table which will be previewed - allows returning a different PreviewRenderer based on record attributes
     * @param int $pageUid The UID of the page on which the preview will be rendered - allows returning a different PreviewRenderer based on for example pageTSconfig
     * @return PreviewRendererInterface
     */
    public function resolveRendererFor(string $table, array $row, int $pageUid): PreviewRendererInterface;
}
