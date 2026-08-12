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

namespace TYPO3\CMS\Seo\Tests\Unit\XmlSitemap;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\Tests\Unit\XmlSitemap\Fixtures\LegacyXmlSitemapDataProviderFixture;
use TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemap;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapFactory;
use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class XmlSitemapFactoryTest extends UnitTestCase
{
    #[Test]
    public function createLetsRegisteredDataProviderServiceBuildTheSitemap(): void
    {
        $sitemapRequest = $this->createSitemapRequest();
        $dataProvider = new class implements XmlSitemapDataProviderInterface {
            public ?XmlSitemapRequest $sitemapRequest = null;
            public function getSitemap(XmlSitemapRequest $sitemapRequest): XmlSitemap
            {
                $this->sitemapRequest = $sitemapRequest;
                return XmlSitemap::forPage([['loc' => 'https://example.com/', 'lastMod' => 1535655756]]);
            }
        };
        $subject = new XmlSitemapFactory(new ServiceLocator([
            'my-provider' => static fn(): XmlSitemapDataProviderInterface => $dataProvider,
        ]));

        $sitemap = $subject->create('my-provider', $sitemapRequest);

        self::assertSame($sitemapRequest, $dataProvider->sitemapRequest);
        self::assertSame([['loc' => 'https://example.com/', 'lastMod' => 1535655756]], $sitemap->getItems());
        self::assertSame(1535655756, $sitemap->getLastModified());
        self::assertSame(1, $sitemap->getNumberOfPages());
    }

    #[IgnoreDeprecations]
    #[Test]
    public function createHandsRuntimeArgumentsToLegacyDataProviderConstructor(): void
    {
        $subject = new XmlSitemapFactory(new ServiceLocator([]));

        $sitemap = $subject->create(
            LegacyXmlSitemapDataProviderFixture::class,
            $this->createSitemapRequest('my-sitemap', ['suffix' => 'my-suffix'])
        );

        self::assertSame(
            ['https://example.com/my-sitemap', 'https://example.com/my-suffix'],
            array_column($sitemap->getItems(), 'loc')
        );
        self::assertSame(1535655756, $sitemap->getLastModified());
        self::assertSame(1, $sitemap->getNumberOfPages());
    }

    #[Test]
    public function createThrowsExceptionForDataProviderThatIsNoRegisteredService(): void
    {
        $subject = new XmlSitemapFactory(new ServiceLocator([]));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1785418954);

        $subject->create(\stdClass::class, $this->createSitemapRequest());
    }

    private function createSitemapRequest(string $name = 'pages', array $configuration = []): XmlSitemapRequest
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        return new XmlSitemapRequest(
            name: $name,
            configuration: $configuration,
            page: 0,
            request: $request,
            contentObjectRenderer: self::createStub(ContentObjectRenderer::class),
        );
    }
}
