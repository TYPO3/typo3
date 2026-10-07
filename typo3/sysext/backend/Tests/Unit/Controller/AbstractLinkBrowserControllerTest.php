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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Controller\LinkBrowserController;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class AbstractLinkBrowserControllerTest extends UnitTestCase
{
    private LinkBrowserController&MockObject&AccessibleObjectInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(LinkBrowserController::class, ['getLanguageService'], [], '', false);
    }

    #[Test]
    public function getLinkAttributeFieldDefinitionsContainsRelField(): void
    {
        $languageService = self::createStub(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static fn(string $key): string => $key);
        $this->subject->method('getLanguageService')->willReturn($languageService);
        $this->subject->_set('linkAttributeValues', ['rel' => 'noopener']);

        $result = $this->subject->_call('getLinkAttributeFieldDefinitions');

        self::assertArrayHasKey('rel', $result);
        self::assertStringContainsString('name="lrel"', $result['rel']);
        self::assertStringContainsString('value="noopener"', $result['rel']);
    }

    #[Test]
    public function getLinkAttributeFieldDefinitionsRendersPlainClassFieldWithoutCssClassItems(): void
    {
        $languageService = self::createStub(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static fn(string $key): string => $key);
        $this->subject->method('getLanguageService')->willReturn($languageService);
        $this->subject->_set('linkAttributeValues', ['class' => 'my-class']);

        $result = $this->subject->_call('getLinkAttributeFieldDefinitions');

        self::assertStringContainsString('name="lclass"', $result['class']);
        self::assertStringContainsString('value="my-class"', $result['class']);
        self::assertStringNotContainsString('typo3-backend-combobox', $result['class']);
    }

    #[Test]
    public function getLinkAttributeFieldDefinitionsRendersCssClassItemsAsComboboxChoices(): void
    {
        $languageService = self::createStub(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static fn(string $key): string => $key);
        $this->subject->method('getLanguageService')->willReturn($languageService);
        $this->subject->_set('linkAttributeValues', ['class' => 'my-class']);
        $this->subject->_set('cssClassItems', [
            ['value' => 'btn btn-primary', 'label' => 'Button <primary>'],
            ['value' => 'link-"download"', 'label' => 'Download'],
        ]);

        $result = $this->subject->_call('getLinkAttributeFieldDefinitions');

        self::assertStringContainsString('<typo3-backend-combobox>', $result['class']);
        self::assertStringContainsString('value="my-class"', $result['class']);
        self::assertStringContainsString(
            '<typo3-backend-combobox-choice value="btn btn-primary">Button &lt;primary&gt;</typo3-backend-combobox-choice>',
            $result['class']
        );
        self::assertStringContainsString(
            '<typo3-backend-combobox-choice value="link-&quot;download&quot;">Download</typo3-backend-combobox-choice>',
            $result['class']
        );
    }

    public static function getCssClassItemsDataProvider(): \Generator
    {
        yield 'no configuration' => [
            [],
            'page',
            [],
        ];
        yield 'global items' => [
            [
                'properties.' => [
                    'cssClass.' => [
                        'items.' => [
                            '10.' => ['value' => 'btn btn-primary', 'label' => 'Button'],
                            '20.' => ['value' => 'link-external'],
                        ],
                    ],
                ],
            ],
            'url',
            [
                ['value' => 'btn btn-primary', 'label' => 'Button'],
                ['value' => 'link-external', 'label' => 'link-external'],
            ],
        ];
        yield 'handler-specific items replace global items' => [
            [
                'properties.' => [
                    'cssClass.' => [
                        'items.' => [
                            '10.' => ['value' => 'btn', 'label' => 'Button'],
                        ],
                    ],
                ],
                'file.' => [
                    'cssClass.' => [
                        'items.' => [
                            '10.' => ['value' => 'link-download', 'label' => 'Download'],
                        ],
                    ],
                ],
            ],
            'file',
            [
                ['value' => 'link-download', 'label' => 'Download'],
            ],
        ];
        yield 'global items apply to handlers without own items' => [
            [
                'properties.' => [
                    'cssClass.' => [
                        'items.' => [
                            '10.' => ['value' => 'btn', 'label' => 'Button'],
                        ],
                    ],
                ],
                'file.' => [
                    'cssClass.' => [
                        'items.' => [
                            '10.' => ['value' => 'link-download', 'label' => 'Download'],
                        ],
                    ],
                ],
            ],
            'page',
            [
                ['value' => 'btn', 'label' => 'Button'],
            ],
        ];
        yield 'items without value are skipped' => [
            [
                'properties.' => [
                    'cssClass.' => [
                        'items.' => [
                            '10.' => ['label' => 'No value'],
                            '20.' => ['value' => '  ', 'label' => 'Blank value'],
                            '30' => 'not an array',
                            '40.' => ['value' => ' btn ', 'label' => 'Button'],
                        ],
                    ],
                ],
            ],
            'page',
            [
                ['value' => 'btn', 'label' => 'Button'],
            ],
        ];
    }

    #[DataProvider('getCssClassItemsDataProvider')]
    #[Test]
    public function getCssClassItemsResolvesPageTsConfig(array $linkHandlerTsConfig, string $handlerId, array $expected): void
    {
        $pageTsConfig = $linkHandlerTsConfig === [] ? [] : ['TCEMAIN.' => ['linkHandler.' => $linkHandlerTsConfig]];

        self::assertSame($expected, $this->subject->_call('getCssClassItems', $pageTsConfig, $handlerId));
    }

    #[Test]
    public function getAllowedLinkAttributesDoesNotContainRelWhenNotAllowed(): void
    {
        $linkHandler = self::createStub(LinkHandlerInterface::class);
        $linkHandler->method('getLinkAttributes')->willReturn(['target', 'title', 'class', 'params', 'rel']);
        $this->subject->_set('displayedLinkHandler', $linkHandler);
        $this->subject->_set('parameters', ['params' => ['allowedOptions' => 'target,title,class,params']]);

        $result = $this->subject->_call('getAllowedLinkAttributes');

        self::assertSame(['target', 'title', 'class', 'params'], $result);
        self::assertNotContains('rel', $result);
    }
}
