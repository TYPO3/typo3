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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Import;

final class ImportExportTest extends AbstractImportExportTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mm',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnsymmetric',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnattributeinline',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnattributesimple',
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    #[Test]
    public function importExportPingPongSucceeds(): void
    {
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/irre-records.xml');
        $import->setForceAllUids(true);
        $import->importData();

        $export = $this->get(Export::class);
        $export->setPid(1);
        $export->setLevels(Export::LEVELS_INFINITE);
        $export->setTables(['_ALL']);
        $export->setRelOnlyTables(['_ALL']);
        $export->process();
        $actual = $export->render();

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/Fixtures/XmlImports/irre-records.xml',
            $actual
        );
    }
}
