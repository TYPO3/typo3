<?php

declare(strict_types=1);
namespace TYPO3\CMS\Redirects\Tests\Unit\FormDataProvider;

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

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ValuePickerItemDataProviderTest extends UnitTestCase
{
    protected $sysRedirectResultSet = [
        'tableName' => 'sys_redirect',
        'processedTca' => [
            'columns' => [
                'source_host' => [
                    'config' => [
                        'valuePicker' => [
                            'items' => []
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * @test
     */
    public function addDataDoesNothingIfNoRedirectDataGiven()
    {
        $result = [
            'tableName' => 'tt_content',
        ];

        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getAllSites()->willReturn([]);
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider($siteFinderProphecy->reveal());
        $actualResult = $valuePickerItemDataProvider->addData($result);
        self::assertSame($result, $actualResult);
    }

    /**
     * @test
     */
    public function addDataAddsHostsAsKeyAndValueToRedirectValuePicker()
    {
        // no results for now
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getAllSites()->willReturn([
            new Site('bar', 13, ['base' => 'bar.test']),
            new Site('foo', 14, ['base' => 'foo.test'])
        ]);
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider($siteFinderProphecy->reveal());
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
    public function addDataDoesNotChangeResultSetIfNoSitesAreFound()
    {
        $siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $siteFinderProphecy->getAllSites()->willReturn([]);
        $valuePickerItemDataProvider = new ValuePickerItemDataProvider($siteFinderProphecy->reveal());
        $actualResult = $valuePickerItemDataProvider->addData($this->sysRedirectResultSet);

        self::assertSame($this->sysRedirectResultSet, $actualResult);
    }
}
