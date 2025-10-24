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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Controller\FormEditorController;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Event\BeforeFormIsSavedEvent;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\Type\FormDefinitionArray;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormEditorControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function getInsertRenderablesPanelConfigurationReturnsGroupedAndSortedConfiguration(): void
    {
        $translationServiceMock = $this->createMock(TranslationService::class);
        $translationServiceMock->method('translateValuesRecursive')->willReturnArgument(0);
        $subjectMock = $this->getAccessibleMock(
            FormEditorController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(FormDefinitionConversionService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $translationServiceMock,
                $this->createMock(ConfigurationService::class),
                $this->createMock(UriBuilder::class),
                $this->createMock(ArrayFormFactory::class),
                $this->createMock(ViewFactoryInterface::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(CacheManager::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $prototypeConfiguration = [
            'formEditor' => [
                'formElementGroups' => [
                    'input' => [
                        'label' => 'Basic elements',
                    ],
                    'select' => [
                        'label' => 'Select elements',
                    ],
                ],
            ],
        ];
        $input = [
            'Password' => [
                'group' => 'input',
                'groupSorting' => 110,
                'iconIdentifier' => 'form-password',
                'label' => 'Password label',
                'description' => 'Password description',
            ],
            'Text' => [
                'group' => 'input',
                'groupSorting' => 100,
                'iconIdentifier' => 'form-text',
                'label' => 'Text label',
                'description' => 'Text description',
            ],
            'SingleSelect' => [
                'group' => 'select',
                'groupSorting' => 100,
                'iconIdentifier' => 'form-single-select',
                'label' => 'Single select label',
                'description' => 'Single select description',
            ],
        ];
        $expected = [
            'input' => [
                'identifier' => 'input',
                'items' => [
                    0 => [
                        'identifier' => 'Text',
                        'label' => 'Text label',
                        'description' => 'Text description',
                        'requestType' => 'event',
                        'event' => 'typo3:form:insert-element-click',
                        'sorting' => 100,
                        'icon' => 'form-text',
                    ],
                    1 => [
                        'identifier' => 'Password',
                        'label' => 'Password label',
                        'description' => 'Password description',
                        'requestType' => 'event',
                        'event' => 'typo3:form:insert-element-click',
                        'sorting' => 110,
                        'icon' => 'form-password',
                    ],
                ],
                'label' => 'Basic elements',
            ],
            'select' => [
                'identifier' => 'select',
                'items' => [
                    0 => [
                        'identifier' => 'SingleSelect',
                        'label' => 'Single select label',
                        'description' => 'Single select description',
                        'requestType' => 'event',
                        'event' => 'typo3:form:insert-element-click',
                        'sorting' => 100,
                        'icon' => 'form-single-select',
                    ],
                ],
                'label' => 'Select elements',
            ],
        ];
        $result = $subjectMock->_call('getInsertRenderablesPanelConfiguration', $prototypeConfiguration, $input);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getFormEditorDefinitionsReturnReducedConfiguration(): void
    {
        $translationServiceMock = $this->createMock(TranslationService::class);
        $translationServiceMock->method('translateValuesRecursive')->willReturnArgument(0);
        $subjectMock = $this->getAccessibleMock(
            FormEditorController::class,
            null,
            [
                $this->get(ModuleTemplateFactory::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(IconFactory::class),
                $this->createMock(FormDefinitionConversionService::class),
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->createMock(ExtFormConfigurationManagerInterface::class),
                $translationServiceMock,
                $this->createMock(ConfigurationService::class),
                $this->createMock(UriBuilder::class),
                $this->createMock(ArrayFormFactory::class),
                $this->createMock(ViewFactoryInterface::class),
                $this->createMock(DatabaseService::class),
                $this->createMock(CacheManager::class),
                $this->createMock(ComponentFactory::class),
            ],
        );
        $prototypeConfiguration = [
            'formEditor' => [
                'someOtherValues' => [
                    'horst' => [
                        'key' => 'value',
                    ],
                    'gertrud' => [
                        'key' => 'value',
                    ],
                ],
                'formElementPropertyValidatorsDefinition' => [
                    'NotEmpty' => [
                        'key' => 'value',
                    ],
                ],
            ],
            'formElementsDefinition' => [
                'Form' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
                'Text' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
            ],
            'finishersDefinition' => [
                'Confirmation' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
                'EmailToSender' => [
                    'formEditor' => [
                        'key' => 'value',
                    ],
                    'someOtherValues' => [
                        'horst' => [
                            'key' => 'value',
                        ],
                        'gertrud' => [
                            'key' => 'value',
                        ],
                    ],
                ],
            ],
            'someOtherValues' => [
                'horst' => [
                    'key' => 'value',
                ],
                'gertrud' => [
                    'key' => 'value',
                ],
            ],
        ];
        $expected = [
            'formElements' => [
                'Form' => [
                    'key' => 'value',
                ],
                'Text' => [
                    'key' => 'value',
                ],
            ],
            'finishers' => [
                'Confirmation' => [
                    'key' => 'value',
                ],
                'EmailToSender' => [
                    'key' => 'value',
                ],
            ],
            'formElementPropertyValidators' => [
                'NotEmpty' => [
                    'key' => 'value',
                ],
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('getFormEditorDefinitions', $prototypeConfiguration));
    }

    #[Test]
    public function renderFormEditorTemplatesThrowsExceptionIfLayoutRootPathsNotSet(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294721);
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $mockController->_call(
            'renderFormEditorTemplates',
            [
                'formEditor' => [
                    'formEditorFluidConfiguration' => [
                        'templatePathAndFilename' => '',
                    ],
                ],
            ],
            []
        );
    }

    #[Test]
    public function renderFormEditorTemplatesThrowsExceptionIfLayoutRootPathsNotArray(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294721);
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $mockController->_call(
            'renderFormEditorTemplates',
            [
                'formEditor' => [
                    'formEditorFluidConfiguration' => [
                        'templatePathAndFilename' => '',
                        'layoutRootPaths' => '',
                    ],
                ],
            ],
            []
        );
    }

    #[Test]
    public function renderFormEditorTemplatesThrowsExceptionIfPartialRootPathsNotSet(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294722);
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $mockController->_call(
            'renderFormEditorTemplates',
            [
                'formEditor' => [
                    'formEditorFluidConfiguration' => [
                        'templatePathAndFilename' => '',
                        'layoutRootPaths' => [],
                    ],
                ],
            ],
            []
        );
    }

    #[Test]
    public function renderFormEditorTemplatesThrowsExceptionIfPartialRootPathsNotArray(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1480294722);
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $mockController->_call(
            'renderFormEditorTemplates',
            [
                'formEditor' => [
                    'formEditorFluidConfiguration' => [
                        'templatePathAndFilename' => '',
                        'layoutRootPaths' => [],
                    ],
                ],
            ],
            []
        );
    }

    #[Test]
    public function renderFormEditorTemplatesThrowsExceptionIfTemplatePathAndFilenameNotSet(): void
    {
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1485636499);
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $mockController->_call(
            'renderFormEditorTemplates',
            [
                'formEditor' => [
                    'formEditorFluidConfiguration' => [],
                ],
            ],
            []
        );
    }

    #[Test]
    public function transformMultiValuePropertiesForFormEditorConvertMultiValueDataIntoMetaData(): void
    {
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $input = [
            0 => [
                'bar' => 'baz',
            ],
            1 => [
                'type' => 'SOMEELEMENT',
                'properties' => [
                    'options' => [
                        5 => '5',
                        4 => '4',
                        3 => '3',
                        2 => '2',
                        1 => '1',
                    ],
                ],
            ],
            2 => [
                0 => [
                    'type' => 'TEST',
                    'properties' => [
                        'options' => [
                            5 => '5',
                            4 => '4',
                            3 => '3',
                            2 => '2',
                            1 => '1',
                        ],
                    ],
                ],
            ],
        ];
        $multiValueProperties = [
            'TEST' => [
                0 => 'properties.options',
            ],
        ];
        $expected = [
            0 => [
                'bar' => 'baz',
            ],
            1 => [
                'type' => 'SOMEELEMENT',
                'properties' => [
                    'options' => [
                        5 => '5',
                        4 => '4',
                        3 => '3',
                        2 => '2',
                        1 => '1',
                    ],
                ],
            ],
            2 => [
                0 => [
                    'type' => 'TEST',
                    'properties' => [
                        'options' => [
                            ['_label' => '5', '_value' => 5],
                            ['_label' => '4', '_value' => 4],
                            ['_label' => '3', '_value' => 3],
                            ['_label' => '2', '_value' => 2],
                            ['_label' => '1', '_value' => 1],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $mockController->_call('transformMultiValuePropertiesForFormEditor', $input, 'type', $multiValueProperties));
    }

    #[Test]
    public function filterEmptyArraysRemovesEmptyArrayKeys(): void
    {
        $mockController = $this->getAccessibleMock(FormEditorController::class, null, [], '', false);
        $input = [
            'heinz' => 1,
            'klaus' => [],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                ],
            ],
        ];
        $expected = [
            'heinz' => 1,
            'sabine' => [
                'heinz' => '2',
                'horst' => [
                    'heinz' => '',
                ],
            ],
        ];
        self::assertSame($expected, $mockController->_call('filterEmptyArrays', $input));
    }

    #[Test]
    public function beforeFormIsSavedEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        /** @var Container $container */
        $container = $this->get('service_container');

        $state = [
            'before-form-saved-listener' => null,
        ];

        // Dummy listeners that just record that the event existed.
        $container->set(
            'before-form-saved-listener',
            static function (BeforeFormIsSavedEvent $event) use (&$state) {
                $event->formPersistenceIdentifier = '1:/form_definitions/new_form.form.yaml';
                $event->form['label'] = 'bar';
                $state['before-form-saved-listener'] = $event;
            }
        );

        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFormIsSavedEvent::class, 'before-form-saved-listener');

        $serverRequest = (new ServerRequest('https://example.com', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $formDefinition = (new FormDefinitionConversionService())->addHmacData([
            'type' => 'Form',
            'identifier' => 'test123_1',
            'label' => 'test123',
            'prototypeName' => 'standard',
        ]);

        $parsedBody = [
            'formPersistenceIdentifier' => '1:/form_definitions/test_form.form.yaml',
            'formDefinition' => new FormDefinitionArray($formDefinition),
        ];
        $serverRequest = $serverRequest->withParsedBody($parsedBody);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName(FormEditorController::class)
            ->withControllerName('FormEditorController')
            ->withArguments($parsedBody)
            ->withControllerActionName('saveForm');
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->get(FormEditorController::class);
        $subject->processRequest($request);

        self::assertInstanceOf(BeforeFormIsSavedEvent::class, $state['before-form-saved-listener']);
        self::assertEquals('1:/form_definitions/new_form.form.yaml', $state['before-form-saved-listener']->formPersistenceIdentifier);
        self::assertEquals('bar', $state['before-form-saved-listener']->form['label']);
    }
}
