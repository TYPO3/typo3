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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\SiteLanguageProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteLanguageProcessorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function siteLanguageIsRetrieved(): void
    {
        $expectedName = 'currentLanguage';
        $processorConfiguration = ['as' => $expectedName];
        $request = new ServerRequest('https://example.com/lotus/');
        $siteLanguage = new SiteLanguage(123, 'de-de', $request->getUri(), ['customValue' => 'test']);
        $request = $request->withAttribute('language', $siteLanguage);
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);

        $subject = new SiteLanguageProcessor();
        $processedData = $subject->process($cObj, [], $processorConfiguration, []);
        self::assertEquals($siteLanguage->toArray(), $processedData[$expectedName]);
    }

    /**
     * @test
     */
    public function nullIsProvidedIfSiteLanguageCouldNotBeRetrieved(): void
    {
        $expectedName = 'currentSiteLanguage';
        $processorConfiguration = ['as' => $expectedName];
        $request = new ServerRequest('https://example.com/lotus/');
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);

        $subject = new SiteLanguageProcessor();
        $processedData = $subject->process($cObj, [], $processorConfiguration, []);
        self::assertNull($processedData[$expectedName]);
    }
}
