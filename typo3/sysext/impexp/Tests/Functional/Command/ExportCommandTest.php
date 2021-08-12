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

namespace TYPO3\CMS\Impexp\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
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
    public function exportCommandRequiresNoArguments(): void
    {
        $exportMock = $this->getAccessibleMock(Export::class, ['setMetaData']);
        $tester = new CommandTester(new ExportCommand($exportMock));
        $tester->execute([], []);

        self::assertEquals(0, $tester->getStatusCode());
    }

    /**
     * @test
     */
    public function exportCommandSavesExportWithGivenFileName(): void
    {
        $fileName = 'empty_export';

        $exportMock = $this->getAccessibleMock(Export::class, ['setMetaData']);
        $tester = new CommandTester(new ExportCommand($exportMock));
        $tester->execute(['filename' => $fileName], []);

        preg_match('/([^\s]*importexport[^\s]*)/', $tester->getDisplay(), $display);
        $filePath = Environment::getPublicPath() . '/' . $display[1];

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringEndsWith('empty_export.xml', $filePath);
        self::assertXmlFileEqualsXmlFile(__DIR__ . '/../Fixtures/XmlExports/empty.xml', $filePath);
    }

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
            '--includeRelated' => ['be_users'],
            '--includeStatic' => ['sys_language'],
            '--exclude' => ['be_users:3'],
            '--excludeDisabledRecords' => true,
            '--excludeHtmlCss' => true,
            '--title' => 'Export Command',
            '--description' => 'The export which considers all arguments passed on the command line.',
            '--notes' => 'This export is not for production use.',
            '--dependency' => ['bootstrap_package'],
            '--saveFilesOutsideExportFile' => true
        ];

        $exportMock = $this->getAccessibleMock(Export::class, [
            'setExportFileType', 'setExportFileName', 'setPid', 'setLevels', 'setTables', 'setRecord', 'setList',
            'setRelOnlyTables', 'setRelStaticTables', 'setExcludeMap', 'setExcludeDisabledRecords',
            'setIncludeExtFileResources', 'setTitle', 'setDescription', 'setNotes', 'setExtensionDependencies',
            'setSaveFilesOutsideExportFile'
        ]);
        $exportMock->expects(self::once())->method('setExportFileName')->with(self::equalTo($input['filename']));
        $exportMock->expects(self::once())->method('setExportFileType')->with(self::equalTo($input['--type']));
        $exportMock->expects(self::once())->method('setPid')->with(self::equalTo($input['--pid']));
        $exportMock->expects(self::once())->method('setLevels')->with(self::equalTo($input['--levels']));
        $exportMock->expects(self::once())->method('setTables')->with(self::equalTo($input['--table']));
        $exportMock->expects(self::once())->method('setRecord')->with(self::equalTo($input['--record']));
        $exportMock->expects(self::once())->method('setList')->with(self::equalTo($input['--list']));
        $exportMock->expects(self::once())->method('setRelOnlyTables')->with(self::equalTo($input['--includeRelated']));
        $exportMock->expects(self::once())->method('setRelStaticTables')->with(self::equalTo($input['--includeStatic']));
        $exportMock->expects(self::once())->method('setExcludeMap')->with(self::equalTo($input['--exclude']));
        $exportMock->expects(self::once())->method('setExcludeDisabledRecords')->with(self::equalTo($input['--excludeDisabledRecords']));
        $exportMock->expects(self::once())->method('setIncludeExtFileResources')->with(self::equalTo(!$input['--excludeHtmlCss']));
        $exportMock->expects(self::once())->method('setTitle')->with(self::equalTo($input['--title']));
        $exportMock->expects(self::once())->method('setDescription')->with(self::equalTo($input['--description']));
        $exportMock->expects(self::once())->method('setNotes')->with(self::equalTo($input['--notes']));
        $exportMock->expects(self::once())->method('setExtensionDependencies')->with(self::equalTo($input['--dependency']));
        $exportMock->expects(self::once())->method('setSaveFilesOutsideExportFile')->with(self::equalTo($input['--saveFilesOutsideExportFile']));

        $tester = new CommandTester(new ExportCommand($exportMock));
        $tester->execute($input);
    }
}
