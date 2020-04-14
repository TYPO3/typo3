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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseSystemLanguageRowsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataThrowsExceptionIfSiteObjectIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1534952559);
        (new DatabaseSystemLanguageRows())->addData([]);
    }

    /**
     * @test
     */
    public function addDataSetsDefaultLanguageAndAllEntries()
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $siteProphecy = $this->prophesize(Site::class);
        $siteLanguageMinusOne = $this->prophesize(SiteLanguage::class);
        $siteLanguageMinusOne->getLanguageId()->willReturn(-1);
        $siteLanguageMinusOne->getTitle()->willReturn('All');
        $siteLanguageMinusOne->getFlagIdentifier()->willReturn('flags-multiple');
        $siteLanguageZero = $this->prophesize(SiteLanguage::class);
        $siteLanguageZero->getLanguageId()->willReturn(0);
        $siteLanguageZero->getTitle()->willReturn('English');
        $siteLanguageZero->getFlagIdentifier()->willReturn('empty-empty');
        $siteLanguageOne = $this->prophesize(SiteLanguage::class);
        $siteLanguageOne->getLanguageId()->willReturn(1);
        $siteLanguageOne->getTitle()->willReturn('Dutch');
        $siteLanguageOne->getFlagIdentifier()->willReturn('flag-nl');
        $siteLanguageOne->getTwoLetterIsoCode()->willReturn('NL');
        $siteLanguages = [
            $siteLanguageMinusOne->reveal(),
            $siteLanguageZero->reveal(),
            $siteLanguageOne->reveal(),
        ];
        $siteProphecy->getAvailableLanguages(Argument::cetera())->willReturn($siteLanguages);
        $input = [
            'effectivePid' => 42,
            'site' => $siteProphecy->reveal(),
        ];
        $expected = [
            'systemLanguageRows' => [
                -1 => [
                    'uid' => -1,
                    'title' => 'All',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'flags-multiple',
                ],
                0 => [
                    'uid' => 0,
                    'title' => 'English',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'empty-empty',
                ],
                1 => [
                    'uid' => 1,
                    'title' => 'Dutch',
                    'iso' => 'NL',
                    'flagIconIdentifier' => 'flag-nl',
                ]
            ],
        ];
        self::assertSame(array_merge($input, $expected), (new DatabaseSystemLanguageRows())->addData($input));
    }
}
