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
 * Service tag to autoconfigure text extractors for the TextExtractorRegistry.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTextExtractor
{
    public const TAG_NAME = 'fal.text_extractor';

    public function __construct(
        /**
         * The priority of the text extractor. This way it is possible to
         * define/overrule an extractor for a specific file type/context,
         * for example a text extractor for a certain mime type.
         *
         * Extractors with a higher priority are asked first whether they
         * can extract text from a given file. The text extractors shipped
         * with TYPO3 Core use the default priority of 0.
         */
        public int $priority = 0,
    ) {}
}
