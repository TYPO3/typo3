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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use TYPO3\CMS\Backend\Controller\SiteConfigurationController;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SiteConfigurationControllerTest extends UnitTestCase
{

    /**
     * @test
     */
    public function duplicateEntryPointsAreRecognized()
    {
        $mockedSiteConfigurationController = $this->getAccessibleMock(SiteConfigurationController::class, ['dummy'], [], '', false);

        $sites = [
            new Site('site-1', 1, [
                'base' => '//domain1.tld',
                'languages' => [
                    [
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'de_DE.UTF-8',
                    ],
                    [
                        'languageId' => 1,
                        'base' => '/fr/',
                        'locale' => 'fr_FR.UTF-8',
                    ]
                ]
            ]),
            new Site('site-2', 1, [
                'base' => '//domain2.tld',
                'languages' => [
                    [
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'de_DE.UTF-8',
                    ],
                    [
                        'languageId' => 1,
                        'base' => 'https://domain1.tld/',
                        'locale' => 'fr_FR.UTF-8',
                    ],
                    [
                        'languageId' => 2,
                        'base' => 'https://domain2.tld/en',
                        'locale' => 'fr_FR.UTF-8',
                    ]
                ]
            ]),
            new Site('site-3', 3, [
                'base' => '/',
                'languages' => [
                    [
                        'languageId' => 0,
                        'base' => 'http://domain1.tld/de',
                        'locale' => 'de_DE.UTF-8',
                    ],
                    [
                        'languageId' => 1,
                        'base' => 'https://domain3.tld',
                        'locale' => 'fr_FR.UTF-8',
                    ]
                ]
            ]),
            new Site('site-4', 4, [
                'base' => '',
                'languages' => [
                    [
                        'languageId' => 0,
                        'base' => 'http://domain1.tld/de/',
                        'locale' => 'de_DE.UTF-8',
                    ],
                    [
                        'languageId' => 1,
                        'base' => 'http://domain1.tld/',
                        'locale' => 'fr_FR.UTF-8',
                    ]
                ]
            ]),
            new Site('site-5', 5, [
                'base' => '//domain2.tld/en/',
                'languages' => [
                    [
                        'languageId' => 0,
                        'base' => '/',
                        'locale' => 'de_DE.UTF-8',
                    ],
                ]
            ])
        ];
        $rootPages = array_flip([1, 2, 3, 4, 5]);

        $expected = [
            'domain1.tld' => [
                '//domain1.tld' => 1,
                'https://domain1.tld' => 1,
                'http://domain1.tld' => 1,
            ],
            'domain2.tld/en' => [
                'https://domain2.tld/en' => 1,
                '//domain2.tld/en' => 1,
            ],
            'domain1.tld/de' => [
                'http://domain1.tld/de' => 2,
            ],
        ];

        self::assertEquals($expected, $mockedSiteConfigurationController->_call('getDuplicatedEntryPoints', $sites, $rootPages));
    }
}
