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

namespace TYPO3\CMS\Seo\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * All runtime information a XmlSitemapDataProvider needs to build a sitemap: The name of the
 * requested sitemap, its TypoScript configuration, the current page number and the request.
 */
final readonly class XmlSitemapRequest
{
    public function __construct(
        public string $name,
        public array $configuration,
        public int $page,
        public ServerRequestInterface $request,
        public ContentObjectRenderer $contentObjectRenderer,
    ) {}
}
