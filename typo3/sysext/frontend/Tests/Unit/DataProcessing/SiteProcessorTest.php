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

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\DataProcessing\SiteProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class SiteProcessorTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @test
     */
    public function siteIsRetrieved(): void
    {
        $processorConfiguration = ['as' => 'variable'];
        $mockedContentObjectRenderer = $this->getAccessibleMock(ContentObjectRenderer::class, ['stdWrapValue'], [], '', false);
        $mockedContentObjectRenderer->expects(self::any())->method('stdWrapValue')->with('as', $processorConfiguration, 'site')->willReturn('variable');

        $site = new Site('site123', 123, []);
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy->getSite()->willReturn($site);

        $subject = new SiteProcessor($tsfeProphecy->reveal());
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

        $subject = new SiteProcessor();
        $processedData = $subject->process($mockedContentObjectRenderer, [], $processorConfiguration, []);

        self::assertNull($processedData['variable']);
    }
}
