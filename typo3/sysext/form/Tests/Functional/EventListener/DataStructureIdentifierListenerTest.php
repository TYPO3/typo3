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

namespace TYPO3\CMS\Form\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\EventListener\DataStructureIdentifierListener;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataStructureIdentifierListenerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
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
        $subject = new DataStructureIdentifierListener(
            $this->createMock(FormPersistenceManagerInterface::class),
            $this->createMock(ConfigurationService::class),
            $this->createMock(TranslationService::class),
            $this->createMock(FlashMessageService::class),
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            $this->createMock(ExtFormConfigurationManagerInterface::class),
        );
        $subject->modifyDataStructureIdentifier($event);
        self::assertSame($givenIdentifier, $event->getIdentifier());
    }

    #[Test]
    public function modifyIdentifiersAddDefaultValuesForNewRecord(): void
    {
        $event = new AfterFlexFormDataStructureIdentifierInitializedEvent(
            [],
            'tt_content',
            'pi_flexform',
            ['CType' => 'form_formframework'],
            [],
        );
        $subject = new DataStructureIdentifierListener(
            $this->createMock(FormPersistenceManagerInterface::class),
            $this->createMock(ConfigurationService::class),
            $this->createMock(TranslationService::class),
            $this->createMock(FlashMessageService::class),
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            $this->createMock(ExtFormConfigurationManagerInterface::class),
        );
        $subject->modifyDataStructureIdentifier($event);
        self::assertEquals(
            ['ext-form-persistenceIdentifier' => '', 'ext-form-overrideFinishers' => ''],
            $event->getIdentifier(),
        );
    }

    #[Test]
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
        $subject = new DataStructureIdentifierListener(
            $this->createMock(FormPersistenceManagerInterface::class),
            $this->createMock(ConfigurationService::class),
            $this->createMock(TranslationService::class),
            $this->createMock(FlashMessageService::class),
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            $this->createMock(ExtFormConfigurationManagerInterface::class),
        );
        $subject->modifyDataStructureIdentifier($event);
        self::assertEquals($expected, $event->getIdentifier());
    }

    #[Test]
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
        $subject = new DataStructureIdentifierListener(
            $this->createMock(FormPersistenceManagerInterface::class),
            $this->createMock(ConfigurationService::class),
            $this->createMock(TranslationService::class),
            $this->createMock(FlashMessageService::class),
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            $this->createMock(ExtFormConfigurationManagerInterface::class),
        );
        $subject->modifyDataStructureIdentifier($event);
        self::assertEquals($expected, $event->getIdentifier());
    }

    #[Test]
    public function modifyDataStructureReturnsDataStructureUnchanged(): void
    {
        $dataStructure = ['foo' => 'bar'];
        $expected = $dataStructure;
        $event = new AfterFlexFormDataStructureParsedEvent(
            $dataStructure,
            [],
        );
        $subject = new DataStructureIdentifierListener(
            $this->createMock(FormPersistenceManagerInterface::class),
            $this->createMock(ConfigurationService::class),
            $this->createMock(TranslationService::class),
            $this->createMock(FlashMessageService::class),
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            $this->createMock(ExtFormConfigurationManagerInterface::class),
        );
        $subject->modifyDataStructure($event);
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

    #[DataProvider('modifyDataStructureDataProvider')]
    #[Test]
    public function modifyDataStructureAddsExistingFormItems(array $formDefinition, array $expectedItem): void
    {
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
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $formPersistenceManagerMock->expects(self::atLeastOnce())->method('listForms')->willReturn([$formDefinition]);
        $subject = new DataStructureIdentifierListener(
            $formPersistenceManagerMock,
            $this->createMock(ConfigurationService::class),
            $this->createMock(TranslationService::class),
            $this->createMock(FlashMessageService::class),
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            $this->createMock(ExtFormConfigurationManagerInterface::class),
        );
        $subject->modifyDataStructure($event);
        self::assertEquals($expected, $event->getDataStructure());
    }
}
