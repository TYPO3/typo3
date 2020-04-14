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

use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;

/**
 * Interface PreviewRendererInterface
 *
 * Contract for classes capable of rendering previews of a given record
 * from a table. Responsible for rendering preview header, preview content
 * and wrapping of those two values.
 *
 * Responsibilities are segmented into three methods, one for each responsibility,
 * which is done in order to allow overriding classes to change those parts
 * individually without having to replace other parts. Rather than relying on
 * implementations to be friendly and divide code into smaller pieces and
 * give them (at least) protected visibility, the key methods are instead required
 * on the interface directly.
 *
 * Callers are then responsible for calling each method and combining/wrapping
 * the output appropriately.
 */
interface PreviewRendererInterface
{
    /**
     * Dedicated method for rendering preview header HTML for
     * the page module only. Receives the the GridColumnItem
     * that contains the record for which a preview header
     * should be rendered and returned.
     *
     * @param GridColumnItem $item
     * @return string
     */
    public function renderPageModulePreviewHeader(GridColumnItem $item): string;

    /**
     * Dedicated method for rendering preview body HTML for
     * the page module only. Receives the the GridColumnItem
     * that contains the record for which a preview should be
     * rendered and returned.
     *
     * @param GridColumnItem $item
     * @return string
     */
    public function renderPageModulePreviewContent(GridColumnItem $item): string;

    /**
     * Render a footer for the record to display in page module below
     * the body of the item's preview.
     *
     * @param GridColumnItem $item
     * @return string
     */
    public function renderPageModulePreviewFooter(GridColumnItem $item): string;

    /**
     * Dedicated method for wrapping a preview header and body
     * HTML. Receives $item, an instance of GridColumnItem holding
     * among other things the record, which can be used to determine
     * appropriate wrapping.
     *
     * @param string $previewHeader
     * @param string $previewContent
     * @param GridColumnItem $item
     * @return string
     */
    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string;
}
