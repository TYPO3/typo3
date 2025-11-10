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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesCtrlOverrides;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaTypesCtrlOverridesTest extends UnitTestCase
{
    #[Test]
    public function typeSpecificTitleOverridesCtrlTitle(): void
    {
        $input = [
            'recordTypeValue' => 'article',
            'processedTca' => [
                'ctrl' => [
                    'title' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:base_title',
                ],
                'types' => [
                    'article' => [
                        'title' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:article_title',
                        'showitem' => 'title, content',
                    ],
                ],
            ],
        ];

        $subject = new TcaTypesCtrlOverrides();
        $result = $subject->addData($input);

        self::assertSame(
            'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:article_title',
            $result['processedTca']['ctrl']['title']
        );
    }

    #[Test]
    public function typeWithoutTitleKeepsBaseTitle(): void
    {
        $input = [
            'recordTypeValue' => 'news',
            'processedTca' => [
                'ctrl' => [
                    'title' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:base_title',
                ],
                'types' => [
                    'news' => [
                        'showitem' => 'title, content',
                    ],
                ],
            ],
        ];

        $subject = new TcaTypesCtrlOverrides();
        $result = $subject->addData($input);

        self::assertSame(
            'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:base_title',
            $result['processedTca']['ctrl']['title']
        );
    }

    #[Test]
    public function typeSpecificPreviewRendererOverridesCtrlPreviewRenderer(): void
    {
        $input = [
            'recordTypeValue' => 'type1',
            'processedTca' => [
                'ctrl' => [
                    'previewRenderer' => 'defaultRenderer',
                ],
                'types' => [
                    'type1' => [
                        'previewRenderer' => 'customRenderer',
                        'showitem' => 'field1',
                    ],
                ],
            ],
        ];

        $subject = new TcaTypesCtrlOverrides();
        $result = $subject->addData($input);

        self::assertSame(
            'customRenderer',
            $result['processedTca']['ctrl']['previewRenderer']
        );
    }

    #[Test]
    public function onlyAllowedPropertiesAreOverriddenOthersAreIgnored(): void
    {
        $input = [
            'recordTypeValue' => 'test',
            'processedTca' => [
                'ctrl' => [
                    'title' => 'Base Title',
                    'type' => 'foo_type',
                    'previewRenderer' => 'BaseRenderer',
                ],
                'types' => [
                    'test' => [
                        'title' => 'Test Title',
                        'previewRenderer' => 'CustomRenderer',
                        'type' => 'overridden_type', // Should NOT be overridden (not in allowed list)
                        'showitem' => 'field1',
                    ],
                ],
            ],
        ];

        $subject = new TcaTypesCtrlOverrides();
        $result = $subject->addData($input);

        self::assertSame('Test Title', $result['processedTca']['ctrl']['title']);
        self::assertSame('CustomRenderer', $result['processedTca']['ctrl']['previewRenderer']);
        // "type" should NOT be overridden (not in allowed list)
        self::assertSame('foo_type', $result['processedTca']['ctrl']['type']);
    }

    #[Test]
    public function emptyStringValueOverridesProperty(): void
    {
        $input = [
            'recordTypeValue' => 'minimal',
            'processedTca' => [
                'ctrl' => [
                    'title' => 'Base Title',
                ],
                'types' => [
                    'minimal' => [
                        'title' => '', // Explicitly set to empty string
                        'showitem' => 'field1',
                    ],
                ],
            ],
        ];

        $subject = new TcaTypesCtrlOverrides();
        $result = $subject->addData($input);

        // Empty string should override the base title
        self::assertSame('', $result['processedTca']['ctrl']['title']);
    }
}
