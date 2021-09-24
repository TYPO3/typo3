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

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Seo\HrefLang\HrefLangGenerator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class HrefLangGeneratorTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     *
     * @param string $url
     *
     * @dataProvider urlPathDataProvider
     */
    public function checkIfGetSiteLanguageIsCalled(string $url): void
    {
        $subject = $this->getAccessibleMock(
            HrefLangGenerator::class,
            ['getSiteLanguage'],
            [
                $this->prophesize(ContentObjectRenderer::class)->reveal(),
                $this->prophesize(LanguageMenuProcessor::class)->reveal(),
            ]
        );

        $siteLanguageProphecy = $this->prophesize(SiteLanguage::class);
        $siteLanguageProphecy->getBase()->willReturn($this->prophesize(UriInterface::class)->reveal());
        $subject->_call('getAbsoluteUrl', $url, $siteLanguageProphecy->reveal());
    }

    /**
     * @return array
     */
    public function urlPathDataProvider(): array
    {
        return [
            [
                '/',
            ],
            [
                'example.com',
            ],
            [
                'filename.pdf',
            ],
            [
                'example.com/filename.pdf',
            ],
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
                '/page-1/subpage-1',
            ],
            [
                'https://example.com/page-1/subpage-1',
            ],
        ];
    }
}
