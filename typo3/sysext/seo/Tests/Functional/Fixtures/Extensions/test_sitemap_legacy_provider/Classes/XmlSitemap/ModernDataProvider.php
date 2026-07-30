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

namespace TYPO3Tests\TestSitemapLegacyProvider\XmlSitemap;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemap;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapRequest;

/**
 * A data provider as it is implemented since TYPO3 v15: A stateless service using dependency
 * injection, registered via the tag of XmlSitemapDataProviderInterface.
 */
final readonly class ModernDataProvider implements XmlSitemapDataProviderInterface
{
    public function __construct(
        private Context $context,
    ) {}

    public function getSitemap(XmlSitemapRequest $sitemapRequest): XmlSitemap
    {
        $items = [];
        foreach (['first', 'second', 'third'] as $index => $identifier) {
            $items[] = [
                'identifier' => $identifier,
                'language' => (int)$this->context->getPropertyFromAspect('language', 'id'),
                'lastMod' => 1535655601 + $index,
            ];
        }
        return XmlSitemap::forPage(
            $items,
            $sitemapRequest->page,
            fn(array $item): array => $this->defineUrl($item, $sitemapRequest),
            2,
        );
    }

    private function defineUrl(array $item, XmlSitemapRequest $sitemapRequest): array
    {
        $item['loc'] = $sitemapRequest->contentObjectRenderer->createUrl([
            'parameter' => (int)$sitemapRequest->configuration['pageId'],
            'queryParameters' => [
                'tx_test[item]' => $item['identifier'],
                'tx_test[language]' => $item['language'],
            ],
            'forceAbsoluteUrl' => 1,
        ]);
        return $item;
    }
}
