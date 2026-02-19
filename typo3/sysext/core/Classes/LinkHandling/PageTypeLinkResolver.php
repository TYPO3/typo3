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

namespace TYPO3\CMS\Core\LinkHandling;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;

/**
 * @internal Used internally in handling and resolving pages of type link
 */
#[Autoconfigure(public: true, shared: true)]
readonly class PageTypeLinkResolver
{
    public function __construct(
        protected TypoLinkCodecService $linkCodecService,
        protected LinkService $linkService,
        protected LinkFactory $linkFactory,
    ) {}

    /**
     * Returns the resolved frontend link for a page with type link (doktype 3)
     */
    public function resolvePageLinkUrl(array $pageRecord, ServerRequestInterface $request, ?ContentObjectRenderer $contentObjectRenderer = null): string
    {
        if ((int)($pageRecord['doktype'] ?? 0) !== PageRepository::DOKTYPE_LINK) {
            throw new \RuntimeException(
                sprintf('This class may only be used with pages of doktype "Link" (3), doktype %s is not supported', $pageRecord['doktype']),
                1762776856,
            );
        }
        $typolink = (string)($pageRecord['link'] ?? '');
        if ($contentObjectRenderer === null) {
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
        }
        $url = $contentObjectRenderer->typoLink_URL(['parameter' => $typolink]);
        return $url;
    }

    /**
     * Determines the HTTP status for redirect in the middleware
     *
     * If the destination is a page, we use 307 to preserve the request type
     * For external URLs and custom types we use 303 see other. It prevents
     * browsers from accidentally sending POST data to the external site.
     *
     * Emails addresses, phone numbers and folders cannot be  forwarded and
     * return null.
     */
    public function getRedirectStatus(array $pageRecord): ?int
    {
        $typolinkParts = $this->resolveTypolinkParts($pageRecord);
        switch ($typolinkParts['type']) {
            case 'file':
                $file = $typolinkParts['file'];
                if ($file->getStorage()->isPublic()) {
                    return 302;
                }
                return null;
            case 'page':
                return 307;
            case 'email':
            case 'telephone':
            case 'folder':
                return null;
            default:
                return 303;
        }
    }

    /**
     * Returns the decoded TypoLink parts like url, target, additional parameters etc.
     * merged with the resolved url part, which contains information about the link type
     * (page, file, external, ..)
     */
    public function resolveTypolinkParts(array $pageRecord): array
    {
        if ((int)($pageRecord['doktype'] ?? 0) !== PageRepository::DOKTYPE_LINK) {
            throw new \RuntimeException(
                sprintf('This class may only be used with pages of doktype "Link" (3), doktype %s is not supported', $pageRecord['doktype']),
                1762776917,
            );
        }
        $typolinkTargetData = $this->linkCodecService->decode($pageRecord['link'] ?? '');
        $typolinkTargetLinkParts = $this->linkService->resolve($typolinkTargetData['url']);

        return array_merge($typolinkTargetData, $typolinkTargetLinkParts);
    }
}
