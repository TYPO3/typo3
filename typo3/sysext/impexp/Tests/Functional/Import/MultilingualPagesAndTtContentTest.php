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

namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class MultilingualPagesAndTtContentTest extends AbstractImportExportTestCase
{
    private const FIXTURE = 'EXT:impexp/Tests/Functional/Fixtures/XmlExports/pages-and-ttcontent-multilingual.xml';

    #[Test]
    public function importPlacesPageTranslationsOnTheSamePageAsTheirDefaultLanguagePage(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile(self::FIXTURE);
        $subject->importData();

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importMultilingualPagesAndTtContent.csv');
    }

    #[Test]
    public function importKeepsFileReferencesOfATranslationInAnExcludedField(): void
    {
        // Each translation mirrors the image of its language parent, and one of them has none.
        // The import restores the references and their translation pointers in one go, which
        // must neither delete the reference of a translation nor add another one to it.
        $GLOBALS['TCA']['tt_content']['columns']['image']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-multilingual-with-image.xml');
        $subject->importData();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importMultilingualTtContentWithImageInExcludedField.csv');
    }
}
