<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\Tests\Unit\HrefLang;

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

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Seo\HrefLang\HrefLangGenerator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class HrefLangGeneratorTest extends UnitTestCase
{
    /**
     * @test
     *
     * @param string $url
     * @param bool $shouldBeCalled
     *
     * @dataProvider urlPathDataProvider
     */
    public function checkIfGetSiteLanguageIsCalled($url, $shouldBeCalled)
    {
        $subject = $this->getAccessibleMock(
            HrefLangGenerator::class,
            ['getSiteLanguage'],
            [
                $this->prophesize(ContentObjectRenderer::class)->reveal(),
                $this->prophesize(TypoScriptFrontendController::class)->reveal()
            ],
            '',
            true
        );

        $check = $shouldBeCalled ? $this->once() : $this->never();
        $subject->expects($check)->method('getSiteLanguage');
        $subject->_call('getAbsoluteUrl', $url);
    }

    /**
     * @return array
     */
    public function urlPathDataProvider(): array
    {
        return [
            [
                '/',
                true
            ],
            [
                'example.com',
                true    //This can't be defined as a domain because it can also be a filename
            ],
            [
                'filename.pdf',
                true
            ],
            [
                'example.com/filename.pdf',
                true
            ],
            [
                '//example.com/filename.pdf',
                false
            ],
            [
                '//example.com',
                false
            ],
            [
                'https://example.com',
                false
            ],
            [
                '/page-1/subpage-1',
                true
            ],
            [
                'https://example.com/page-1/subpage-1',
                false
            ],
        ];
    }
}
