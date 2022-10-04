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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
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
        $expectedName = 'currentSite';
        $processorConfiguration = ['as' => $expectedName];
        $request = new ServerRequest('https://example.com/lotus/');
        $site = new Site('main', 123, []);
        $request = $request->withAttribute('site', $site);
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);

        $subject = new SiteProcessor();
        $processedData = $subject->process($cObj, [], $processorConfiguration, []);
        self::assertEquals($site, $processedData[$expectedName]);
    }

    /**
     * @test
     */
    public function nullIsProvidedIfSiteCouldNotBeRetrieved(): void
    {
        $expectedName = 'currentSite';
        $processorConfiguration = ['as' => $expectedName];
        $request = new ServerRequest('https://example.com/lotus/');
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);

        $subject = new SiteProcessor();
        $processedData = $subject->process($cObj, [], $processorConfiguration, []);
        self::assertNull($processedData[$expectedName]);
    }
}
