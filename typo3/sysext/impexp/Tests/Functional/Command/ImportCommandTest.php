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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Impexp\Command\ImportCommand;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class ImportCommandTest extends AbstractImportExportTestCase
{
    #[Test]
    public function importCommandRequiresFileArgument(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "file")');
        $tester = new CommandTester(new ImportCommand(new Import()));
        $tester->execute([], []);
    }

    #[Test]
    public function importCommandRequiresFileArgumentOnly(): void
    {
        $filePath = 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_news.xml';
        $tester = new CommandTester(new ImportCommand(new Import()));
        $tester->execute(['file' => $filePath], []);
        self::assertEquals(0, $tester->getStatusCode());
    }

    #[Test]
    public function importCommandPassesArgumentsToImportObject(): void
    {
        $input = [
            'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_news.xml',
            'pid' => 3,
            '--update-records' => false,
            '--ignore-pid' => false,
            '--force-uid' => false,
            '--enable-log' => false,
            '--import-mode' => [
                sprintf('pages:789=%s', Import::IMPORT_MODE_FORCE_UID),
                sprintf('tt_content:1=%s', Import::IMPORT_MODE_EXCLUDE),
            ],
        ];

        $importMock = $this->getAccessibleMock(Import::class, [
            'setPid', 'setUpdate', 'setGlobalIgnorePid', 'setForceAllUids', 'setEnableLogging', 'loadFile',
            'setImportMode',
        ]);

        $importMock->expects($this->once())->method('setPid')->with(self::equalTo(3));
        $importMock->expects($this->once())->method('setUpdate')->with(self::equalTo(false));
        $importMock->expects($this->once())->method('setGlobalIgnorePid')->with(self::equalTo(false));
        $importMock->expects($this->once())->method('setForceAllUids')->with(self::equalTo(false));
        $importMock->expects($this->once())->method('setEnableLogging')->with(self::equalTo(false));
        $importMock->expects($this->once())->method('setImportMode')->with(self::equalTo([
            'tt_content:1' => Import::IMPORT_MODE_EXCLUDE,
            'pages:789' => Import::IMPORT_MODE_FORCE_UID,
        ]));
        $importMock->expects($this->once())->method('loadFile')->with(self::equalTo('EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_news.xml'));

        $tester = new CommandTester(new ImportCommand($importMock));
        $tester->execute($input);
    }

    public static function importCommandFailsDataProvider(): array
    {
        return [
            'path to not existing file' => [
                [
                    'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/me_does_not_exist.xml',
                    '--force-uid' => true,
                ],
                'expected' => 'File not found: ',
            ],
            'unsupported file extension' => [
                [
                    'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/unsupported.json',
                    '--force-uid' => true,
                ],
                'expected' => 'File extension "json" is not valid. Supported file extensions are "xml", "t3d".',
            ],
            'missing required extension' => [
                [
                    'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_category_table_with_news.xml',
                    '--force-uid' => true,
                ],
                'expected' => 'Prerequisites for file import are not met.',
            ],
            'missing required storage path' => [
                [
                    'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-image-with-invalid-storage.xml',
                    '--force-uid' => true,
                ],
                'expected' => 'Prerequisites for file import are not met.',
            ],
            'forcing uids of sys_file records not supported' => [
                [
                    'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-image-with-forced-uids.xml',
                    '--force-uid' => true,
                ],
                'expected' => 'The import has failed.',
            ],
            'import mode does not match associative array pattern of cli' => [
                [
                    'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml',
                    '--import-mode' => [sprintf('pages:987:%s', Import::IMPORT_MODE_FORCE_UID)],
                ],
                'expected' => sprintf('Command line option "import-mode" has invalid entry "pages:987:%s".', Import::IMPORT_MODE_FORCE_UID),
            ],
        ];
    }

    #[DataProvider('importCommandFailsDataProvider')]
    #[Test]
    public function importCommandFails(array $input, string $expected): void
    {
        $tester = new CommandTester(new ImportCommand(new Import()));
        $tester->execute(
            $input,
            ['verbosity' => Output::VERBOSITY_VERBOSE]
        );

        self::assertEquals(1, $tester->getStatusCode());
        self::assertStringContainsString($expected, $tester->getDisplay(true));
    }
}
