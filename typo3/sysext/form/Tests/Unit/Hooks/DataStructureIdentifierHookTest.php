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

namespace TYPO3\CMS\Form\Tests\Unit\Hooks;

use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Hooks\DataStructureIdentifierHook;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DataStructureIdentifierHookTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera())->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessReturnsIdentifierForNotMatchingScenario(): void
    {
        $givenIdentifier = ['aKey' => 'aValue'];
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [],
            'aTable',
            'aField',
            [],
            $givenIdentifier
        );
        self::assertEquals($givenIdentifier, $result);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessAddDefaultValuesForNewRecord(): void
    {
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [],
            'tt_content',
            'pi_flexform',
            ['CType' => 'form_formframework'],
            []
        );
        self::assertEquals(
            ['ext-form-persistenceIdentifier' => '', 'ext-form-overrideFinishers' => ''],
            $result
        );
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessAddsGivenPersistenceIdentifier(): void
    {
        $row = [
            'CType' => 'form_formframework',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
                <T3FlexForms>
                    <data>
                        <sheet index="sDEF">
                            <language index="lDEF">
                                <field index="settings.persistenceIdentifier">
                                    <value index="vDEF">1:user_upload/karl.yml</value>
                                </field>
                            </language>
                        </sheet>
                    </data>
                </T3FlexForms>
            ',
        ];
        $incomingIdentifier = [
            'aKey' => 'aValue',
        ];
        $expected = [
            'aKey' => 'aValue',
            'ext-form-persistenceIdentifier' => '1:user_upload/karl.yml',
            'ext-form-overrideFinishers' => '',
        ];
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [],
            'tt_content',
            'pi_flexform',
            $row,
            $incomingIdentifier
        );
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessAddsOverrideFinisherValue(): void
    {
        $row = [
            'CType' => 'form_formframework',
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
                <T3FlexForms>
                    <data>
                        <sheet index="sDEF">
                            <language index="lDEF">
                                <field index="settings.overrideFinishers">
                                    <value index="vDEF">1</value>
                               </field>
                            </language>
                        </sheet>
                    </data>
                </T3FlexForms>
            ',
        ];
        $expected = [
            'ext-form-persistenceIdentifier' => '',
            'ext-form-overrideFinishers' => 'enabled',
        ];
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [],
            'tt_content',
            'pi_flexform',
            $row,
            []
        );
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierPostProcessReturnsDataStructureUnchanged(): void
    {
        $dataStructure = ['foo' => 'bar'];
        $expected = $dataStructure;
        $result = (new DataStructureIdentifierHook())->parseDataStructureByIdentifierPostProcess(
            $dataStructure,
            []
        );
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider parseDataStructureByIdentifierPostProcessDataProvider
     *
     * @param array $formDefinition
     * @param array $expectedItem
     */
    public function parseDataStructureByIdentifierPostProcessAddsExistingFormItems(array $formDefinition, array $expectedItem): void
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());
        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManager::class);
        $objectManagerProphecy->get(FormPersistenceManagerInterface::class)
            ->willReturn($formPersistenceManagerProphecy->reveal());

        $formPersistenceManagerProphecy->listForms()->shouldBeCalled()->willReturn([$formDefinition]);

        $incomingDataStructure = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'el' => [
                            'settings.persistenceIdentifier' => [
                                'TCEforms' => [
                                    'config' => [
                                        'items' => [
                                            0 => [
                                                0 => 'default, no value',
                                                1 => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'el' => [
                            'settings.persistenceIdentifier' => [
                                'TCEforms' => [
                                    'config' => [
                                        'items' => [
                                            0 => [
                                                0 => 'default, no value',
                                                1 => '',
                                            ],
                                            1 => $expectedItem,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new DataStructureIdentifierHook())->parseDataStructureByIdentifierPostProcess(
            $incomingDataStructure,
            ['ext-form-persistenceIdentifier' => '']
        );

        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function parseDataStructureByIdentifierPostProcessDataProvider(): array
    {
        return [
            'simple' => [
                [
                    'persistenceIdentifier' => 'hugo1',
                    'name' => 'myHugo1',
                    'location' => 'extension',
                ],
                [
                    'myHugo1 (hugo1)',
                    'hugo1',
                    'content-form',
                ],
            ],
            'invalid' => [
                [
                    'persistenceIdentifier' => 'Error.yaml',
                    'label' => 'Test Error Label',
                    'name' => 'Test Error Name',
                    'location' => 'extension',
                    'invalid' => true,
                ],
                [
                    'Test Error Name (Error.yaml)',
                    'Error.yaml',
                    'overlay-missing',
                ],
            ],
        ];
    }
}
