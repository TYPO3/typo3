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

namespace TYPO3\CMS\Frontend\Typolink;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Factory for LinkResult instances
 */
class LinkResultFactory
{
    protected LinkService $linkService;

    public function __construct(LinkService $linkService)
    {
        $this->linkService = $linkService;
    }

    public function createFromUriString(string $uri): LinkResultInterface
    {
        $linkDetails = $this->linkService->resolve($uri);
        $linkDetails['typoLinkParameter'] = $uri;
        $linkType = $linkDetails['type'] ?? '';

        $linkBuilder = $this->resolveLinkBuilder($linkType);
        if ($linkBuilder !== null) {
            return $linkBuilder->build($linkDetails, '', '', []);
        }
        return GeneralUtility::makeInstance(
            LinkResult::class,
            $linkDetails['type'] ?? '',
            $linkDetails['url'] ?? $uri
        );
    }

    protected function resolveLinkBuilder(string $linkType): ?AbstractTypolinkBuilder
    {
        $className = (string)($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkType] ?? '');
        if (!is_a($className, AbstractTypolinkBuilder::class, true)) {
            return null;
        }
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return GeneralUtility::makeInstance($className, $contentObjectRenderer);
    }
}
