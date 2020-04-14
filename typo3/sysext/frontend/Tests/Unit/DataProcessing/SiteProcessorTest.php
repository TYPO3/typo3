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

namespace TYPO3\CMS\Frontend\Tests\Unit\DataProcessing;

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\SiteProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class SiteProcessorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function siteIsRetrieved(): void
    {
        $processorConfiguration = ['as' => 'variable'];
        $mockedContentObjectRenderer = $this->getAccessibleMock(ContentObjectRenderer::class, ['stdWrapValue'], [], '', false);
        $mockedContentObjectRenderer->expects(self::any())->method('stdWrapValue')->with('as', $processorConfiguration, 'site')->willReturn('variable');

        $site = new Site('site123', 123, []);

        $subject = $this->getAccessibleMock(SiteProcessor::class, ['getCurrentSite'], []);
        $subject->expects(self::any())->method('getCurrentSite')->willReturn($site);

        $processedData = $subject->process($mockedContentObjectRenderer, [], $processorConfiguration, []);

        self::assertEquals($site, $processedData['variable']);
    }

    /**
     * @test
     */
    public function nullIsProvidedIfSiteCouldNotBeRetrieved(): void
    {
        $processorConfiguration = ['as' => 'variable'];
        $mockedContentObjectRenderer = $this->getAccessibleMock(ContentObjectRenderer::class, ['stdWrapValue'], [], '', false);
        $mockedContentObjectRenderer->expects(self::any())->method('stdWrapValue')->with('as', $processorConfiguration, 'site')->willReturn('variable');

        $finderMock = $this->getMockBuilder(SiteFinder::class)->disableOriginalConstructor()->getMock();
        $finderMock->expects(self::any())->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('message', 1550670118));

        $subject = $this->getAccessibleMock(SiteProcessor::class, ['getSiteFinder', 'getCurrentPageId'], []);
        $subject->expects(self::any())->method('getSiteFinder')->willReturn($finderMock);
        $subject->expects(self::any())->method('getCurrentPageId')->willReturn(1);

        $processedData = $subject->process($mockedContentObjectRenderer, [], $processorConfiguration, []);

        self::assertNull($processedData['variable']);
    }
}
