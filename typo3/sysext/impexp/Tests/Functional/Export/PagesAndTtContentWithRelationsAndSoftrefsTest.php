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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class PagesAndTtContentWithRelationsAndSoftrefsTest extends AbstractImportExportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/form_definitions' => 'fileadmin/form_definitions',
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
                'list_type',
                'pi_flexform',
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

    #[Test]
    public function exportPagesAndRelatedTtContentWithFlexFormRelation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-flexform-relation.csv');

        $GLOBALS['TCA']['tt_content']['types']['text']['showitem'] .= ',pi_flexform';
        $GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['pi_flexform']['config']['ds'] = '
<T3DataStructure>
    <ROOT>
        <type>array</type>
        <el>
            <flexFormRelation>
                <label>FlexForm relation</label>
                <config>
                    <type>group</type>
                    <allowed>pages</allowed>
                    <size>1</size>
                    <maxitems>1</maxitems>
                    <minitems>0</minitems>
                </config>
            </flexFormRelation>
        </el>
    </ROOT>
</T3DataStructure>';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['tt_content']);
        $subject->setRelOnlyTables(['pages']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-flexform-relation.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithSoftrefs(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-softrefs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.csv');

        $GLOBALS['TCA']['tt_content']['types']['text']['showitem'] .= ',pi_flexform';
        $GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides']['pi_flexform']['config']['ds'] = '
<T3DataStructure>
    <ROOT>
        <type>array</type>
        <el>
            <softrefLink>
                <label>Soft reference link</label>
                <config>
                    <type>link</type>
                    <allowedTypes>page,file,url,record,telephone</allowedTypes>
                    <appearance>
                        <browserTitle>Link</browserTitle>
                    </appearance>
                </config>
            </softrefLink>
        </el>
    </ROOT>
</T3DataStructure>';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-softrefs.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithFlexFormSoftrefs(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-flexform-softrefs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/form_sys_file.csv');

        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] = '
<T3DataStructure>
    <sheets>
        <sDEF>
            <ROOT>
                <sheetTitle>LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.pi_flexform.formframework.sheet_general</sheetTitle>
                <type>array</type>
                <el>
                    <settings.persistenceIdentifier>
                        <label>LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.pi_flexform.formframework.persistenceIdentifier</label>
                        <onChange>reload</onChange>
                        <config>
                            <type>select</type>
                            <renderType>selectSingle</renderType>
                            <items>
                                <numIndex index="0" type="array">
                                    <label>LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.pi_flexform.formframework.selectPersistenceIdentifier</label>
                                    <value></value>
                                </numIndex>
                            </items>
                            <softref>formPersistenceIdentifier</softref>
                        </config>
                    </settings.persistenceIdentifier>
                </el>
            </ROOT>
        </sDEF>
    </sheets>
</T3DataStructure>';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();

        $out = $subject->render();

        // @todo Use self::assertXmlStringEqualsXmlFile() instead when sqlite issue is sorted out
        $this->assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-flexform-softrefs.xml',
            $out
        );
    }
}
