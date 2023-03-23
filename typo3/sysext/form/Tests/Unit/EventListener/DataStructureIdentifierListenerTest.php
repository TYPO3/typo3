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

namespace TYPO3\CMS\Form\Tests\Unit\EventListener;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\EventListener\DataStructureIdentifierListener;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DataStructureIdentifierListenerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public function setUp(): void
    {
        parent::setUp();
        $cacheManager = new CacheManager();
        $cacheManager->registerCache(new NullFrontend('runtime'));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
    }

    /**
     * @test
     */
    public function modifyIdentifiersReturnsIdentifierForNotMatchingScenario(): void
    {
        $givenIdentifier = ['aKey' => 'aValue'];

        $event = new AfterFlexFormDataStructureIdentifierInitializedEvent(
            [],
            'aTable',
            'aField',
            [],
            $givenIdentifier,
        );

        (new DataStructureIdentifierListener())->modifyDataStructureIdentifier($event);

        self::assertSame($givenIdentifier, $event->getIdentifier());
    }

    /**
     * @test
     */
    public function modifyIdentifiersAddDefaultValuesForNewRecord(): void
    {
        $event = new AfterFlexFormDataStructureIdentifierInitializedEvent(
            [],
            'tt_content',
            'pi_flexform',
            ['CType' => 'form_formframework'],
            [],
        );

        (new DataStructureIdentifierListener())->modifyDataStructureIdentifier($event);

        self::assertEquals(
            ['ext-form-persistenceIdentifier' => '', 'ext-form-overrideFinishers' => ''],
            $event->getIdentifier(),
        );
    }

    /**
     * @test
     */
    public function modifyIdentifiersAddsGivenPersistenceIdentifier(): void
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

        $event = new AfterFlexFormDataStructureIdentifierInitializedEvent(
            [],
            'tt_content',
            'pi_flexform',
            $row,
            $incomingIdentifier,
        );

        (new DataStructureIdentifierListener())->modifyDataStructureIdentifier($event);

        self::assertEquals($expected, $event->getIdentifier());
    }

    /**
     * @test
     */
    public function modifyIdentifiersAddsOverrideFinisherValue(): void
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

        $event = new AfterFlexFormDataStructureIdentifierInitializedEvent(
            [],
            'tt_content',
            'pi_flexform',
            $row,
            [],
        );

        (new DataStructureIdentifierListener())->modifyDataStructureIdentifier($event);

        self::assertEquals($expected, $event->getIdentifier());
    }

    /**
     * @test
     */
    public function modifyDataStructureReturnsDataStructureUnchanged(): void
    {
        $dataStructure = ['foo' => 'bar'];
        $expected = $dataStructure;

        $event = new AfterFlexFormDataStructureParsedEvent(
            $dataStructure,
            [],
        );

        (new DataStructureIdentifierListener())->modifyDataStructure($event);

        self::assertEquals($expected, $event->getDataStructure());
    }

    /**
     * @test
     * @dataProvider modifyDataStructureDataProvider
     */
    public function modifyDataStructureAddsExistingFormItems(array $formDefinition, array $expectedItem): void
    {
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $formPersistenceManagerMock->expects(self::atLeastOnce())->method('listForms')->willReturn([$formDefinition]);
        GeneralUtility::addInstance(FormPersistenceManagerInterface::class, $formPersistenceManagerMock);

        $incomingDataStructure = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'el' => [
                            'settings.persistenceIdentifier' => [
                                'config' => [
                                    'items' => [
                                        0 => [
                                            'label' => 'default, no value',
                                            'value' => '',
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
                                'config' => [
                                    'items' => [
                                        0 => [
                                            'label' => 'default, no value',
                                            'value' => '',
                                        ],
                                        1 => $expectedItem,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $event = new AfterFlexFormDataStructureParsedEvent(
            $incomingDataStructure,
            ['ext-form-persistenceIdentifier' => ''],
        );

        (new DataStructureIdentifierListener())->modifyDataStructure($event);

        self::assertEquals($expected, $event->getDataStructure());
    }

    public static function modifyDataStructureDataProvider(): array
    {
        return [
            'simple' => [
                [
                    'persistenceIdentifier' => 'hugo1',
                    'name' => 'myHugo1',
                    'location' => 'extension',
                ],
                [
                    'label' => 'myHugo1 (hugo1)',
                    'value' => 'hugo1',
                    'icon' => 'content-form',
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
                    'label' => 'Test Error Name (Error.yaml)',
                    'value' => 'Error.yaml',
                    'icon' => 'overlay-missing',
                ],
            ],
        ];
    }
}
