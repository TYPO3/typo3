<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\View;

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

use TYPO3\CMS\Fluid\View\TemplatePaths;

/**
 * Test case
 */
class TemplatePathsTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @return array
     */
    public function getPathSetterMethodTestValues()
    {
        $generator = function ($method, $indexType = 'numeric') {
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
            'simple numeric index, template' => $generator(TemplatePaths::CONFIG_TEMPLATEROOTPATHS, 'numeric'),
            'alpha index, template' => $generator(TemplatePaths::CONFIG_TEMPLATEROOTPATHS, 'alpha'),
            'alpha-numeric index, template' => $generator(TemplatePaths::CONFIG_TEMPLATEROOTPATHS, 'alphanumeric'),
            'simple numeric index, partial' => $generator(TemplatePaths::CONFIG_PARTIALROOTPATHS, 'numeric'),
            'alpha index, partial' => $generator(TemplatePaths::CONFIG_PARTIALROOTPATHS, 'alpha'),
            'alpha-numeric index, partial' => $generator(TemplatePaths::CONFIG_PARTIALROOTPATHS, 'alphanumeric'),
            'simple numeric index, layout' => $generator(TemplatePaths::CONFIG_LAYOUTROOTPATHS, 'numeric'),
            'alpha index, layout' => $generator(TemplatePaths::CONFIG_LAYOUTROOTPATHS, 'alpha'),
            'alpha-numeric index, layout' => $generator(TemplatePaths::CONFIG_LAYOUTROOTPATHS, 'alphanumeric'),
        ];
    }

    /**
     * @test
     * @dataProvider getPathSetterMethodTestValues
     * @param string $method
     * @param array $paths
     * @param array $expected
     */
    public function pathSetterMethodSortsPathsByKeyDescending($method, array $paths, array $expected)
    {
        $setter = 'set' . ucfirst($method);
        $subject = $this->getMockBuilder(TemplatePaths::class)->setMethods(['sanitizePath'])->getMock();
        $subject->expects($this->any())->method('sanitizePath')->willReturnArgument(0);
        $subject->$setter($paths);
        $this->assertAttributeSame($expected, $method, $subject);
    }
}
