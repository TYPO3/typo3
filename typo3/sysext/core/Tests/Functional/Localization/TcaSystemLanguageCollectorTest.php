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

namespace TYPO3\CMS\Core\Tests\Functional\Localization;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\TcaSystemLanguageCollector;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TcaSystemLanguageCollectorTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $user = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($user);
    }

    /**
     * @test
     */
    public function populateAvailableSiteLanguagesTest(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteFinderMock->method('getAllSites')->willReturn([
            new Site('site-1', 1, [
                'base' => '/',
                'languages' => [
                    [
                        'title' => 'English',
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'en_US',
                        'flag' => 'us',
                    ],
                    [
                        'title' => 'German',
                        'languageId' => 2,
                        'base' => '/de/',
                        'locale' => 'de_DE',
                        'flag' => 'de',
                    ],
                ],
            ]),
            new Site('site-2', 2, [
                'base' => '/',
                'languages' => [
                    [
                        'title' => 'English',
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'en_US',
                        'flag' => 'us',
                    ],
                ],
            ]),
        ]);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderMock);
        $expectedItems = [
            0 => [
                'label' => 'English [Site: site-1], English [Site: site-2]',
                'value' => 0,
                'icon' => 'flags-us',
            ],
            1 => [
                'label' => 'German [Site: site-1]',
                'value' => 2,
                'icon' => 'flags-de',
            ],
        ];
        $fieldInformation = ['items' => []];
        (new TcaSystemLanguageCollector(new Locales()))->populateAvailableSiteLanguages($fieldInformation);
        self::assertSame($expectedItems, $fieldInformation['items']);
    }

    /**
     * @test
     */
    public function populateAvailableSiteLanguagesWithoutSiteTest(): void
    {
        $expectedItems = [
            0 => [
                'label' => 'Default',
                'value' => 0,
                'icon' => '',
            ],
        ];
        $fieldInformation = ['items' => []];
        (new TcaSystemLanguageCollector(new Locales()))->populateAvailableSiteLanguages($fieldInformation);
        self::assertSame($expectedItems, $fieldInformation['items']);
    }
}
