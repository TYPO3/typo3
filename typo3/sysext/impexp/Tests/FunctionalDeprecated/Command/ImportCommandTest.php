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
use TYPO3\CMS\Impexp\Command\ImportCommand;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

class ImportCommandTest extends AbstractImportExportTestCase
{
    /**
     * @test
     */
    public function importCommandPassesArgumentsToImportObject(): void
    {
        $input = [
            'file' => 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_language.xml',
            'pid' => 3,
            // @deprecated since v11, will be removed in v12. Drop the lowerCamelCase options.
            '--update-records' => false,
            '--updateRecords' => true,
            '--ignore-pid' => false,
            '--ignorePid' => true,
            '--force-uid' => false,
            '--forceUid' => true,
            '--enable-log' => false,
            '--enableLog' => true,
            '--import-mode' => [
                sprintf('pages:789=%s', Import::IMPORT_MODE_FORCE_UID),
                sprintf('tt_content:1=%s', Import::IMPORT_MODE_EXCLUDE),
            ],
            '--importMode' => [
                sprintf('pages:987=%s', Import::IMPORT_MODE_FORCE_UID),
                sprintf('tt_content:1=%s', Import::IMPORT_MODE_AS_NEW),
            ],
        ];

        $importMock = $this->getAccessibleMock(Import::class, [
            'setPid', 'setUpdate', 'setGlobalIgnorePid', 'setForceAllUids', 'setEnableLogging', 'loadFile',
            'setImportMode',
        ]);

        $importMock->expects(self::once())->method('setPid')->with(self::equalTo(3));
        $importMock->expects(self::once())->method('setUpdate')->with(self::equalTo(true));
        $importMock->expects(self::once())->method('setGlobalIgnorePid')->with(self::equalTo(true));
        $importMock->expects(self::once())->method('setForceAllUids')->with(self::equalTo(true));
        $importMock->expects(self::once())->method('setEnableLogging')->with(self::equalTo(true));
        $importMock->expects(self::once())->method('setImportMode')->with(self::equalTo([
            'pages:987' => Import::IMPORT_MODE_FORCE_UID,
            'tt_content:1' => Import::IMPORT_MODE_EXCLUDE,
            'pages:789' => Import::IMPORT_MODE_FORCE_UID,
        ]));
        $importMock->expects(self::once())->method('loadFile')->with(self::equalTo('EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_language.xml'));

        $tester = new CommandTester(new ImportCommand($importMock));
        $tester->execute($input);
    }
}
