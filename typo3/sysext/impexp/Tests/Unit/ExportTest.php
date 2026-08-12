<?php

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

namespace TYPO3\CMS\Impexp\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Impexp\Export;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExportTest extends UnitTestCase
{
    public static function setExportFileNameSanitizesFileNameProvider(): array
    {
        return [
            [
                'fileName' => 'my-export-file_20201012 äöüß!"§$%&/()²³¼½¬{[]};,:µ<>|.1',
                'expected' => 'my-export-file_20201012.1',
            ],
        ];
    }

    #[DataProvider('setExportFileNameSanitizesFileNameProvider')]
    #[Test]
    public function setExportFileNameSanitizesFileName(string $fileName, string $expected): void
    {
        $subject = $this->createSubject();
        $subject->setExportFileName($fileName);
        $actual = $subject->getExportFileName();
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function getOrGenerateExportFileNameWithFileExtensionConsidersPidAndLevels(): void
    {
        $subject = $this->createSubject();
        $subject->setPid(1);
        $subject->setLevels(2);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_tree_PID1_L2_$patternDateTime.xml/", $subject->getOrGenerateExportFileNameWithFileExtension());
    }

    #[Test]
    public function getOrGenerateExportFileNameWithFileExtensionConsidersRecords(): void
    {
        $subject = $this->createSubject();
        $subject->setRecord(['page:1', 'tt_content:1']);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_recs_page_1-tt_conte_$patternDateTime.xml/", $subject->getOrGenerateExportFileNameWithFileExtension());
    }

    #[Test]
    public function getOrGenerateExportFileNameWithFileExtensionConsidersLists(): void
    {
        $subject = $this->createSubject();
        $subject->setList(['sys_news:0', 'news:12']);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_list_sys_news_0-news_$patternDateTime.xml/", $subject->getOrGenerateExportFileNameWithFileExtension());
    }

    public static function setExportFileTypeSucceedsWithSupportedFileTypeProvider(): array
    {
        return [
            ['fileType' => Export::FILETYPE_XML],
            ['fileType' => Export::FILETYPE_T3D],
            ['fileType' => Export::FILETYPE_T3DZ],
        ];
    }

    #[DataProvider('setExportFileTypeSucceedsWithSupportedFileTypeProvider')]
    #[Test]
    public function setExportFileTypeSucceedsWithSupportedFileType(string $fileType): void
    {
        $subject = $this->createSubject();
        $subject->setExportFileType($fileType);
        self::assertEquals($fileType, $subject->getExportFileType());
    }

    #[Test]
    public function setExportFileTypeFailsWithUnsupportedFileType(): void
    {
        $this->expectException(\Exception::class);
        $subject = $this->createSubject();
        $subject->setExportFileType('json');
    }

    private function createSubject(): Export
    {
        return new Export(
            self::createStub(ConnectionPool::class),
            self::createStub(Locales::class),
            new Typo3Version(),
            self::createStub(ReferenceIndex::class),
            self::createStub(SiteConfiguration::class),
            new Context(),
        );
    }
}
