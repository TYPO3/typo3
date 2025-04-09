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

final class MultilingualPagesAndTtContentTest extends AbstractImportExportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    protected array $recordTypesIncludeFields = [
        'pages' => [
            'title',
            'deleted',
            'doktype',
            'hidden',
            'perms_everybody',
            'l10n_parent',
            'l10n_source',
            'sys_language_uid',
            'sorting',
        ],
        'tt_content' => [
            'CType',
            'header',
            'deleted',
            'l18n_parent', // note l18n vs. l10n
            'l10n_source',
            'sys_language_uid',
            'sorting',
        ],

    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages-multilingual.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-multilingual.csv');
    }

    #[Test]
    public function exportMultilingualPagesAndRelatedTtContent(): void
    {
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-multilingual.xml',
            $out
        );
    }

    #[Test]
    public function exportSingleLanguagePageAndRelatedTtContent(): void
    {
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        // Restrict to pid=8 which is a the UID of german page, that belongs to a page
        // with 2 more records (default and french). Exporting that page needs to contain
        // data from the language parent, which is what's tested here.
        // The expected output is:
        // - pages.uid=8 (the german page)
        // - pages.uid=5 (the parent page with sys_language_uid=0)
        // - tt_content.uid=4 (german content)
        // - tt_content.uid=3 (default content)
        $subject->setPid(8);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->setRelOnlyTables(['_ALL']);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-multilingual-single.xml',
            $out
        );
    }

    #[Test]
    public function exportOnlySingleLanguagePageAndRelatedTtContent(): void
    {
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        // Restrict to pid=8 which is a the UID of german page, that belongs to a page
        // with 2 more records (default and french). This tests that the export does not
        // contain the language parent and thus not other language records on the same page.
        // (Difference is not using setRelOnlyTables!)
        // The expected output is:
        // - pages.uid=8 (the german page)
        // - tt_content.uid=4 (german content)
        $subject->setPid(8);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-multilingual-onlysingle.xml',
            $out
        );
    }

}
