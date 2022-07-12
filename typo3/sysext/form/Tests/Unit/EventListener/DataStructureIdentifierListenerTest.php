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

use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\EventListener\DataStructureIdentifierListener;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DataStructureIdentifierListenerTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected bool $resetSingletonInstances = true;

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
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
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
     *
     * @param array $formDefinition
     * @param array $expectedItem
     */
    public function modifyDataStructureAddsExistingFormItems(array $formDefinition, array $expectedItem): void
    {
        $formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManagerInterface::class);
        GeneralUtility::addInstance(FormPersistenceManagerInterface::class, $formPersistenceManagerProphecy->reveal());

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

        $event = new AfterFlexFormDataStructureParsedEvent(
            $incomingDataStructure,
            ['ext-form-persistenceIdentifier' => ''],
        );

        (new DataStructureIdentifierListener())->modifyDataStructure($event);

        self::assertEquals($expected, $event->getDataStructure());
    }

    /**
     * @return array
     */
    public function modifyDataStructureDataProvider(): array
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
