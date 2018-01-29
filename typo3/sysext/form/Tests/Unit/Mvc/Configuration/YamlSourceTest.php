<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;

/**
 * Test case
 */
class YamlSourceTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function loadThrowsExceptionIfFileToLoadNotExists()
    {
        $this->expectException(NoSuchFileException::class);
        $this->expectExceptionCode(1471473378);

        $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
            'dummy',
        ], [], '', false);

        $input = [
            'EXT:form/Resources/Forms/_example.yaml'
        ];

        $mockYamlSource->_call('load', $input);
    }

    /**
     * @test
     */
    public function loadThrowsExceptionIfFileToLoadIsNotValidYamlUseSymfonyParser()
    {
        if (!extension_loaded('yaml')) {
            $this->expectException(ParseErrorException::class);
            $this->expectExceptionCode(1480195405);

            $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
                'dummy',
            ], [], '', false);

            $input = [
                'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Invalid.yaml'
            ];

            $mockYamlSource->_call('load', $input);
        }
    }

    /**
     * @test
     */
    public function loadThrowsExceptionIfFileToLoadIsNotValidYamlUsePhpExtensionParser()
    {
        if (extension_loaded('yaml')) {
            $this->expectException(ParseErrorException::class);
            $this->expectExceptionCode(1391894094);

            $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
                'dummy',
            ], [], '', false);

            $input = [
                'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Invalid.yaml'
            ];

            $mockYamlSource->_call('load', $input);
        }
    }

    /**
     * @test
     */
    public function getHeaderFromFileReturnsHeaderPart()
    {
        $mockYamlSource = $this->getAccessibleMock(YamlSource::class, [
            'dummy',
        ], [], '', false);

        $input = GeneralUtility::getFileAbsFileName('EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/Header.yaml');
        $expected =
'# Header 1
# Header 2
';

        $this->assertSame($expected, $mockYamlSource->_call('getHeaderFromFile', $input));
    }

    /**
     * @test
     */
    public function loadOverruleNonArrayValuesOverArrayValues()
    {
        $mockYamlSource = $this->getAccessibleMock(YamlSource::class, ['dummy'], [], '', false);

        $input = [
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/OverruleNonArrayValuesOverArrayValues1.yaml',
            'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/OverruleNonArrayValuesOverArrayValues2.yaml'
        ];

        $expected = [
            'Form' => [
                'klaus01' => null,
                'key03' => 'value2',
            ],
        ];

        $this->assertSame($expected, $mockYamlSource->_call('load', $input));
    }

    /**
     * @return array
     */
    public function mergeRecursiveWithOverruleCalculatesExpectedResultDataProvider()
    {
        return [
            'Override array can reset string to array' => [
                [
                    'first' => [
                        'second' => 'foo',
                    ],
                ],
                [
                    'first' => [
                        'second' => ['third' => 'bar'],
                    ],
                ],
                [
                    'first' => [
                        'second' => ['third' => 'bar'],
                    ],
                ],
            ],
            'Override array does reset array to string' => [
                [
                    'first' => [],
                ],
                [
                    'first' => 'foo',
                ],
                [
                    'first' => 'foo', // Note that ArrayUtility::mergeRecursiveWithOverrule returns [] here
                ],
            ],
            'Override array does override null with string' => [
                [
                    'first' => null,
                ],
                [
                    'first' => 'foo',
                ],
                [
                    'first' => 'foo',
                ],
            ],
            'Override array does override null with empty string' => [
                [
                    'first' => null,
                ],
                [
                    'first' => '',
                ],
                [
                    'first' => '',
                ],
            ],
            'Override array does override string with null' => [
                [
                    'first' => 'foo',
                ],
                [
                    'first' => null,
                ],
                [
                    'first' => null, // Note that ArrayUtility::mergeRecursiveWithOverrule returns 'foo' here
                ],
            ],
            'Override array does override null with null' => [
                [
                    'first' => null,
                ],
                [
                    'first' => null,
                ],
                [
                    'first' => null, // Note that ArrayUtility::mergeRecursiveWithOverrule returns '' here
                ],
            ],
            'Override can add keys' => [
                [
                    'first' => 'foo',
                ],
                [
                    'second' => 'bar',
                ],
                [
                    'first' => 'foo',
                    'second' => 'bar',
                ],
            ],
        ];
    }

    /**
     * Note the data provider is similar to the data provider for ArrayUtility::mergeRecursiveWithOverrule()
     *
     * @test
     * @dataProvider mergeRecursiveWithOverruleCalculatesExpectedResultDataProvider
     * @param array $input1 Input 1
     * @param array $input2 Input 2
     * @param array $expected expected array
     */
    public function mergeRecursiveWithOverruleCalculatesExpectedResult($input1, $input2, $expected)
    {
        $mockYamlSource = $this->getAccessibleMock(YamlSource::class, ['dummy'], [], '', false);
        $mockYamlSource->_callRef('mergeRecursiveWithOverrule', $input1, $input2);
        $this->assertSame($expected, $input1);
    }
}
