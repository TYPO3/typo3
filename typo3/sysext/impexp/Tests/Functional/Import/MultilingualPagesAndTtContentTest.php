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
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class MultilingualPagesAndTtContentTest extends AbstractImportExportTestCase
{
    private const string FIXTURE = 'EXT:impexp/Tests/Functional/Fixtures/XmlExports/pages-and-ttcontent-multilingual.xml';

    #[Test]
    public function importPlacesPageTranslationsOnTheSamePageAsTheirDefaultLanguagePage(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile(self::FIXTURE);
        $subject->importData();

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importMultilingualPagesAndTtContent.csv');
    }
}
