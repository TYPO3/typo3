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

namespace TYPO3\CMS\Impexp\Tests\FunctionalDeprecated\Command;

use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Impexp\Command\ExportCommand;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class ExportCommandTest extends AbstractImportExportTestCase
{
    /**
     * @test
     */
    public function exportCommandPassesArgumentsToExportObject(): void
    {
        $input = [
            'filename' => 'empty_export',
            '--type' => Export::FILETYPE_T3D,
            '--pid' => 123,
            '--levels' => Export::LEVELS_RECORDS_ON_THIS_PAGE,
            '--table' => ['tt_content'],
            '--record' => ['sys_category:6'],
            '--list' => ['sys_category:123'],
            '--include-related' => ['be_users'],
            // @deprecated since v11, will be removed in v12. Drop the lowerCamelCase options.
            '--includeRelated' => ['be_groups'],
            '--include-static' => ['sys_category'],
            '--includeStatic' => ['sys_language'],
            '--exclude' => ['be_users:3'],
            '--exclude-disabled-records' => false,
            '--excludeDisabledRecords' => true,
            '--exclude-html-css' => false,
            '--excludeHtmlCss' => true,
            '--title' => 'Export Command',
            '--description' => 'The export which considers all arguments passed on the command line.',
            '--notes' => 'This export is not for production use.',
            '--dependency' => ['bootstrap_package'],
            '--save-files-outside-export-file' => false,
            '--saveFilesOutsideExportFile' => true,
        ];

        $exportMock = $this->getAccessibleMock(Export::class, [
            'setExportFileType', 'setExportFileName', 'setPid', 'setLevels', 'setTables', 'setRecord', 'setList',
            'setRelOnlyTables', 'setRelStaticTables', 'setExcludeMap', 'setExcludeDisabledRecords',
            'setIncludeExtFileResources', 'setTitle', 'setDescription', 'setNotes', 'setExtensionDependencies',
            'setSaveFilesOutsideExportFile',
        ]);
        $exportMock->expects(self::once())->method('setExportFileName')->with(self::equalTo('empty_export'));
        $exportMock->expects(self::once())->method('setExportFileType')->with(self::equalTo(Export::FILETYPE_T3D));
        $exportMock->expects(self::once())->method('setPid')->with(self::equalTo(123));
        $exportMock->expects(self::once())->method('setLevels')->with(self::equalTo(Export::LEVELS_RECORDS_ON_THIS_PAGE));
        $exportMock->expects(self::once())->method('setTables')->with(self::equalTo(['tt_content']));
        $exportMock->expects(self::once())->method('setRecord')->with(self::equalTo(['sys_category:6']));
        $exportMock->expects(self::once())->method('setList')->with(self::equalTo(['sys_category:123']));
        $exportMock->expects(self::once())->method('setRelOnlyTables')->with(self::equalTo(['be_groups', 'be_users']));
        $exportMock->expects(self::once())->method('setRelStaticTables')->with(self::equalTo(['sys_language', 'sys_category']));
        $exportMock->expects(self::once())->method('setExcludeMap')->with(self::equalTo(['be_users:3']));
        $exportMock->expects(self::once())->method('setExcludeDisabledRecords')->with(self::equalTo(true));
        $exportMock->expects(self::once())->method('setIncludeExtFileResources')->with(self::equalTo(false));
        $exportMock->expects(self::once())->method('setTitle')->with(self::equalTo('Export Command'));
        $exportMock->expects(self::once())->method('setDescription')->with(self::equalTo('The export which considers all arguments passed on the command line.'));
        $exportMock->expects(self::once())->method('setNotes')->with(self::equalTo('This export is not for production use.'));
        $exportMock->expects(self::once())->method('setExtensionDependencies')->with(self::equalTo(['bootstrap_package']));
        $exportMock->expects(self::once())->method('setSaveFilesOutsideExportFile')->with(self::equalTo(true));

        $tester = new CommandTester(new ExportCommand($exportMock));
        $tester->execute($input);
    }
}
