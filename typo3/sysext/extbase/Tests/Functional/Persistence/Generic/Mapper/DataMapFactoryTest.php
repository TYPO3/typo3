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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use ExtbaseTeam\BlogExample\Domain\Model\Administrator;
use ExtbaseTeam\BlogExample\Domain\Model\TtContent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DataMapFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @var DataMapFactory
     */
    protected $dataMapFactory;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dataMapFactory = $this->get(DataMapFactory::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    /**
     * @test
     */
    public function classSettingsAreResolved(): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(Administrator::class);

        self::assertInstanceOf(DataMap::class, $dataMap);
        self::assertEquals('ExtbaseTeam\BlogExample\Domain\Model\Administrator', $dataMap->getRecordType());
        self::assertEquals('fe_users', $dataMap->getTableName());
    }

    /**
     * @test
     */
    public function columnMapPropertiesAreResolved(): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(TtContent::class);

        self::assertInstanceOf(DataMap::class, $dataMap);
        self::assertNull($dataMap->getColumnMap('thisPropertyDoesNotExist'));

        $headerColumnMap = $dataMap->getColumnMap('header');

        self::assertInstanceOf(ColumnMap::class, $headerColumnMap);
        self::assertEquals('header', $headerColumnMap->getPropertyName());
        self::assertEquals('header', $headerColumnMap->getColumnName());
    }
}
