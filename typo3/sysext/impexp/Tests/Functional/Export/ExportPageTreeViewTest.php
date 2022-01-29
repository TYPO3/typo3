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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\CMS\Impexp\View\ExportPageTreeView;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

class ExportPageTreeViewTest extends AbstractImportExportTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    public function printTreeSucceedsDataProvider(): array
    {
        return [
            ['pid' => 0, 'levels' => Export::LEVELS_EXPANDED_TREE, 'expectedTreeItemsCount' => 2],
            ['pid' => 1, 'levels' => Export::LEVELS_EXPANDED_TREE, 'expectedTreeItemsCount' => 1],
            ['pid' => 0, 'levels' => 0, 'expectedTreeItemsCount' => 1],
            ['pid' => 1, 'levels' => 0, 'expectedTreeItemsCount' => 1],
        ];
    }

    /**
     * @test
     * @dataProvider printTreeSucceedsDataProvider
     */
    public function printTreeSucceeds(int $pid, int $levels, int $expectedTreeItemsCount): void
    {
        // @todo: This test needs an overhaul.
        //        It fails with mariadb / mysql with "DOMDocument::loadXML(): Namespace prefix xlink for href on use is not defined in Entity, line: 6"
        //        it fails with sqlite with data set 0 due to missing '</li></ul>' at the end. ExportPagetTree class issue?
        self::markTestSkipped();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/irre_tutorial.xml');

        $exportPageTreeView = $this->getAccessibleMock(ExportPageTreeView::class, ['dummy']);
        GeneralUtility::addInstance(ExportPageTreeView::class, $exportPageTreeView);

        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, [
            'setMetaData', 'exportAddRecordsFromRelations', 'exportAddFilesFromRelations', 'exportAddFilesFromSysFilesRecords',
        ]);
        $subject->setPid($pid);
        $subject->setLevels($levels);
        $subject->setTables(['_ALL']);
        $subject->process();

        $clause = $exportPageTreeView->_get('clause');
        $tree = $exportPageTreeView->_get('tree');
        $treeHtml = $subject->_get('treeHTML');
        $domDocument = new \DOMDocument();

        self::assertEquals(
            ['deleted=0', 'sys_language_uid=0'],
            GeneralUtility::trimExplode('AND', $clause, true)
        );
        self::assertEquals($expectedTreeItemsCount, count($tree));
        self::assertEquals($expectedTreeItemsCount, substr_count($treeHtml, '<span class="list-tree-title">'));
        foreach ($tree as $treeItem) {
            self::assertStringContainsString(
                sprintf('id="pages%d"', $treeItem['row']['uid']),
                $treeHtml
            );
            self::assertStringContainsString(
                sprintf('<span class="list-tree-title">%s</span>', $treeItem['row']['title']),
                $treeHtml
            );
        }

        self::assertTrue($domDocument->loadXML($treeHtml));
    }
}
