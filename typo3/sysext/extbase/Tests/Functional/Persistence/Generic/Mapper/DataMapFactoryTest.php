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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\RestrictedComment;
use TYPO3Tests\BlogExample\Domain\Model\TtContent;

final class DataMapFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected DataMapFactory $dataMapFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataMapFactory = $this->get(DataMapFactory::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    #[Test]
    public function classSettingsAreResolved(): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(Administrator::class);

        self::assertInstanceOf(DataMap::class, $dataMap);
        self::assertEquals('TYPO3Tests\BlogExample\Domain\Model\Administrator', $dataMap->getRecordType());
        self::assertEquals('fe_users', $dataMap->getTableName());
    }

    #[Test]
    public function columnMapPropertiesAreResolved(): void
    {
        $dataMap = $this->dataMapFactory->buildDataMap(TtContent::class);

        self::assertInstanceOf(DataMap::class, $dataMap);
        self::assertNull($dataMap->getColumnMap('thisPropertyDoesNotExist'));

        $headerColumnMap = $dataMap->getColumnMap('header');

        self::assertInstanceOf(ColumnMap::class, $headerColumnMap);
        self::assertEquals('header', $headerColumnMap->getColumnName());
    }

    #[Test]
    public function customRestrictionFieldsAreMappedWithProperDataMap(): void
    {
        $subject = $this->get(DataMapFactory::class);
        $map = $subject->buildDataMap(RestrictedComment::class);

        self::assertSame('customhidden', $map->getDisabledFlagColumnName());
        self::assertSame('customstarttime', $map->getStartTimeColumnName());
        self::assertSame('customendtime', $map->getEndTimeColumnName());
        self::assertSame('customfegroup', $map->getFrontendUserGroupColumnName());
        self::assertSame('customsyslanguageuid', $map->getLanguageIdColumnName());
        self::assertSame('custom_l10182342n_parent', $map->getTranslationOriginColumnName());
        self::assertSame('custom_l10182342n_diff', $map->getTranslationOriginDiffSourceName());
        self::assertSame('customtstamp', $map->getModificationDateColumnName());
        self::assertSame('customcrdate', $map->getCreationDateColumnName());
        self::assertSame('customdeleted', $map->getDeletedFlagColumnName());
        self::assertSame('custom_ctype', $map->getRecordTypeColumnName());
    }
}
