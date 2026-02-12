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

namespace TYPO3\CMS\Form\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Service\FormEditorEnrichmentService;
use TYPO3\CMS\Form\Service\RichTextConfigurationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormEditorEnrichmentServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function shouldEnrichEditorWithRichTextReturnsTrueForTextareaEditorWithEnableRichtext(): void
    {
        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $editor = [
            'templateName' => 'Inspector-TextareaEditor',
            'enableRichtext' => true,
        ];

        $result = $subject->_call('shouldEnrichEditorWithRichText', $editor);

        self::assertTrue($result);
    }

    #[Test]
    public function shouldEnrichEditorWithRichTextReturnsFalseForOtherTemplateName(): void
    {
        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $editor = [
            'templateName' => 'Inspector-TextEditor',
            'enableRichtext' => true,
        ];

        $result = $subject->_call('shouldEnrichEditorWithRichText', $editor);

        self::assertFalse($result);
    }

    #[Test]
    public function shouldEnrichEditorWithRichTextReturnsFalseForEnableRichtextFalse(): void
    {
        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $editor = [
            'templateName' => 'Inspector-TextareaEditor',
            'enableRichtext' => false,
        ];

        $result = $subject->_call('shouldEnrichEditorWithRichText', $editor);

        self::assertFalse($result);
    }

    #[Test]
    public function shouldEnrichEditorWithRichTextReturnsFalseIfEnableRichtextMissing(): void
    {
        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $editor = [
            'templateName' => 'Inspector-TextareaEditor',
        ];

        $result = $subject->_call('shouldEnrichEditorWithRichText', $editor);

        self::assertFalse($result);
    }

    #[Test]
    public function resolveRichTextOptionsUsesFormAsDefaultPreset(): void
    {
        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $richTextServiceMock
            ->expects($this->once())
            ->method('resolveCkEditorConfiguration')
            ->with('form-label')
            ->willReturn(['toolbar' => []]);

        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $editor = ['templateName' => 'Inspector-TextareaEditor'];

        $subject->_call('resolveRichTextOptions', $editor);
    }

    #[Test]
    public function resolveRichTextOptionsUsesConfiguredPreset(): void
    {
        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $richTextServiceMock
            ->expects($this->once())
            ->method('resolveCkEditorConfiguration')
            ->with('minimal')
            ->willReturn(['toolbar' => []]);

        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $editor = [
            'templateName' => 'Inspector-TextareaEditor',
            'richtextConfiguration' => 'minimal',
        ];

        $subject->_call('resolveRichTextOptions', $editor);
    }

    #[Test]
    public function enrichFormEditorDefinitionsHandlesCompleteStructure(): void
    {
        $testRteOptions = ['toolbar' => ['bold', 'italic']];

        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $richTextServiceMock->method('resolveCkEditorConfiguration')->willReturn($testRteOptions);

        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $input = [
            'formElementsDefinition' => [
                'StaticText' => [
                    'editors' => [
                        ['templateName' => 'Inspector-TextareaEditor', 'enableRichtext' => true],
                        ['templateName' => 'Inspector-TextEditor'],
                    ],
                    'propertyCollections' => [
                        'validators' => [
                            10 => [
                                'editors' => [
                                    ['templateName' => 'Inspector-TextareaEditor', 'enableRichtext' => true],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Manually set extension as loaded for this test
        $GLOBALS['TYPO3_LOADED_EXT']['rte_ckeditor'] = ['type' => 'S'];

        $result = $subject->enrichFormEditorDefinitions($input);

        self::assertArrayHasKey('rteOptions', $result['formElementsDefinition']['StaticText']['editors'][0]);
        self::assertArrayNotHasKey('rteOptions', $result['formElementsDefinition']['StaticText']['editors'][1]);
        self::assertArrayHasKey('rteOptions', $result['formElementsDefinition']['StaticText']['propertyCollections']['validators'][10]['editors'][0]);
    }

    #[Test]
    public function enrichFormEditorDefinitionsHandlesPropertyCollectionsFinishers(): void
    {
        $testRteOptions = ['toolbar' => ['bold']];

        $richTextServiceMock = $this->createMock(RichTextConfigurationService::class);
        $richTextServiceMock->method('resolveCkEditorConfiguration')->willReturn($testRteOptions);

        $subject = $this->getAccessibleMock(
            FormEditorEnrichmentService::class,
            null,
            [$richTextServiceMock]
        );

        $input = [
            'finishersDefinition' => [
                'EmailToReceiver' => [
                    'propertyCollections' => [
                        'finishers' => [
                            10 => [
                                'editors' => [
                                    ['templateName' => 'Inspector-TextareaEditor', 'enableRichtext' => true],
                                ],
                            ],
                            20 => [
                                'editors' => [
                                    ['templateName' => 'Inspector-TextareaEditor', 'enableRichtext' => true],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Manually set extension as loaded for this test
        $GLOBALS['TYPO3_LOADED_EXT']['rte_ckeditor'] = ['type' => 'S'];

        $result = $subject->enrichFormEditorDefinitions($input);

        self::assertArrayHasKey('rteOptions', $result['finishersDefinition']['EmailToReceiver']['propertyCollections']['finishers'][10]['editors'][0]);
        self::assertArrayHasKey('rteOptions', $result['finishersDefinition']['EmailToReceiver']['propertyCollections']['finishers'][20]['editors'][0]);
    }
}
