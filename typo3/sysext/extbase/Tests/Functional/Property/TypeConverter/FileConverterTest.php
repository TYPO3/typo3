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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FileConverterTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function convertReturnsFileObject()
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['admin' => true];

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.xml');

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        /** @var File $file */
        $file = $propertyMapper->convert(
            1,
            File::class
        );

        self::assertInstanceOf(File::class, $file);
        self::assertSame(1, $file->getOriginalResource()->getUid());
    }
}
