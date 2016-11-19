<?php
namespace TYPO3\CMS\Form\Tests\Unit\Hooks;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Hooks\DataStructureIdentifierHook;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Test case
 */
class DataStructureIdentifierHookTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Set up
     */
    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessReturnsIdentifierForNotMatchingScenario()
    {
        $givenIdentifier = ['aKey' => 'aValue'];
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [], 'aTable', 'aField', [], $givenIdentifier
        );
        $this->assertEquals($givenIdentifier, $result);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessAddDefaultValuesForNewRecord()
    {
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [], 'tt_content', 'pi_flexform', ['CType' => 'form_formframework'], []
        );
        $this->assertEquals(
            ['ext-form-persistenceIdentifier' => '', 'ext-form-overrideFinishers' => false],
            $result
        );
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessAddsGivenPersistenceIdentifier()
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
            'ext-form-overrideFinishers' => false,
        ];
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [], 'tt_content', 'pi_flexform', $row, $incomingIdentifier
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessAddsOverrideFinisherValue()
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
            'ext-form-overrideFinishers' => true,
        ];
        $result = (new DataStructureIdentifierHook())->getDataStructureIdentifierPostProcess(
            [], 'tt_content', 'pi_flexform', $row, []
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierPostProcessReturnsDataStructureUnchanged()
    {
        $dataStructure = ['foo' => 'bar'];
        $expected = $dataStructure;
        $result = (new DataStructureIdentifierHook())->parseDataStructureByIdentifierPostProcess(
            $dataStructure, []
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierPostProcessAddsExistingFormItems()
    {
        $objectMangerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectMangerProphecy->reveal());
        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManager::class);
        $objectMangerProphecy->get(FormPersistenceManagerInterface::class)
            ->willReturn($formPersistenceManagerProphecy->reveal());

        $existingForms = [
            [
                'persistenceIdentifier' => 'hugo1',
                'name' => 'myHugo1',
            ],
            [
                'persistenceIdentifier' => 'hugo2',
                'name' => 'myHugo2',
            ]
        ];
        $formPersistenceManagerProphecy->listForms()->shouldBeCalled()->willReturn($existingForms);

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
                                            1 => [
                                                0 => 'myHugo1 (hugo1)',
                                                1 => 'hugo1',
                                            ],
                                            2 => [
                                                0 => 'myHugo2 (hugo2)',
                                                1 => 'hugo2',
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

        $result = (new DataStructureIdentifierHook())->parseDataStructureByIdentifierPostProcess(
            $incomingDataStructure,
            ['ext-form-persistenceIdentifier' => '']
        );

        $this->assertEquals($expected, $result);
    }
}
