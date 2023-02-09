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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseSystemLanguageRowsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataThrowsExceptionIfSiteObjectIsNotSet(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1534952559);
        (new DatabaseSystemLanguageRows())->addData([]);
    }

    /**
     * @test
     */
    public function addDataSetsDefaultLanguageAndAllEntries(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->withAnyParameters()->willReturnArgument(0);
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);

        $siteLanguageMinusOne = new SiteLanguage(
            -1,
            '',
            new Uri('/'),
            ['title' => 'All', 'flag' => 'flags-multiple']
        );
        $siteLanguageZero = new SiteLanguage(
            0,
            'en',
            new Uri('/en/'),
            ['title' => 'English', 'flag' => 'empty-empty']
        );
        $siteLanguageOne = new SiteLanguage(
            1,
            'nl_NL',
            new Uri('/nl/'),
            ['title' => 'Dutch', 'flag' => 'flag-nl']
        );
        $siteLanguages = [
            $siteLanguageMinusOne,
            $siteLanguageZero,
            $siteLanguageOne,
        ];
        $siteMock = $this->createMock(Site::class);
        $siteMock->method('getAvailableLanguages')->withAnyParameters()->willReturn($siteLanguages);
        $input = [
            'effectivePid' => 42,
            'site' => $siteMock,
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
                    'iso' => 'nl',
                    'flagIconIdentifier' => 'flag-nl',
                ],
            ],
        ];
        self::assertSame(array_merge($input, $expected), (new DatabaseSystemLanguageRows())->addData($input));
    }
}
