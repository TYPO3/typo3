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
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

class PagesAndTtContentTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected array $pathsToLinkInTestInstance = [
            'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
            'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    protected array $recordTypesIncludeFields =
        [
            'pages' => [
                'title',
                'deleted',
                'doktype',
                'hidden',
                'perms_everybody',
            ],
            'tt_content' => [
                'CType',
                'header',
                'header_link',
                'deleted',
                'hidden',
                't3ver_oid',
            ],
            'sys_file' => [
                'storage',
                'type',
                'metadata',
                'identifier',
                'identifier_hash',
                'folder_hash',
                'mime_type',
                'name',
                'sha1',
                'size',
                'creation_date',
                'modification_date',
            ],
        ]
    ;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.xml');
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContent(): void
    {
        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData']);
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent.xml',
            $out
        );
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithComplexConfiguration(): void
    {
        /** @var Export|MockObject|AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData']);
        $subject->setPid(1);
        $subject->setExcludeMap(['pages:2' => 1]);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->setExcludeDisabledRecords(true);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-complex.xml',
            $out
        );
    }
}
