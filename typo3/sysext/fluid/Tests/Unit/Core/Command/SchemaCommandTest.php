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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Command\SchemaCommand;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Schema\ViewHelperMetadata;

final class SchemaCommandTest extends UnitTestCase
{
    public static function combineViewHelperNamespacesDataProvider(): array
    {
        $testViewHelper = new ViewHelperMetadata(
            'MyVendor\\MyPackage\\ViewHelpers\\TestViewHelper',
            'MyVendor\\MyPackage\\ViewHelpers',
            'TestViewHelper',
            'test',
            '',
            'http://typo3.org/ns/MyVendor/MyPackage/ViewHelpers',
            [],
            [],
            false,
        );
        $fooViewHelper = new ViewHelperMetadata(
            'MyVendor\\MyPackage\\ViewHelpers\\Sub\\FooViewHelper',
            'MyVendor\\MyPackage\\ViewHelpers',
            'Sub\\FooViewHelper',
            'sub.foo',
            '',
            'http://typo3.org/ns/MyVendor/MyPackage/ViewHelpers',
            [],
            [],
            false,
        );
        $testComponent = new ViewHelperMetadata(
            null,
            'MyVendor\\MyPackage\\Components',
            null,
            'test',
            '',
            'http://typo3.org/ns/MyVendor/MyPackage/Components',
            [],
            [],
            false,
        );
        $barComponent = new ViewHelperMetadata(
            null,
            'MyVendor\\MyPackage\\Components',
            null,
            'sub.bar',
            '',
            'http://typo3.org/ns/MyVendor/MyPackage/Components',
            [],
            [],
            false,
        );
        return [
            'viewhelpers and components, no merging' => [
                [$testViewHelper, $fooViewHelper, $testComponent, $barComponent],
                [],
                [
                    'http://typo3.org/ns/MyVendor/MyPackage/ViewHelpers' => [$testViewHelper, $fooViewHelper],
                    'http://typo3.org/ns/MyVendor/MyPackage/Components' => [$testComponent, $barComponent],
                ],
            ],
            'viewhelpers and components, no merging, namespace with 1 entry' => [
                [$testViewHelper, $fooViewHelper, $testComponent, $barComponent],
                ['my' => ['MyVendor\\MyPackage\\ViewHelpers']],
                [
                    'http://typo3.org/ns/MyVendor/MyPackage/ViewHelpers' => [$testViewHelper, $fooViewHelper],
                    'http://typo3.org/ns/MyVendor/MyPackage/Components' => [$testComponent, $barComponent],
                ],
            ],
            'override viewhelper with component' => [
                [$testViewHelper, $fooViewHelper, $testComponent, $barComponent],
                ['my' => ['MyVendor\\MyPackage\\ViewHelpers', 'MyVendor\\MyPackage\\Components']],
                [
                    'http://typo3.org/ns/MyVendor/MyPackage/ViewHelpers' => [$testViewHelper, $fooViewHelper],
                    'http://typo3.org/ns/MyVendor/MyPackage/Components' => [$testComponent, $fooViewHelper, $barComponent],
                ],
            ],
            'override component with viewhelper' => [
                [$testViewHelper, $fooViewHelper, $testComponent, $barComponent],
                ['my' => ['MyVendor\\MyPackage\\Components', 'MyVendor\\MyPackage\\ViewHelpers']],
                [
                    'http://typo3.org/ns/MyVendor/MyPackage/ViewHelpers' => [$testViewHelper, $barComponent, $fooViewHelper],
                    'http://typo3.org/ns/MyVendor/MyPackage/Components' => [$testComponent, $barComponent],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('combineViewHelperNamespacesDataProvider')]
    public function combineViewHelperNamespaces(array $viewHelpers, array $globalNamespaces, array $expectedViewHelperNamespaces): void
    {
        $reflectedMethod = new \ReflectionMethod(SchemaCommand::class, 'combineViewHelperNamespaces');
        self::assertSame($expectedViewHelperNamespaces, $reflectedMethod->invoke(null, $viewHelpers, $globalNamespaces));
    }
}
