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

use TYPO3\CMS\Backend\Form\FormDataProvider\SiteDatabaseEditRow;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteDatabaseEditRowTest extends UnitTestCase
{
    public function setUp(): void
    {
        $this->backupEnvironment = true;
        parent::setUp();
        Environment::initialize(
            $this->createMock(ApplicationContext::class),
            true,
            false,
            '',
            '',
            '',
            '',
            '',
            ''
        );
    }

    /**
     * @test
     */
    public function addDataDoesNotChangeResultIfCommandIsNotEdit(): void
    {
        $input = [
            'command' => 'new',
            'foo' => 'bar',
        ];
        $siteConfigurationMock = $this->createMock(SiteConfiguration::class);
        self::assertSame($input, (new SiteDatabaseEditRow($siteConfigurationMock))->addData($input));
    }

    /**
     * @test
     */
    public function addDataDoesNotChangeResultIfDatabaseRowIsNotEmpty(): void
    {
        $input = [
            'command' => 'edit',
            'databaseRow' => [
                'foo' => 'bar',
            ],
        ];
        $siteConfigurationMock = $this->createMock(SiteConfiguration::class);
        self::assertSame($input, (new SiteDatabaseEditRow($siteConfigurationMock))->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfTableNameIsNotExpected(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'foo',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520886234);
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteConfigurationMock = $this->createMock(SiteConfiguration::class);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderMock);
        (new SiteDatabaseEditRow($siteConfigurationMock))->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsDataForSysSite(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'site',
            'vanillaUid' => 23,
            'customData' => [
                'siteIdentifier' => 'main',
            ],
        ];
        $rowData = [
            'foo' => 'bar',
            'rootPageId' => 42,
            'someArray' => [
                'foo' => 'bar',
            ],
        ];
        $siteFinderMock = $this->createMock(SiteFinder::class);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderMock);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(23)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);

        $expected = $input;
        $expected['databaseRow'] = [
            'uid' => 42,
            'identifier' => 'main',
            'rootPageId' => 42,
            'pid' => 0,
            'foo' => 'bar',
        ];

        self::assertEquals($expected, (new SiteDatabaseEditRow($siteConfiguration))->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionWithInvalidErrorHandling(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'site_errorhandling',
            'vanillaUid' => 23,
            'inlineTopMostParentUid' => 5,
            'inlineParentFieldName' => 'invalid',
        ];
        $rowData = [
            'foo' => 'bar',
        ];
        $siteFinderMock = $this->createMock(SiteFinder::class);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderMock);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(5)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520886092);
        (new SiteDatabaseEditRow($siteConfiguration))->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionWithInvalidLanguage(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'site_language',
            'vanillaUid' => 23,
            'inlineTopMostParentUid' => 5,
            'inlineParentFieldName' => 'invalid',
        ];
        $rowData = [
            'foo' => 'bar',
        ];
        $siteFinderMock = $this->createMock(SiteFinder::class);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderMock);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(5)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520886092);
        (new SiteDatabaseEditRow($siteConfiguration))->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddLanguageRow(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'site_language',
            'vanillaUid' => 23,
            'inlineTopMostParentUid' => 5,
            'inlineParentFieldName' => 'languages',
        ];
        $rowData = [
            'languages' => [
                23 => [
                    'foo' => 'bar',
                ],
            ],
        ];
        $siteFinderMock = $this->createMock(SiteFinder::class);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinderMock);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(5)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);

        $expected = $input;
        $expected['databaseRow'] = [
            'foo' => 'bar',
            'uid' => 23,
            'pid' => 0,
        ];

        self::assertEquals($expected, (new SiteDatabaseEditRow($siteConfiguration))->addData($input));
    }
}
