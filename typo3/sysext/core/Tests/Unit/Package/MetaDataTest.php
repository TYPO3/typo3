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

namespace TYPO3\CMS\Core\Tests\Unit\Package;

use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MetaDataTest extends UnitTestCase
{
    public function typeIsCorrectlyResolvedDataProvider(): \Generator
    {
        yield 'framework type is set' => [
            'typo3-cms-framework',
            true,
            true,
        ];

        yield 'extension type is set' => [
            'typo3-cms-extension',
            true,
            false,
        ];

        yield 'no type is set' => [
            null,
            false,
            false,
        ];

        yield 'other type is set' => [
            'other',
            false,
            false,
        ];
    }

    /**
     * @test
     * @dataProvider typeIsCorrectlyResolvedDataProvider
     */
    public function typeIsCorrectlyResolved(?string $type, bool $isExtension, bool $isFramework): void
    {
        $metaData = new MetaData('foo');
        $metaData->setPackageType($type);
        self::assertSame($isExtension, $metaData->isExtensionType());
        self::assertSame($isFramework, $metaData->isFrameworkType());
    }
}
