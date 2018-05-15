<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Authentication;

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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserAuthenticationTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function getTSConfigDataProvider(): array
    {
        $completeConfiguration = [
            'value' => 'oneValue',
            'value.' => ['oneProperty' => 'oneValue'],
            'permissions.' => [
                'file.' => [
                    'default.' => ['readAction' => '1'],
                    '1.' => ['writeAction' => '1'],
                    '0.' => ['readAction' => '0'],
                ],
            ]
        ];

        return [
            'single level string' => [
                $completeConfiguration,
                'permissions',
                [
                    'value' => null,
                    'properties' =>
                    [
                        'file.' => [
                            'default.' => ['readAction' => '1'],
                            '1.' => ['writeAction' => '1'],
                            '0.' => ['readAction' => '0'],
                        ],
                    ],
                ],
            ],
            'two levels string' => [
                $completeConfiguration,
                'permissions.file',
                [
                    'value' => null,
                    'properties' =>
                    [
                        'default.' => ['readAction' => '1'],
                        '1.' => ['writeAction' => '1'],
                        '0.' => ['readAction' => '0'],
                    ],
                ],
            ],
            'three levels string' => [
                $completeConfiguration,
                'permissions.file.default',
                [
                    'value' => null,
                    'properties' =>
                    ['readAction' => '1'],
                ],
            ],
            'three levels string with integer property' => [
                $completeConfiguration,
                'permissions.file.1',
                [
                    'value' => null,
                    'properties' => ['writeAction' => '1'],
                ],
            ],
            'three levels string with integer zero property' => [
                $completeConfiguration,
                'permissions.file.0',
                [
                    'value' => null,
                    'properties' => ['readAction' => '0'],
                ],
            ],
            'four levels string with integer zero property, value, no properties' => [
                $completeConfiguration,
                'permissions.file.0.readAction',
                [
                    'value' => '0',
                    'properties' => null,
                ],
            ],
            'four levels string with integer property, value, no properties' => [
                $completeConfiguration,
                'permissions.file.1.writeAction',
                [
                    'value' => '1',
                    'properties' => null,
                ],
            ],
            'one level, not existent string' => [
                $completeConfiguration,
                'foo',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'two level, not existent string' => [
                $completeConfiguration,
                'foo.bar',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'two level, where second level does not exist' => [
                $completeConfiguration,
                'permissions.bar',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'three level, where third level does not exist' => [
                $completeConfiguration,
                'permissions.file.foo',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'three level, where second and third level does not exist' => [
                $completeConfiguration,
                'permissions.foo.bar',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'value and properties' => [
                $completeConfiguration,
                'value',
                [
                    'value' => 'oneValue',
                    'properties' => ['oneProperty' => 'oneValue'],
                ],
            ],
        ];
    }

    /**
     * @param array $completeConfiguration
     * @param string $objectString
     * @param array $expectedConfiguration
     * @dataProvider getTSConfigDataProvider
     * @test
     */
    public function getTSConfigReturnsCorrectArrayForGivenObjectString(array $completeConfiguration, $objectString, array $expectedConfiguration): void
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getAccessibleMock(BackendUserAuthentication::class, ['dummy'], [], '', false);
        $subject->setLogger(new NullLogger());
        $subject->_set('userTS', $completeConfiguration);

        $actualConfiguration = $subject->getTSConfig($objectString);
        $this->assertSame($expectedConfiguration, $actualConfiguration);
    }
}
