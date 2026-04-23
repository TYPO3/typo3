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
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class PagesAndTtContentTest extends AbstractImportExportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');
    }

    #[Test]
    public function exportPagesAndRelatedTtContent(): void
    {
        $subject = $this->get(Export::class);
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->process();

        $out = $subject->render();

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithMetaData(): void
    {
        $subject = $this->get(Export::class);
        $subject->setTitle('Test Export');
        $subject->setDescription('Export of pages and tt_content');
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->process();

        $out = $subject->render();

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-meta.xml',
            $out
        );
    }

    #[Test]
    public function exportHeaderChildOrderIsCorrect(): void
    {
        $subject = $this->get(Export::class);
        $subject->setTitle('Order Test');
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelStaticTables(['static_countries']);
        $subject->setExcludeMap(['pages:2' => 1]);
        $subject->setSoftrefCfg(['token123' => ['mode' => 'exclude']]);
        $subject->setExtensionDependencies(['news']);
        $subject->process();

        $out = $subject->render();

        $xml = new \DOMDocument();
        $xml->loadXML($out);
        $xpath = new \DOMXPath($xml);
        $headerChildren = $xpath->query('/T3RecordDocument/header/*');

        $actualOrder = [];
        foreach ($headerChildren as $node) {
            $actualOrder[] = $node->nodeName;
        }

        $expectedOrder = [
            'XMLversion',
            'charset',
            'meta',
            'static_tables',     // relStaticTables
            'excludeMap',
            'softrefCfg',
            'extensionDependencies',
            'pagetree',
            'records',
            'pid_lookup',
        ];
        self::assertSame($expectedOrder, $actualOrder);
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithComplexConfiguration(): void
    {
        $subject = $this->get(Export::class);
        $subject->setPid(1);
        $subject->setExcludeMap(['pages:2' => 1]);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setExcludeDisabledRecords(true);
        $subject->process();

        $out = $subject->render();

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-complex.xml',
            $out
        );
    }

    #[Test]
    public function exportPagesAndRelatedTtContentWithSiteConfiguration(): void
    {
        $this->get(SiteWriter::class)->write('test-site', [
            'rootPageId' => 1,
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => '/', 'flag' => 'global'],
            ],
        ]);

        $subject = $this->get(Export::class);
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setIncludeSiteConfigurations(true);
        $subject->process();

        $out = $subject->render();

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-site-config.xml',
            $out
        );
    }

    #[Test]
    public function exportDoesNotIncludeSiteConfigurationForNonExportedRootPage(): void
    {
        $this->get(SiteWriter::class)->write('other-site', [
            'rootPageId' => 99,
            'base' => 'https://other.example.com/',
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => '/', 'flag' => 'global'],
            ],
        ]);

        $subject = $this->get(Export::class);
        $subject->setPid(1);
        $subject->setLevels(0);
        $subject->setTables(['pages']);
        $subject->setIncludeSiteConfigurations(true);
        $subject->process();

        $out = $subject->render();

        self::assertStringNotContainsString('<site_configurations', $out);
    }
}
