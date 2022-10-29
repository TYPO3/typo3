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

namespace TYPO3\CMS\Seo\Tests\Unit\HrefLang;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Seo\HrefLang\HrefLangGenerator;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HrefLangGeneratorTest extends UnitTestCase
{
    protected MockObject&AccessibleObjectInterface&HrefLangGenerator $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(
            HrefLangGenerator::class,
            ['dummy'],
            [
                $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder(LanguageMenuProcessor::class)->disableOriginalConstructor()->getMock(),
            ]
        );
    }

    public function urlPathWithoutHostDataProvider(): array
    {
        return [
            [
                '/',
            ],
            [
                'example.com', // This can't be defined as a domain because it can also be a filename
            ],
            [
                'filename.pdf',
            ],
            [
                'example.com/filename.pdf',
            ],
            [
                '/page-1/subpage-1',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider urlPathWithoutHostDataProvider
     */
    public function checkIfSiteLanguageGetBaseIsCalledForUrlsWithoutHost(string $url): void
    {
        $mockUriInterface = $this->getMockBuilder(UriInterface::class)->getMock();
        $mockSiteLanguage = $this->getMockBuilder(SiteLanguage::class)->disableOriginalConstructor()->getMock();
        $mockSiteLanguage->expects(self::once())->method('getBase')->willReturn($mockUriInterface);
        $this->subject->_call('getAbsoluteUrl', $url, $mockSiteLanguage);
    }

    public function urlPathWithHostDataProvider(): array
    {
        return [
            [
                '//example.com/filename.pdf',
            ],
            [
                '//example.com',
            ],
            [
                'https://example.com',
            ],
            [
                'https://example.com/page-1/subpage-1',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider urlPathWithHostDataProvider
     */
    public function checkIfSiteLanguageGetBaseIsNotCalledForUrlsWithHost(string $url): void
    {
        $mockUriInterface = $this->getMockBuilder(UriInterface::class)->getMock();
        $mockSiteLanguage = $this->getMockBuilder(SiteLanguage::class)->disableOriginalConstructor()->getMock();
        $mockSiteLanguage->expects(self::never())->method('getBase')->willReturn($mockUriInterface);
        $this->subject->_call('getAbsoluteUrl', $url, $mockSiteLanguage);
    }
}
