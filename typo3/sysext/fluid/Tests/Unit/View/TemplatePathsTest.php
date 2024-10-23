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

namespace TYPO3\CMS\Fluid\Tests\Unit\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TemplatePathsTest extends UnitTestCase
{
    public static function getPathSetterMethodTestValues(): array
    {
        $generator = static function ($method, $indexType = 'numeric') {
            switch ($indexType) {
                default:
                case 'numeric':
                    $set = [
                        20 => 'bar',
                        0 => 'baz',
                        100 => 'boz',
                        10 => 'foo',
                    ];
                    $expected = [
                        0 => 'baz',
                        10 => 'foo',
                        20 => 'bar',
                        100 => 'boz',
                    ];
                    break;
                case 'alpha':
                    $set = [
                        'bcd' => 'bar',
                        'abc' => 'foo',
                    ];
                    $expected = [
                        'bcd' => 'bar',
                        'abc' => 'foo',
                    ];
                    break;
                case 'alphanumeric':
                    $set = [
                        0 => 'baz',
                        'bcd' => 'bar',
                        15 => 'boz',
                        'abc' => 'foo',
                    ];
                    $expected = [
                        0 => 'baz',
                        'bcd' => 'bar',
                        15 => 'boz',
                        'abc' => 'foo',
                    ];
                    break;
            }
            return [$method, $set, $expected];
        };
        return [
            'simple numeric index, template' => $generator('templateRootPaths', 'numeric'),
            'alpha index, template' => $generator('templateRootPaths', 'alpha'),
            'alpha-numeric index, template' => $generator('templateRootPaths', 'alphanumeric'),
            'simple numeric index, partial' => $generator('partialRootPaths', 'numeric'),
            'alpha index, partial' => $generator('partialRootPaths', 'alpha'),
            'alpha-numeric index, partial' => $generator('partialRootPaths', 'alphanumeric'),
            'simple numeric index, layout' => $generator('layoutRootPaths', 'numeric'),
            'alpha index, layout' => $generator('layoutRootPaths', 'alpha'),
            'alpha-numeric index, layout' => $generator('layoutRootPaths', 'alphanumeric'),
        ];
    }

    #[DataProvider('getPathSetterMethodTestValues')]
    #[Test]
    public function pathSetterMethodSortsPathsByKeyDescending(string $method, array $paths, array $expected): void
    {
        $setter = 'set' . ucfirst($method);
        $getter = 'get' . ucfirst($method);
        $subject = $this->getMockBuilder(TemplatePaths::class)->onlyMethods(['sanitizePath'])->getMock();
        $subject->method('sanitizePath')->willReturnArgument(0);
        $subject->$setter($paths);
        self::assertEquals($expected, $subject->$getter());
    }
}
