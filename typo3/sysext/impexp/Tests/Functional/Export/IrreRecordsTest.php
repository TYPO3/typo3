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

namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class IrreRecordsTest extends AbstractImportExportTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mm',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnsymmetric',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnattributeinline',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnattributesimple',
    ];

    #[Test]
    public function exportIrreRecords(): void
    {
        $recordTypesIncludeFields = include __DIR__ . '/../Fixtures/IrreRecordsIncludeFields.php';

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/irre_records.csv');

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->setPid(1);
        $subject->setTables(['_ALL']);
        $subject->setRecordTypesIncludeFields($recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/irre-records.xml',
            $out
        );
    }
}
