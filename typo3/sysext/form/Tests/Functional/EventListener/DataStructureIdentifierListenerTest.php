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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
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

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

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
            self::createStub(FormPersistenceManagerInterface::class),
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
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
            self::createStub(FormPersistenceManagerInterface::class),
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
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
            self::createStub(FormPersistenceManagerInterface::class),
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
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
            self::createStub(FormPersistenceManagerInterface::class),
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
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
            self::createStub(FormPersistenceManagerInterface::class),
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
        );
        $subject->modifyDataStructure($event);
        self::assertEquals($expected, $event->getDataStructure());
    }

    public static function modifyDataStructureDataProvider(): array
    {
        return [
            'simple' => [
                new FormMetadata(
                    identifier: 'hugo1',
                    type: 'Form',
                    name: 'myHugo1',
                    prototypeName: 'Standard',
                    persistenceIdentifier: 'hugo1',
                    storageType: 'extension'
                ),
                [
                    'label' => 'myHugo1 (hugo1)',
                    'value' => 'hugo1',
                    'icon' => 'content-form',
                ],
            ],
            'invalid' => [
                new FormMetadata(
                    identifier: 'testError',
                    type: 'Form',
                    name: 'Test Error Name',
                    prototypeName: 'Standard',
                    persistenceIdentifier: 'Error.yaml',
                    invalid: true,
                    storageType: 'extension',
                ),
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
    public function modifyDataStructureAddsExistingFormItems(FormMetadata $formMetadata, array $expectedItem): void
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
        $formPersistenceManagerMock->expects($this->atLeastOnce())->method('listForms')->willReturn([$formMetadata]);
        $subject = new DataStructureIdentifierListener(
            $formPersistenceManagerMock,
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
        );
        $subject->modifyDataStructure($event);
        self::assertEquals($expected, $event->getDataStructure());
    }

    /**
     * Record->toArray() parses flex form data structures in the frontend as well, where
     * $GLOBALS['LANG'] only exists while a backend user is logged in.
     */
    #[Test]
    public function modifyDataStructureTranslatesInFrontendWithoutBackendUser(): void
    {
        unset($GLOBALS['LANG']);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []));

        $event = new AfterFlexFormDataStructureParsedEvent(
            [
                'sheets' => [
                    'sDEF' => ['ROOT' => ['el' => ['settings.persistenceIdentifier' => ['config' => ['items' => []]]]]],
                ],
            ],
            ['ext-form-persistenceIdentifier' => 'gone.form.yaml'],
        );
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $formPersistenceManagerMock->expects($this->atLeastOnce())->method('listForms')->willReturn([]);
        $subject = new DataStructureIdentifierListener(
            $formPersistenceManagerMock,
            self::createStub(ConfigurationService::class),
            self::createStub(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
        );

        $subject->modifyDataStructure($event);

        $items = $event->getDataStructure()['sheets']['sDEF']['ROOT']['el']['settings.persistenceIdentifier']['config']['items'];
        self::assertSame('gone.form.yaml', $items[0]['value']);
        self::assertStringContainsString('gone.form.yaml', $items[0]['label']);
    }

    /**
     * The finisher override sheets are built by FinisherOptionGenerator, which labels each
     * option with its default value. That label is translated without $GLOBALS['LANG'] as well.
     */
    #[Test]
    public function modifyDataStructureTranslatesFinisherOverridesInFrontendWithoutBackendUser(): void
    {
        unset($GLOBALS['LANG']);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []));

        $persistenceIdentifier = 'EXT:form/Tests/Functional/myForm.form.yaml';
        $event = new AfterFlexFormDataStructureParsedEvent(
            ['sheets' => ['sDEF' => ['ROOT' => ['el' => []]]]],
            [
                'ext-form-persistenceIdentifier' => $persistenceIdentifier,
                'ext-form-overrideFinishers' => 'enabled',
            ],
        );
        $formPersistenceManagerStub = self::createStub(FormPersistenceManagerInterface::class);
        $formPersistenceManagerStub->method('listForms')->willReturn([
            new FormMetadata(
                identifier: 'myForm',
                type: 'Form',
                name: 'My form',
                prototypeName: 'standard',
                persistenceIdentifier: $persistenceIdentifier,
                storageType: 'extension',
            ),
        ]);
        $formPersistenceManagerStub->method('load')->willReturn([
            'identifier' => 'myForm',
            'prototypeName' => 'standard',
            'finishers' => [
                ['identifier' => 'EmailToReceiver', 'options' => ['subject' => 'Hello']],
            ],
        ]);
        $configurationServiceStub = self::createStub(ConfigurationService::class);
        $configurationServiceStub->method('getPrototypeConfiguration')->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'label' => 'Email to receiver',
                        'elements' => [
                            'subject' => ['label' => 'Subject', 'config' => ['type' => 'input']],
                        ],
                    ],
                ],
            ],
        ]);
        $translationServiceStub = self::createStub(TranslationService::class);
        $translationServiceStub->method('translateSingleValue')->willReturnArgument(0);
        $translationServiceStub->method('translateFormEngineFinisherOptions')->willReturnArgument(0);
        $subject = new DataStructureIdentifierListener(
            $formPersistenceManagerStub,
            $configurationServiceStub,
            $translationServiceStub,
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
        );

        $subject->modifyDataStructure($event);

        $sheetIdentifier = md5($persistenceIdentifier . 'standard' . 'myForm' . 'EmailToReceiver');
        self::assertSame(
            [
                'label' => 'Subject (Default: "Hello")',
                'config' => ['type' => 'input', 'default' => 'Hello'],
            ],
            $event->getDataStructure()['sheets'][$sheetIdentifier]['ROOT']['el']['settings.finishers.EmailToReceiver.subject']
        );
    }

    #[Test]
    public function modifyDataStructureTranslatesFinisherOverrideLabelsButKeepsSelectItemValues(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $finisherConfiguration = Yaml::parseFile(
            GeneralUtility::getFileAbsFileName('EXT:form/Configuration/Form/Base/Finishers/EmailToSender.yaml')
        );
        $persistenceIdentifier = '1:/form_definitions/contact.form.yaml';
        $event = new AfterFlexFormDataStructureParsedEvent(
            ['sheets' => ['sDEF' => ['ROOT' => ['el' => []]]]],
            [
                'ext-form-persistenceIdentifier' => $persistenceIdentifier,
                'ext-form-overrideFinishers' => 'enabled',
            ],
        );
        $formPersistenceManagerStub = self::createStub(FormPersistenceManagerInterface::class);
        $formPersistenceManagerStub->method('listForms')->willReturn([
            new FormMetadata(
                identifier: 'contact',
                type: 'Form',
                name: 'Contact',
                prototypeName: 'standard',
                persistenceIdentifier: $persistenceIdentifier,
                storageType: 'filemount',
            ),
        ]);
        $formPersistenceManagerStub->method('load')->willReturn([
            'identifier' => 'contact',
            'prototypeName' => 'standard',
            'finishers' => [
                ['identifier' => 'EmailToSender'],
            ],
        ]);
        $configurationServiceStub = self::createStub(ConfigurationService::class);
        $configurationServiceStub->method('getPrototypeConfiguration')->willReturn([
            'finishersDefinition' => $finisherConfiguration['prototypes']['standard']['finishersDefinition'],
            'formEngine' => [
                'translationFiles' => [
                    10 => 'EXT:form/Resources/Private/Language/Database.xlf',
                ],
            ],
        ]);
        $subject = new DataStructureIdentifierListener(
            $formPersistenceManagerStub,
            $configurationServiceStub,
            $this->get(TranslationService::class),
            self::createStub(FlashMessageService::class),
            self::createStub(ExtbaseConfigurationManagerInterface::class),
            self::createStub(ExtFormConfigurationManagerInterface::class),
            $this->get(LanguageServiceFactory::class),
        );

        $subject->modifyDataStructure($event);

        $sheetIdentifier = md5($persistenceIdentifier . 'standard' . 'contact' . 'EmailToSender');
        self::assertSame(
            [
                5 => ['label' => 'Auto', 'value' => ''],
                10 => ['label' => 'EN', 'value' => 'default'],
            ],
            $event->getDataStructure()['sheets'][$sheetIdentifier]['ROOT']['el']['settings.finishers.EmailToSender.translation.language']['config']['items'],
        );
    }
}
