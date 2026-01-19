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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormDefinitionConversionServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function addHmacDataAddsHmacHashes(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $formDefinitionConversionService = $this->getAccessibleMock(
            FormDefinitionConversionService::class,
            [
                'generateSessionToken',
                'persistSessionToken',
            ],
            [],
            '',
            false
        );

        $sessionToken = '123';
        $formDefinitionConversionService->method(
            'generateSessionToken'
        )->willReturn($sessionToken);

        $formDefinitionConversionService->method(
            'persistSessionToken'
        )->willReturn(null);

        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'type' => 'Form',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                ],
            ],
        ];

        $data = $formDefinitionConversionService->addHmacData($input);

        $expected = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'type' => 'Form',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
                '_orig__label' => [
                    'value' => 'x',
                    'hmac' => '8b62cd2971adeac49e8900530c9c2067a81e8b53',
                ],
                '_orig__value' => [
                    'value' => 'y',
                    'hmac' => '582e4c76c9e6589b4ca1a85860ac774af6c5a5e0',
                ],
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                    '_orig_heinz' => [
                        'value' => '',
                        'hmac' => $data['sabine']['horst']['_orig_heinz']['hmac'],
                    ],
                ],
                '_orig_heinz' => [
                    'value' => '2',
                    'hmac' => $data['sabine']['_orig_heinz']['hmac'],
                ],
            ],
            '_orig_prototypeName' => [
                'value' => 'standard',
                'hmac' => $data['_orig_prototypeName']['hmac'],
            ],
            '_orig_identifier' => [
                'value' => 'test',
                'hmac' => $data['_orig_identifier']['hmac'],
            ],
            '_orig_type' => [
                'value' => 'Form',
                'hmac' => $data['_orig_type']['hmac'],
            ],
            '_orig_heinz' => [
                'value' => 1,
                'hmac' => $data['_orig_heinz']['hmac'],
            ],
        ];

        self::assertSame($expected, $data);
    }

    #[Test]
    public function removeHmacDataRemoveHmacs(): void
    {
        $formDefinitionConversionService = new FormDefinitionConversionService();
        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
                '_orig__label' => [
                    'value' => 'x',
                    'hmac' => '8b62cd2971adeac49e8900530c9c2067a81e8b53',
                ],
                '_orig__value' => [
                    'value' => 'y',
                    'hmac' => '582e4c76c9e6589b4ca1a85860ac774af6c5a5e0',
                ],
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                    '_orig_heinz' => [
                        'value' => '',
                        'hmac' => '12345',
                    ],
                ],
                '_orig_heinz' => [
                    'value' => '2',
                    'hmac' => '12345',
                ],
            ],
            '_orig_prototypeName' => [
                'value' => 'standard',
                'hmac' => '12345',
            ],
            '_orig_identifier' => [
                'value' => 'test',
                'hmac' => '12345',
            ],
            '_orig_heinz' => [
                'value' => 1,
                'hmac' => '12345',
            ],
        ];

        $expected = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                ],
            ],
        ];

        self::assertSame($expected, $formDefinitionConversionService->removeHmacData($input));
    }

    #[Test]
    public function addRenderableVisibilityAddsEnabledIfMissing(): void
    {
        $formDefinitionConversionService = new FormDefinitionConversionService();
        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'identifier' => 'test',
            'type' => 'Form',
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'test',
            'type' => 'Form',
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'renderingOptions' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'renderingOptions' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'renderingOptions' => [
                'enabled' => true,
            ],
        ];

        self::assertSame($expected, $formDefinitionConversionService->addRenderableVisibility($input));
    }

    #[Test]
    public function addRenderableVisibilityDoesNotOverrideExplicitlySetEnabled(): void
    {
        $formDefinitionConversionService = new FormDefinitionConversionService();
        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'identifier' => 'test',
            'type' => 'Form',
            'renderingOptions' => [
                'enabled' => false,
            ],
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderingOptions' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'test',
            'type' => 'Form',
            'renderingOptions' => [
                'enabled' => false,
            ],
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderingOptions' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $formDefinitionConversionService->addRenderableVisibility($input));
    }

    #[Test]
    public function addRenderableVisibilityAddsEnabledForFinishersAndValidators(): void
    {
        $formDefinitionConversionService = new FormDefinitionConversionService();
        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'identifier' => 'test',
            'type' => 'Form',
            'finishers' => [
                [
                    'identifier' => 'EmailToReceiver',
                ],
            ],
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'validators' => [
                                [
                                    'identifier' => 'NotEmpty',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'identifier' => 'test',
            'type' => 'Form',
            'finishers' => [
                [
                    'identifier' => 'EmailToReceiver',
                    'renderingOptions' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'validators' => [
                                [
                                    'identifier' => 'NotEmpty',
                                    'renderingOptions' => [
                                        'enabled' => true,
                                    ],
                                ],
                            ],
                            'renderingOptions' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'renderingOptions' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'renderingOptions' => [
                'enabled' => true,
            ],
        ];

        self::assertSame($expected, $formDefinitionConversionService->addRenderableVisibility($input));
    }
}
