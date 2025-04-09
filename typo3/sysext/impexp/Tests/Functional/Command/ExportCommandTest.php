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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Resource\DefaultUploadFolderResolver;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Command\ExportCommand;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class ExportCommandTest extends AbstractImportExportTestCase
{
    #[Test]
    public function exportCommandRequiresNoArguments(): void
    {
        $subject = $this->get(Export::class);
        $tester = new CommandTester(new ExportCommand($subject));
        $tester->execute([]);
        self::assertEquals(0, $tester->getStatusCode());
    }

    #[Test]
    public function exportCommandSavesExportWithGivenFileName(): void
    {
        $fileName = 'empty_export';

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));

        $tester = new CommandTester(new ExportCommand($subject));
        $tester->execute(['filename' => $fileName], []);

        preg_match('/([^\s]*importexport[^\s]*)/', $tester->getDisplay(), $display);
        $filePath = Environment::getPublicPath() . '/' . $display[1];

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringEndsWith('empty_export.xml', $filePath);
        self::assertXmlFileEqualsXmlFile(__DIR__ . '/../Fixtures/XmlExports/empty.xml', $filePath);
    }

    #[Test]
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
            '--include-static' => ['sys_category'],
            '--exclude' => ['be_users:3'],
            '--exclude-disabled-records' => false,
            '--title' => 'Export Command',
            '--description' => 'The export which considers all arguments passed on the command line.',
            '--notes' => 'This export is not for production use.',
            '--dependency' => ['bootstrap_package'],
            '--save-files-outside-export-file' => false,
        ];

        $exportMock = $this->getAccessibleMock(
            Export::class,
            [
                'setExportFileType', 'setExportFileName', 'setPid', 'setLevels', 'setTables', 'setRecord', 'setList',
                'setRelOnlyTables', 'setRelStaticTables', 'setExcludeMap', 'setExcludeDisabledRecords',
                'setTitle', 'setDescription', 'setNotes', 'setExtensionDependencies', 'setSaveFilesOutsideExportFile',
            ],
            [
                $this->get(ConnectionPool::class),
                $this->get(Locales::class),
                $this->get(Typo3Version::class),
                $this->get(ReferenceIndex::class),
            ]
        );
        $exportMock->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));
        $exportMock->expects(self::once())->method('setExportFileName')->with(self::equalTo('empty_export'));
        $exportMock->expects(self::once())->method('setExportFileType')->with(self::equalTo(Export::FILETYPE_T3D));
        $exportMock->expects(self::once())->method('setPid')->with(self::equalTo(123));
        $exportMock->expects(self::once())->method('setLevels')->with(self::equalTo(Export::LEVELS_RECORDS_ON_THIS_PAGE));
        $exportMock->expects(self::once())->method('setTables')->with(self::equalTo(['tt_content']));
        $exportMock->expects(self::once())->method('setRecord')->with(self::equalTo(['sys_category:6']));
        $exportMock->expects(self::once())->method('setList')->with(self::equalTo(['sys_category:123']));
        $exportMock->expects(self::once())->method('setRelOnlyTables')->with(self::equalTo(['be_users']));
        $exportMock->expects(self::once())->method('setRelStaticTables')->with(self::equalTo(['sys_category']));
        $exportMock->expects(self::once())->method('setExcludeMap')->with(self::equalTo(['be_users:3']));
        $exportMock->expects(self::once())->method('setExcludeDisabledRecords')->with(self::equalTo(false));
        $exportMock->expects(self::once())->method('setTitle')->with(self::equalTo('Export Command'));
        $exportMock->expects(self::once())->method('setDescription')->with(self::equalTo('The export which considers all arguments passed on the command line.'));
        $exportMock->expects(self::once())->method('setNotes')->with(self::equalTo('This export is not for production use.'));
        $exportMock->expects(self::once())->method('setExtensionDependencies')->with(self::equalTo(['bootstrap_package']));
        $exportMock->expects(self::once())->method('setSaveFilesOutsideExportFile')->with(self::equalTo(false));

        $tester = new CommandTester(new ExportCommand($exportMock));
        $tester->execute($input);
    }
}
