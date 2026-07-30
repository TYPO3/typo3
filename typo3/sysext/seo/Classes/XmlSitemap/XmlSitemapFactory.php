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

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException;

/**
 * Resolves the data provider configured for a sitemap and lets it build the sitemap.
 *
 * @internal this class is not part of TYPO3's Core API.
 */
final readonly class XmlSitemapFactory
{
    /**
     * @param ServiceLocator<XmlSitemapDataProviderInterface> $dataProviders
     */
    public function __construct(
        #[AutowireLocator(
            services: 'seo.xmlsitemap.provider',
        )]
        private ServiceLocator $dataProviders,
    ) {}

    /**
     * @throws InvalidConfigurationException
     */
    public function create(string $dataProviderClassName, XmlSitemapRequest $sitemapRequest): XmlSitemap
    {
        if ($this->dataProviders->has($dataProviderClassName)) {
            return $this->dataProviders->get($dataProviderClassName)->getSitemap($sitemapRequest);
        }
        if (is_subclass_of($dataProviderClassName, AbstractXmlSitemapDataProvider::class)) {
            return $this->createLegacySitemap($dataProviderClassName, $sitemapRequest);
        }
        throw new InvalidConfigurationException(
            'No valid provider set for ' . $sitemapRequest->name . ': Class "' . $dataProviderClassName
            . '" is not registered as a service implementing ' . XmlSitemapDataProviderInterface::class,
            1785418954
        );
    }

    /**
     * Providers based on the deprecated AbstractXmlSitemapDataProvider receive their runtime
     * information as constructor arguments and can therefore not be resolved by the container.
     *
     * @deprecated since TYPO3 v15.0, will be removed in TYPO3 v16.0 together with
     *             AbstractXmlSitemapDataProvider.
     */
    private function createLegacySitemap(string $dataProviderClassName, XmlSitemapRequest $sitemapRequest): XmlSitemap
    {
        $dataProvider = GeneralUtility::makeInstance(
            $dataProviderClassName,
            $sitemapRequest->request,
            $sitemapRequest->name,
            $sitemapRequest->configuration,
            $sitemapRequest->contentObjectRenderer
        );
        return $dataProvider->getSitemap($sitemapRequest);
    }
}
