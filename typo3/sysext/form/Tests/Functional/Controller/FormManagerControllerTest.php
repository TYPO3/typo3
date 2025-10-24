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

namespace TYPO3\CMS\Form\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Route as SymfonyRoute;
use TYPO3\CMS\Backend\Routing\Route as BackendRoute;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder as CoreUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder as ExtbaseUriBuilder;
use TYPO3\CMS\Form\Controller\FormManagerController;
use TYPO3\CMS\Form\Event\BeforeFormIsCreatedEvent;
use TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent;
use TYPO3\CMS\Form\Event\BeforeFormIsDuplicatedEvent;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormManagerControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/form/Tests/Functional/Controller/Fixtures/Folders/fileadmin/form_definitions' => 'fileadmin/form_definitions',
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'defaultTypoScript_setup' => '@import "EXT:form/Tests/Functional/Controller/Fixtures/formSetup.typoscript"',
        ],
    ];

    #[Test]
    public function getAccessibleFormStorageFoldersReturnsProcessedArray(): void
    {
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $formPersistenceManagerMock,
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );

        $storageMock1 = $this->createMock(ResourceStorage::class);
        $storageMock2 = $this->createMock(ResourceStorage::class);

        $storageMock1->method('isPublic')->willReturn(true);
        $storageMock2->method('isPublic')->willReturn(false);

        $folder1Mock = $this->createMock(Folder::class);
        $folder1Mock->method('getPublicUrl')->willReturn('/fileadmin/user_upload/');
        $folder1Mock->method('getStorage')->willReturn($storageMock1);

        $folder2Mock = $this->createMock(Folder::class);
        $folder2Mock->method('getStorage')->willReturn($storageMock2);

        $formPersistenceManagerMock->method('getAccessibleFormStorageFolders')->willReturn([
            '1:/user_upload/' => $folder1Mock,
            '2:/forms/' => $folder2Mock,
        ]);
        $formPersistenceManagerMock->method('getAccessibleExtensionFolders')->willReturn([
            'EXT:form/Resources/Forms/' => '/some/path/form/Resources/Forms/',
            'EXT:form_additions/Resources/Forms/' => '/some/path/form_additions/Resources/Forms/',
        ]);

        $expected = [
            0 => [
                'label' => '/fileadmin/user_upload/',
                'value' => '1:/user_upload/',
            ],
            1 => [
                'label' => '2:/forms/',
                'value' => '2:/forms/',
            ],
            2 => [
                'label' => 'EXT:form/Resources/Forms/',
                'value' => 'EXT:form/Resources/Forms/',
            ],
            3 => [
                'label' => 'EXT:form_additions/Resources/Forms/',
                'value' => 'EXT:form_additions/Resources/Forms/',
            ],
        ];

        self::assertSame($expected, $subjectMock->_call('getAccessibleFormStorageFolders', [], true));
    }

    #[Test]
    public function getFormManagerAppInitialDataReturnsProcessedArray(): void
    {
        $translationServiceMock = $this->createMock(TranslationService::class);
        $translationServiceMock->method('translateValuesRecursive')->willReturnArgument(0);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            ['getAccessibleFormStorageFolders'],
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $translationServiceMock,
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );

        $mockUriBuilder = $this->createMock(ExtbaseUriBuilder::class);
        $mockUriBuilder->method('uriFor')->willReturn('/typo3/index.php?some=param');
        $subjectMock->_set('uriBuilder', $mockUriBuilder);

        $subjectMock->method('getAccessibleFormStorageFolders')
            ->willReturn([
                0 => [
                    'label' => 'user_upload',
                    'value' => '1:/user_upload/',
                ],
            ]);
        $expected = [
            'selectablePrototypesConfiguration' => [],
            'accessibleFormStorageFolders' => [
                0 => [
                    'label' => 'user_upload',
                    'value' => '1:/user_upload/',
                ],
            ],
            'endpoints' => [
                'create' => '/typo3/index.php?some=param',
                'duplicate' => '/typo3/index.php?some=param',
                'delete' => '/typo3/index.php?some=param',
                'references' => '/typo3/index.php?some=param',
            ],
        ];
        $result = $subjectMock->_call(
            'getFormManagerAppInitialData',
            [
                'formManager' => [
                    'selectablePrototypesConfiguration' => [],
                ],
            ]
        );
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getAvailableFormDefinitionsReturnsProcessedArray(): void
    {
        $formPersistenceManagerMock = $this->createMock(FormPersistenceManagerInterface::class);
        $databaseServiceMock = $this->createMock(DatabaseService::class);
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $databaseServiceMock,
                $formPersistenceManagerMock,
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $formPersistenceManagerMock->method('listForms')->willReturn([
            0 => [
                'identifier' => 'ext-form-identifier',
                'name' => 'some name',
                'persistenceIdentifier' => '1:/user_uploads/someFormName.yaml',
                'readOnly' => false,
                'removable' => true,
                'location' => 'storage',
                'duplicateIdentifier' => false,
            ],
        ]);
        $databaseServiceMock->method('getAllReferencesForFileUid')->willReturn([
            0 => 0,
        ]);
        $databaseServiceMock->method('getAllReferencesForPersistenceIdentifier')->willReturn([
            '1:/user_uploads/someFormName.yaml' => 2,
        ]);
        $expected = [
            0 => [
                'identifier' => 'ext-form-identifier',
                'name' => 'some name',
                'persistenceIdentifier' => '1:/user_uploads/someFormName.yaml',
                'readOnly' => false,
                'removable' => true,
                'location' => 'storage',
                'duplicateIdentifier' => false,
                'referenceCount' => 2,
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('getAvailableFormDefinitions', []));
    }

    #[Test]
    public function getProcessedReferencesRowsThrowsExceptionIfPersistenceIdentifierIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1477071939);
        $subjectMock = $this->getAccessibleMock(FormManagerController::class, null, [], '', false);
        $subjectMock->_call('getProcessedReferencesRows', '');
    }

    #[Test]
    public function isValidTemplatePathReturnsTrueIfTemplateIsDefinedAndExists(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        self::assertTrue($subjectMock->_call(
            'isValidTemplatePath',
            [
                'formManager' => [
                    'selectablePrototypesConfiguration' => [
                        0 => [
                            'identifier' => 'standard',
                            'label' => 'some label',
                            'newFormTemplates' => [
                                0 => [
                                    'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                    'label' => 'some label',
                                ],
                                1 => [
                                    'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml',
                                    'label' => 'some other label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'standard',
            'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml'
        ));
    }

    #[Test]
    public function isValidTemplatePathReturnsFalseIfTemplateIsDefinedButNotExists(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        self::assertFalse(
            $subjectMock->_call(
                'isValidTemplatePath',
                [
                    'formManager' => [
                        'selectablePrototypesConfiguration' => [
                            0 => [
                                'identifier' => 'standard',
                                'label' => 'some label',
                                'newFormTemplates' => [
                                    0 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                        'label' => 'some label',
                                    ],
                                    1 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml',
                                        'label' => 'some other label',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'standard',
                'EXT:form/Tests/Functional/Controller/Fixtures/NonExistingForm.yaml'
            )
        );
    }

    #[Test]
    public function isValidTemplatePathReturnsFalseIfTemplateIsNotDefinedAndExists(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->createMock(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        self::assertFalse(
            $subjectMock->_call(
                'isValidTemplatePath',
                [
                    'formManager' => [
                        'selectablePrototypesConfiguration' => [
                            0 => [
                                'identifier' => 'standard',
                                'label' => 'some label',
                                'newFormTemplates' => [
                                    0 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                        'label' => 'some label',
                                    ],
                                    1 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml',
                                        'label' => 'some other label',
                                    ],
                                ],
                            ],
                            1 => [
                                'identifier' => 'other',
                                'label' => 'some label',
                                'newFormTemplates' => [
                                    0 => [
                                        'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/BlankForm.yaml',
                                        'label' => 'some label',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'other',
                'EXT:form/Tests/Functional/Controller/Fixtures/SimpleContactForm.yaml'
            )
        );
    }

    #[Test]
    public function convertFormNameToIdentifierRemoveSpaces(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->get(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $input = 'test form';
        $expected = 'testform';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function convertFormNameToIdentifierConvertAccentedCharacters(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->get(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $input = 'téstform';
        $expected = 'testform';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function convertFormNameToIdentifierConvertAccentedCharactersNotInNFC(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->get(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $input = 'test form ' . hex2bin('667275cc88686e65757a6569746c696368656e');
        $expected = 'testformfruehneuzeitlichen';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function convertFormNameToIdentifierRemoveSpecialChars(): void
    {
        $subjectMock = $this->getAccessibleMock(
            FormManagerController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $this->createMock(TranslationService::class),
                $this->get(CharsetConverter::class),
                $this->createMock(CoreUriBuilder::class),
                $this->createMock(YamlSource::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $input = 'test form ä#!_-01';
        $expected = 'testformae_-01';
        self::assertSame($expected, $subjectMock->_call('convertFormNameToIdentifier', $input));
    }

    #[Test]
    public function beforeFormIsCreatedEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        /** @var Container $container */
        $container = $this->get('service_container');

        $state = [
            'before-form-create-listener' => null,
        ];

        // Dummy listeners that just record that the event existed.
        $container->set(
            'before-form-create-listener',
            static function (BeforeFormIsCreatedEvent $event) use (&$state) {
                $event->formPersistenceIdentifier = '1:/form_definitions/new_form.form.yaml';
                $event->form['label'] = 'bar';
                $state['before-form-create-listener'] = $event;
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFormIsCreatedEvent::class, 'before-form-create-listener');

        $serverRequest = (new ServerRequest('https://example.com', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $parsedBody = [
            'formName' => 'test',
            'templatePath' => 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml',
            'prototypeName' => 'standard',
            'savePath' => '1:/form_definitions/',
        ];
        $serverRequest = $serverRequest->withParsedBody($parsedBody);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName(FormManagerController::class)
            ->withControllerName('FormManagerController')
            ->withArguments($parsedBody)
            ->withControllerActionName('create');
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(FormManagerController::class);
        $subject->processRequest($request);

        self::assertInstanceOf(BeforeFormIsCreatedEvent::class, $state['before-form-create-listener']);
        self::assertEquals('1:/form_definitions/new_form.form.yaml', $state['before-form-create-listener']->formPersistenceIdentifier);
        self::assertEquals('bar', $state['before-form-create-listener']->form['label']);
    }

    #[Test]
    public function beforeFormIsDeletedEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        /** @var Container $container */
        $container = $this->get('service_container');

        $state = [
            'before-form-deleted-listener' => null,
            'before-form-deleted-listener-unused' => null,
        ];

        // Listeners which stops the event
        $container->set(
            'before-form-deleted-listener',
            static function (BeforeFormIsDeletedEvent $event) use (&$state) {
                $event->preventDeletion = true;
                $state['before-form-deleted-listener'] = $event;
            }
        );

        // Listeners which should not be called
        $container->set(
            'before-form-deleted-listener-unused',
            static function (BeforeFormIsDeletedEvent $event) use (&$state) {
                $state['before-form-deleted-listener-unused'] = $event;
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFormIsDeletedEvent::class, 'before-form-deleted-listener');
        $eventListener->addListener(BeforeFormIsDeletedEvent::class, 'before-form-deleted-listener-unused');

        $serverRequest = (new ServerRequest('https://example.com', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $parsedBody = [
            'formPersistenceIdentifier' => '1:/form_definitions/test_form.form.yaml',
        ];
        $serverRequest = $serverRequest->withParsedBody($parsedBody);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName(FormManagerController::class)
            ->withControllerName('FormManagerController')
            ->withArguments($parsedBody)
            ->withControllerActionName('delete');
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(FormManagerController::class);
        $subject->processRequest($request);

        self::assertInstanceOf(BeforeFormIsDeletedEvent::class, $state['before-form-deleted-listener']);
        self::assertNull($state['before-form-deleted-listener-unused']);
        self::assertTrue($state['before-form-deleted-listener']->preventDeletion);
    }

    #[Test]
    public function beforeFormIsDuplicatedEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        /** @var Container $container */
        $container = $this->get('service_container');

        $state = [
            'before-form-duplicated-listener' => null,
        ];

        // Dummy listeners that just record that the event existed.
        $container->set(
            'before-form-duplicated-listener',
            static function (BeforeFormIsDuplicatedEvent $event) use (&$state) {
                $event->formPersistenceIdentifier = '1:/form_definitions/duplicated_form.form.yaml';
                $event->form['label'] = 'bar';
                $state['before-form-duplicated-listener'] = $event;
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFormIsDuplicatedEvent::class, 'before-form-duplicated-listener');

        $serverRequest = (new ServerRequest('https://example.com', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $parsedBody = [
            'formName' => 'test',
            'formPersistenceIdentifier' => '1:/form_definitions/test_form.form.yaml',
            'savePath' => '1:/form_definitions/',
        ];
        $serverRequest = $serverRequest->withParsedBody($parsedBody);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName(FormManagerController::class)
            ->withControllerName('FormManagerController')
            ->withArguments($parsedBody)
            ->withControllerActionName('duplicate');
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(FormManagerController::class);
        $subject->processRequest($request);

        self::assertInstanceOf(BeforeFormIsDuplicatedEvent::class, $state['before-form-duplicated-listener']);
        self::assertEquals('1:/form_definitions/duplicated_form.form.yaml', $state['before-form-duplicated-listener']->formPersistenceIdentifier);
        self::assertEquals('bar', $state['before-form-duplicated-listener']->form['label']);
    }

    #[Test]
    public function formIsCreatedFromTemplateWithEnvSubstitution(): void
    {
        $testEnv = 'TEST';
        putenv('FORM_ENV=' . $testEnv);
        $route = $this->createBackendRouteFromSymfonyRoute(
            $this->get(Router::class)->getRoute('web_FormFormbuilder.FormManager_create')
        );
        $serverRequest = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody([
                'formName' => 'testform',
                'templatePath' => 'EXT:form/Tests/Functional/Controller/Fixtures/FormTemplate.yaml',
                'prototypeName' => 'standard',
                'savePath' => '1:/form_definitions/',
            ])
            ->withAttribute('route', $route)
            ->withAttribute('module', $route->getOption('module'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $bootstrap = $this->get(Bootstrap::class);
        $result = $bootstrap->handleBackendRequest($serverRequest);
        $status = json_decode((string)$result->getBody(), true)['status'] ?? null;
        $targetFilePath = $this->instancePath . '/fileadmin/form_definitions/testform.form.yaml';

        self::assertSame('success', $status);
        self::assertFileExists($targetFilePath);
        self::assertStringContainsString('Form env:' . $testEnv, file_get_contents($targetFilePath));
    }

    /**
     * @todo this transformation should be in Backend\Routing\Route::fromSymfonyRoute
     * @see https://review.typo3.org/c/Packages/TYPO3.CMS/+/90148
     */
    private function createBackendRouteFromSymfonyRoute(SymfonyRoute $symfonyRoute): BackendRoute
    {
        $symfonyRouteOptions = $symfonyRoute->getOptions();
        $symfonyRouteOptions['_identifier'] = 'web_FormFormbuilder.FormManager_create';
        unset($symfonyRouteOptions['methods']);
        return new BackendRoute($symfonyRoute->getPath(), $symfonyRouteOptions);
    }
}
