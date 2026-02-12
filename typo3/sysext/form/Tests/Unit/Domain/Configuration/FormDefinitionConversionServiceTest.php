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
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\CMS\Form\Service\RichTextConfigurationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormDefinitionConversionServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private function createFormDefinitionConversionService(): FormDefinitionConversionService
    {
        $richTextConfigurationServiceMock = $this->createMock(RichTextConfigurationService::class);
        return new FormDefinitionConversionService($richTextConfigurationServiceMock);
    }

    #[Test]
    public function addHmacDataAddsHmacHashes(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';

        $richTextConfigurationServiceMock = $this->createMock(RichTextConfigurationService::class);
        $formDefinitionConversionService = $this->getAccessibleMock(
            FormDefinitionConversionService::class,
            [
                'generateSessionToken',
                'persistSessionToken',
            ],
            [$richTextConfigurationServiceMock]
        );

        $sessionToken = '123';
        $formDefinitionConversionService->method(
            'generateSessionToken'
        )->willReturn($sessionToken);

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
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

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
    public function sanitizeHtmlRemovesScriptTags(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'label' => 'Test<script>alert("XSS")</script>End',
            'text' => '<p>Safe content</p>',
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input);

        self::assertStringNotContainsString('<script>', $result['label']);
        self::assertStringContainsString('Test', $result['label']);
        self::assertStringContainsString('End', $result['label']);
        self::assertSame('Safe content', $result['text']);
    }

    #[Test]
    public function sanitizeHtmlRemovesEventHandlerAttributes(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'label' => '<img src="test.jpg" onerror="alert(1)">',
            'text' => '<a href="#" onclick="malicious()">Link</a>',
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input);

        self::assertStringNotContainsString('onerror', $result['label']);
        self::assertStringNotContainsString('onclick', $result['text']);
    }

    #[Test]
    public function sanitizeHtmlRemovesJavascriptUrls(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'label' => '<a href="javascript:alert(1)">Link</a>',
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input);

        self::assertStringNotContainsString('javascript:', $result['label']);
    }

    #[Test]
    public function sanitizeHtmlRemovesDangerousTags(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'text' => '<iframe src="evil.com"></iframe><p>Safe</p>',
            'label' => '<object data="malicious.swf"></object>',
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input);

        self::assertStringNotContainsString('<iframe', $result['text']);
        self::assertStringNotContainsString('<object', $result['label']);
        self::assertStringContainsString('Safe', $result['text']);
    }

    #[Test]
    public function sanitizeHtmlSanitizesRteFieldsWithHtmlSanitizer(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'type' => 'StaticText',
            // Safe HTML content that should be preserved by the sanitizer
            'text' => '<p><b>Bold</b> and <i>italic</i> and <a href="#">link</a></p><ul><li>Item</li></ul>',
        ];

        $rtePropertyPaths = [
            'StaticText' => [
                'text' => 'form-content',
            ],
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input, $rtePropertyPaths);

        // RTE fields are sanitized with HtmlSanitizer which preserves safe HTML
        // This ensures sanitization even for form definitions from external sources (YAML files)
        self::assertStringContainsString('<b>Bold</b>', $result['text']);
        self::assertStringContainsString('<i>italic</i>', $result['text']);
        self::assertStringContainsString('<p>', $result['text']);
        self::assertStringContainsString('<ul>', $result['text']);
        self::assertStringContainsString('<li>', $result['text']);
    }

    #[Test]
    public function sanitizeHtmlHandlesNestedArrays(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'type' => 'Form',
            'renderables' => [
                [
                    'type' => 'Page',
                    'renderables' => [
                        [
                            'type' => 'StaticText',
                            'properties' => [
                                // Content with dangerous and safe HTML
                                'text' => '<script>alert("XSS")</script><p>Safe content</p>',
                            ],
                        ],
                        [
                            'type' => 'Checkbox',
                            'label' => '<b>Bold label</b>',
                        ],
                    ],
                ],
            ],
            'finishers' => [
                [
                    'identifier' => 'Confirmation',
                    'options' => [
                        'message' => '<script>XSS</script><p>Thank you</p>',
                    ],
                ],
            ],
        ];

        // Define RTE fields
        $rtePropertyPaths = [
            'StaticText' => [
                'properties.text' => 'form-content',
            ],
            'Checkbox' => [
                'label' => 'form-label',
            ],
            '_finishers' => [
                'Confirmation' => [
                    'options.message' => 'form-content',
                ],
            ],
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input, $rtePropertyPaths);

        // RTE fields are sanitized - dangerous content removed, safe HTML preserved
        self::assertStringNotContainsString('<script>', $result['renderables'][0]['renderables'][0]['properties']['text']);
        self::assertStringContainsString('<p>Safe content</p>', $result['renderables'][0]['renderables'][0]['properties']['text']);
        self::assertStringContainsString('<b>Bold label</b>', $result['renderables'][0]['renderables'][1]['label']);
        self::assertStringNotContainsString('<script>', $result['finishers'][0]['options']['message']);
        self::assertStringContainsString('<p>Thank you</p>', $result['finishers'][0]['options']['message']);
    }

    #[Test]
    public function sanitizeHtmlHandlesEmptyStrings(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'label' => '',
            'text' => null,
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input);

        self::assertSame('', $result['label']);
        self::assertNull($result['text']);
    }

    #[Test]
    public function sanitizeHtmlHandlesUnicodeCharacters(): void
    {
        $formDefinitionConversionService = $this->createFormDefinitionConversionService();

        $input = [
            'label' => '<p>Überschrift mit Ümläutén und émojis 🎉</p>',
        ];

        $result = $formDefinitionConversionService->sanitizeHtml($input);

        self::assertStringContainsString('Überschrift', $result['label']);
        self::assertStringContainsString('Ümläutén', $result['label']);
        self::assertStringContainsString('émojis', $result['label']);
    }
}
