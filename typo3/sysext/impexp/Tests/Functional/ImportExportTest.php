<?php

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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Import;

final class ImportExportTest extends AbstractImportExportTestCase
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
    public function importExportPingPongSucceeds(): void
    {
        $recordTypesIncludeFields = include __DIR__ . '/Fixtures/IrreRecordsIncludeFields.php';

        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/irre-records.xml');
        $import->setForceAllUids(true);
        $import->importData();

        $exportMock = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $exportMock->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $exportMock->setPid(1);
        $exportMock->setLevels(Export::LEVELS_INFINITE);
        $exportMock->setTables(['_ALL']);
        $exportMock->setRelOnlyTables(['_ALL']);
        $exportMock->setRecordTypesIncludeFields($recordTypesIncludeFields);
        $exportMock->process();
        $actual = $exportMock->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/Fixtures/XmlImports/irre-records.xml',
            $actual
        );
    }
}
