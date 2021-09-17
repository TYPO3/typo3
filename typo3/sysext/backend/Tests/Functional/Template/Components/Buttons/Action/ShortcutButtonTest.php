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

namespace TYPO3\CMS\Backend\Tests\Functional\Template\Components\Buttons\Action;

use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ShortcutButtonTest extends FunctionalTestCase
{
    private const FIXTURES_PATH_PATTERN = __DIR__ . '/../../../Fixtures/%s.html';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function isButtonValid(): void
    {
        self::assertFalse((new ShortcutButton())->isValid());
        self::assertTrue((new ShortcutButton())->setRouteIdentifier('web_list')->isValid());
        // @todo Remove below in v12
        self::assertTrue((new ShortcutButton())->setArguments(['route' => 'web_list'])->isValid());
        self::assertTrue((new ShortcutButton())->setModuleName('web_list')->isValid());
    }

    /**
     * @dataProvider rendersCorrectMarkupDataProvider
     * @test
     */
    public function rendersCorrectMarkup(ShortcutButton $button, string $expectedMarkupFile): void
    {
        self::assertEquals(
            $this->normalizeSpaces(file_get_contents(sprintf(self::FIXTURES_PATH_PATTERN, $expectedMarkupFile))),
            $this->normalizeSpaces($button->render())
        );
    }

    public function rendersCorrectMarkupDataProvider(): \Generator
    {
        yield 'Recordlist' => [
            (new ShortcutButton())
                ->setRouteIdentifier('web_list')
                ->setDisplayName('Recordlist')
                ->setCopyUrlToClipboard(false),
            'RecordList'
        ];
        yield 'Recordlist with copyToClipboard action' => [
            (new ShortcutButton())
                ->setRouteIdentifier('web_list')
                ->setDisplayName('Recordlist'),
            'RecordListCopyToClipboard'
        ];
        // @todo Below is deprecated functionality which only provides backwards compatibility for v11. Remove in v12!
        yield 'Recordlist as route path' => [
            (new ShortcutButton())
                ->setRouteIdentifier('/module/web/list')
                ->setDisplayName('Recordlist')
                ->setCopyUrlToClipboard(false),
            'RecordList'
        ];
        yield 'Recordlist - single table view' => [
            (new ShortcutButton())
                ->setRouteIdentifier('web_list')
                ->setDisplayName('Recordlist - single table view')
                ->setCopyUrlToClipboard(false)
                ->setArguments([
                    'id' => 123,
                    'table' => 'some_table',
                    'GET' => [
                        'clipBoard' => 1
                    ]
                ]),
            'RecordListSingleTable'
        ];
        yield 'Recordlist - single table view with copyToClipboard action' => [
            (new ShortcutButton())
                ->setRouteIdentifier('web_list')
                ->setDisplayName('Recordlist - single table view')
                ->setArguments([
                    'id' => 123,
                    'table' => 'some_table',
                    'GET' => [
                       'clipBoard' => 1
                    ]
                ]),
            'RecordListSingleTableCopyToClipboard'
        ];
        yield 'With special route identifier' => [
            (new ShortcutButton())
                ->setRouteIdentifier('record_edit')
                ->setDisplayName('Edit record')
                ->setCopyUrlToClipboard(false),
            'SpecialRouteIdentifier'
        ];
        yield 'With special route identifier and arguments' => [
            (new ShortcutButton())
                ->setRouteIdentifier('record_edit')
                ->setDisplayName('Edit record')
                ->setCopyUrlToClipboard(false)
                ->setArguments([
                    'id' => 123,
                    'edit' => [
                        'pages' => [
                            123 => 'edit',
                        ],
                        'overrideVals' => [
                            'pages' => [
                                'sys_language_uid' => 1
                            ]
                        ]
                    ],
                    'returnUrl' => 'some/url'
                ]),
            'SpecialRouteIdentifierWithArguments'
        ];
        yield 'With special route identifier and arguments - copyToClipboard' => [
            (new ShortcutButton())
                ->setRouteIdentifier('record_edit')
                ->setDisplayName('Edit record')
                ->setArguments([
                    'id' => 123,
                    'edit' => [
                        'pages' => [
                            123 => 'edit',
                        ],
                        'overrideVals' => [
                            'pages' => [
                                'sys_language_uid' => 1
                            ]
                        ]
                    ],
                    'returnUrl' => 'some/url'
                ]),
            'SpecialRouteIdentifierWithArgumentsCopyToClipboard'
        ];
        // @todo Below is deprecated functionality which only provides backwards compatibility for v11. Remove in v12!
        yield 'With special route path' => [
            (new ShortcutButton())
                ->setRouteIdentifier('/record/edit')
                ->setDisplayName('Edit record')
                ->setCopyUrlToClipboard(false),
            'SpecialRouteIdentifier'
        ];
        // @todo Below is deprecated functionality which only provides backwards compatibility for v11. Remove in v12!
        yield 'With special route path as Argument' => [
            (new ShortcutButton())
                ->setArguments(['route' => '/record/edit'])
                ->setDisplayName('Edit record')
                ->setCopyUrlToClipboard(false),
            'SpecialRouteIdentifier'
        ];
    }

    /**
     * Normalizes spaces for comparing markup.
     * + `    <` will be `<` (removing leading spaces before `<` on a line)
     * + `    href=""` will be ` href=""` (reducing multiple leading spaces to just one space)
     * + `\n</span>\n</span>` will be `</span></span>` (removing all vertical spaces - like new lines)
     *
     * @param string $html
     * @return string
     */
    private function normalizeSpaces(string $html): string
    {
        return preg_replace(
            ['/^\s+(?=<)/m', '/^\s+(?!<)/m', '/\v+/'],
            ['', ' ', ''],
            $html
        );
    }
}
