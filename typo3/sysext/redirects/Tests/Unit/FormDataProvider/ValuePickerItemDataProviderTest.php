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

namespace TYPO3\CMS\Redirects\Tests\Unit\FormDataProvider;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ValuePickerItemDataProviderTest extends UnitTestCase
{
    protected array $sysRedirectResultSet = [
        'tableName' => 'sys_redirect',
        'processedTca' => [
            'columns' => [
                'source_host' => [
                    'config' => [
                        'valuePicker' => [
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function addDataDoesNothingIfNoRedirectDataGiven(): void
    {
        $result = [
            'tableName' => 'tt_content',
        ];

        $siteFinderMock = $this->getMockBuilder(SiteFinder::class)->disableOriginalConstructor()->getMock();
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider($siteFinderMock);
        $actualResult = $valuePickerItemDataProvider->addData($result);
        self::assertSame($result, $actualResult);
    }

    /**
     * @test
     */
    public function addDataAddsHostsAsKeyAndValueToRedirectValuePicker(): void
    {
        // no results for now
        $siteFinderMock = $this->getMockBuilder(SiteFinder::class)->disableOriginalConstructor()->getMock();
        $siteFinderMock->expects(self::once())->method('getAllSites')->willReturn([
            new Site('bar', 13, ['base' => 'bar.test']),
            new Site('foo', 14, ['base' => 'foo.test']),
        ]);
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider($siteFinderMock);
        $actualResult = $valuePickerItemDataProvider->addData($this->sysRedirectResultSet);
        $expected = $this->sysRedirectResultSet;
        $expected['processedTca']['columns']['source_host']['config']['valuePicker']['items'] = [
            ['bar.test', 'bar.test'],
            ['foo.test', 'foo.test'],
        ];
        self::assertSame($expected, $actualResult);
    }

    /**
     * @test
     */
    public function addDataDoesNotChangeResultSetIfNoSitesAreFound(): void
    {
        $siteFinderMock = $this->getMockBuilder(SiteFinder::class)->disableOriginalConstructor()->getMock();
        $siteFinderMock->expects(self::once())->method('getAllSites')->willReturn([]);
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider($siteFinderMock);
        $actualResult = $valuePickerItemDataProvider->addData($this->sysRedirectResultSet);

        self::assertSame($this->sysRedirectResultSet, $actualResult);
    }
}
