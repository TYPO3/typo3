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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\SiteDatabaseEditRow;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteDatabaseEditRowTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

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

    #[Test]
    public function addDataDoesNotChangeResultIfCommandIsNotEdit(): void
    {
        $input = [
            'command' => 'new',
            'foo' => 'bar',
        ];
        self::assertSame($input, (new SiteDatabaseEditRow($this->createMock(SiteFinder::class)))->addData($input));
    }

    #[Test]
    public function addDataDoesNotChangeResultIfDatabaseRowIsNotEmpty(): void
    {
        $input = [
            'command' => 'edit',
            'databaseRow' => [
                'foo' => 'bar',
            ],
        ];
        self::assertSame($input, (new SiteDatabaseEditRow($this->createMock(SiteFinder::class)))->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfTableNameIsNotExpected(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'foo',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520886234);
        (new SiteDatabaseEditRow($this->createMock(SiteFinder::class)))->addData($input);
    }

    #[Test]
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
            'dependencies' => [
                'foo/bar',
                'baz',
            ],
        ];
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(23)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);
        GeneralUtility::setSingletonInstance(SiteConfiguration::class, $siteConfiguration);

        $expected = $input;
        $expected['databaseRow'] = [
            'uid' => 42,
            'identifier' => 'main',
            'rootPageId' => 42,
            'pid' => 0,
            'foo' => 'bar',
            'dependencies' => 'foo/bar,baz',
        ];

        self::assertEquals($expected, (new SiteDatabaseEditRow($siteFinderMock))->addData($input));
    }

    #[Test]
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
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(5)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);
        GeneralUtility::setSingletonInstance(SiteConfiguration::class, $siteConfiguration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520886092);
        (new SiteDatabaseEditRow($siteFinderMock))->addData($input);
    }

    #[Test]
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
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(5)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);
        GeneralUtility::setSingletonInstance(SiteConfiguration::class, $siteConfiguration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520886092);
        (new SiteDatabaseEditRow($siteFinderMock))->addData($input);
    }

    #[Test]
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
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByRootPageId')->with(5)->willReturn($siteMock);
        $siteMock->method('getIdentifier')->willReturn('testident');
        $siteConfiguration = $this->createMock(SiteConfiguration::class);
        $siteConfiguration->method('load')->with('testident')->willReturn($rowData);
        GeneralUtility::setSingletonInstance(SiteConfiguration::class, $siteConfiguration);

        $expected = $input;
        $expected['databaseRow'] = [
            'foo' => 'bar',
            'uid' => 23,
            'pid' => 0,
        ];

        self::assertEquals($expected, (new SiteDatabaseEditRow($siteFinderMock))->addData($input));
    }
}
