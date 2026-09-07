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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Import;

/**
 * Categories are the local side of an MM relation whose records carry
 * "MM_opposite_field". The reference index deliberately skips that side, so the
 * relation has to be resolved by the exporter itself - otherwise the plain
 * database column value, which holds the *number* of relations, is imported as
 * if it were a uid list.
 */
final class CategoryRelationTest extends AbstractImportExportTestCase
{
    #[Test]
    public function categoryRelationSurvivesExportAndImport(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_category-with-relation.csv');
        // The local column of an MM relation holds the number of relations, not the uid
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['categories' => 1], ['uid' => 1]);

        $export = $this->get(Export::class);
        $export->setPid(1);
        $export->setLevels(0);
        $export->setTables(['tt_content']);
        $export->setRelOnlyTables(['sys_category']);
        $export->process();
        $file = $export->saveToFile();
        $this->testFilesToDelete[] = $file->getForLocalProcessing(false);

        $import = $this->get(Import::class);
        $import->setPid(2);
        $import->loadFile($file->getForLocalProcessing(false));
        $import->importData();
        self::assertSame([], $import->getErrorLog());

        // The imported content element must be related to "Category 2", not to the
        // category that happens to have the uid of the relation count.
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category_record_mm');
        $relatedTitles = $queryBuilder
            ->select('sys_category.title')
            ->from('sys_category_record_mm', 'mm')
            ->join('mm', 'sys_category', 'sys_category', 'mm.uid_local = sys_category.uid')
            ->join('mm', 'tt_content', 'tt_content', 'mm.uid_foreign = tt_content.uid')
            ->where(
                $queryBuilder->expr()->neq('tt_content.uid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories'))
            )
            ->executeQuery()
            ->fetchFirstColumn();

        self::assertSame(['Category 2'], $relatedTitles);
    }
}
