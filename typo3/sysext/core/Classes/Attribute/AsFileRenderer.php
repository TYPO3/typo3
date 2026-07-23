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

namespace TYPO3\CMS\Core\Attribute;

/**
 * Service tag to autoconfigure file renderers for the RendererRegistry.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsFileRenderer
{
    public const TAG_NAME = 'fal.file_renderer';

    public function __construct(
        /**
         * The priority of the renderer. This way it is possible to
         * define/overrule a renderer for a specific file type/context,
         * for example a video renderer for a certain storage/driver type.
         *
         * Renderers with a higher priority are asked first whether they
         * can render a given file. All file renderers shipped with
         * TYPO3 Core use the default priority of 0.
         */
        public int $priority = 0,
    ) {}
}
