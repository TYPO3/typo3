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
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Controller\FormFrontendController;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormFrontendControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function overrideByFlexFormSettingsReturnsNoOverriddenConfigurationIfFlexformOverridesDisabled(): void
    {
        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $subjectMock = $this->getAccessibleMock(
            FormFrontendController::class,
            null,
            [
                $configurationServiceMock,
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->get(FlexFormTools::class),
                $this->createMock(ConfigurationManagerInterface::class),
            ],
        );
        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver',
            ])
        );
        $request = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($request));
        $contentObject = new ContentObjectRenderer();
        $request = $request->withAttribute('currentContentObject', $contentObject);
        $contentObject->setRequest($request);
        $subjectMock->_set('request', $request);
        $contentObject->data = [
            'pi_flexform' => $this->get(FlexFormTools::class)->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => [
                                'vDEF' => 'Message Subject overridden',
                            ],
                            'settings.finishers.EmailToReceiver.recipients' => [
                                'el' => [
                                    'abc' => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => 'your.company@example.com overridden',
                                                ],
                                                'name' => [
                                                    'vDEF' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'settings.finishers.EmailToReceiver.format' => [
                                'vDEF' => 'html overridden',
                            ],
                        ],
                    ],
                ],
            ]),
        ];
        $configurationServiceMock->method('getPrototypeConfiguration')->with(self::anything())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [],
                            'recipients' => [],
                            'format' => [],
                        ],
                    ],
                ],
            ],
        ]);
        $subjectMock->_set('settings', [
            'overrideFinishers' => 0,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Message Subject overridden',
                    'recipients' => [
                        'your.company@example.com overridden' => '',
                    ],
                    'format' => 'html overridden',
                ],
            ],
        ]);
        $input = [
            'identifier' => 'ext-form-identifier',
            'persistenceIdentifier' => '1:/foo',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];
        $expected = [
            'identifier' => 'ext-form-identifier',
            'persistenceIdentifier' => '1:/foo',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('overrideByFlexFormSettings', $input));
    }

    #[Test]
    public function overrideByFlexFormSettingsReturnsOverriddenConfigurationIfFlexformOverridesEnabled(): void
    {
        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $subjectMock = $this->getAccessibleMock(
            FormFrontendController::class,
            null,
            [
                $configurationServiceMock,
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->get(FlexFormTools::class),
                $this->createMock(ConfigurationManagerInterface::class),
            ],
        );
        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver',
            ])
        );
        $request = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($request));
        $contentObject = new ContentObjectRenderer();
        $request = $request->withAttribute('currentContentObject', $contentObject);
        $contentObject->setRequest($request);
        $subjectMock->_set('request', $request);
        $contentObject->data = [
            'pi_flexform' => $this->get(FlexFormTools::class)->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => [
                                'vDEF' => 'Message Subject overridden',
                            ],
                            'settings.finishers.EmailToReceiver.recipients' => [
                                'el' => [
                                    'abc' => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => 'your.company@example.com overridden',
                                                ],
                                                'name' => [
                                                    'vDEF' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'settings.finishers.EmailToReceiver.format' => [
                                'vDEF' => 'html overridden',
                            ],
                        ],
                    ],
                ],
            ]),
        ];
        $configurationServiceMock->method('getPrototypeConfiguration')->with(self::anything())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                            'recipients' => [
                                'type' => 'array',
                                'section' => true,
                                'sectionItemKey' => 'email',
                                'sectionItemValue' => 'name',
                            ],
                            'format' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $subjectMock->_set('settings', [
            'overrideFinishers' => 1,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Message Subject overridden',
                    'recipients' => [
                        'abcxyz' => [
                            'email' => 'your.company@example.com overridden',
                            'name' => '',
                        ],
                    ],
                    'format' => 'html overridden',
                ],
            ],
        ]);
        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];
        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject overridden',
                        'recipients' => [
                            'your.company@example.com overridden' => '',
                        ],
                        'format' => 'html overridden',
                        'translation' => [
                            'propertiesExcludedFromTranslation' => [
                                'subject',
                                'recipients',
                                'format',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('overrideByFlexFormSettings', $input));
    }

    #[Test]
    public function overrideByFlexFormSettingsReturnsNotOverriddenConfigurationKeyIfFlexformOverridesAreNotRepresentedInFormEngineConfiguration(): void
    {
        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $mockController = $this->getAccessibleMock(
            FormFrontendController::class,
            null,
            [
                $configurationServiceMock,
                $this->createMock(FormPersistenceManagerInterface::class),
                $this->get(FlexFormTools::class),
                $this->createMock(ConfigurationManagerInterface::class),
            ],
        );

        $sheetIdentifier = md5(
            implode('', [
                '1:/foo',
                'standard',
                'ext-form-identifier',
                'EmailToReceiver',
            ])
        );
        $request = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($request));
        $contentObject = new ContentObjectRenderer();
        $request = $request->withAttribute('currentContentObject', $contentObject);
        $contentObject->setRequest($request);
        $mockController->_set('request', $request);
        $contentObject->data = [
            'pi_flexform' => $this->get(FlexFormTools::class)->flexArray2Xml([
                'data' => [
                    $sheetIdentifier => [
                        'lDEF' => [
                            'settings.finishers.EmailToReceiver.subject' => [
                                'vDEF' => 'Message Subject overridden',
                            ],
                            'settings.finishers.EmailToReceiver.recipients' => [
                                'el' => [
                                    'abc' => [
                                        '_arrayContainer' => [
                                            'el' => [
                                                'email' => [
                                                    'vDEF' => 'your.company@example.com overridden',
                                                ],
                                                'name' => [
                                                    'vDEF' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'settings.finishers.EmailToReceiver.format' => [
                                'vDEF' => 'html overridden',
                            ],
                        ],
                    ],
                ],
            ]),
        ];
        $configurationServiceMock->method('getPrototypeConfiguration')->with(self::anything())->willReturn([
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'FormEngine' => [
                        'elements' => [
                            'subject' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                            'recipients' => [
                                'type' => 'array',
                                'section' => true,
                                'sectionItemKey' => 'email',
                                'sectionItemValue' => 'name',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $mockController->_set('settings', [
            'overrideFinishers' => 1,
            'finishers' => [
                'EmailToReceiver' => [
                    'subject' => 'Message Subject overridden',
                    'recipients' => [
                        'abcxyz' => [
                            'email' => 'your.company@example.com overridden',
                            'name' => '',
                        ],
                    ],
                    'format' => 'html overridden',
                ],
            ],
        ]);
        $input = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject',
                        'recipients' => [
                            'your.company@example.com' => '',
                        ],
                        'format' => 'html',
                    ],
                ],
            ],
        ];
        $expected = [
            'persistenceIdentifier' => '1:/foo',
            'identifier' => 'ext-form-identifier',
            'prototypeName' => 'standard',
            'finishers' => [
                0 => [
                    'identifier' => 'EmailToReceiver',
                    'options' => [
                        'subject' => 'Message Subject overridden',
                        'recipients' => [
                            'your.company@example.com overridden' => '',
                        ],
                        'format' => 'html',
                        'translation' => [
                            'propertiesExcludedFromTranslation' => [
                                'subject',
                                'recipients',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $mockController->_call('overrideByFlexFormSettings', $input));
    }
}
