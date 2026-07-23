<?php

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

namespace TYPO3\CMS\Core\Resource\Rendering;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Interface for file renderers, which are registered as tagged services
 * via the #[AsFileRenderer] attribute or the 'fal.file_renderer' service tag.
 */
interface FileRendererInterface
{
    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File or FileReference to render
     */
    public function canRender(FileInterface $file): bool;

    /**
     * Render for given File(Reference) HTML output
     *
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     */
    public function render(FileInterface $file, int|string $width, int|string $height, array $options = []): string;
}
