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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\CodeEditor;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\CodeEditor\CodeCompletionController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CodeCompletionControllerTest extends FunctionalTestCase
{
    #[DataProvider('setupTypoScriptProvider')]
    #[Test]
    public function loadCompletionsHandlesSetupTypoScript(string $setup, array $checks): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pagesWithSysTemplate.csv');
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_template')
            ->update('sys_template', ['config' => $setup], ['uid' => 1]);

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = new ServerRequest('https://example.com/typo3/ajax/code-editor/codecompletion/load-templates', 'POST')
            ->withParsedBody(['pageId' => 1]);

        $response = $this->get(CodeCompletionController::class)->loadCompletions($request);

        self::assertSame(200, $response->getStatusCode());
        $tree = json_decode((string)$response->getBody(), true);
        self::assertIsArray($tree);
        foreach ($checks as [$mode, $path, $expected]) {
            $node = $tree;
            $parentPath = $path;
            $lastKey = array_pop($parentPath);
            foreach ($parentPath as $key) {
                self::assertArrayHasKey($key, $node);
                $node = $node[$key];
            }
            if ($mode === 'equals') {
                self::assertArrayHasKey($lastKey, $node);
                self::assertSame($expected, $node[$lastKey]);
            } else {
                self::assertArrayNotHasKey($lastKey, $node);
            }
        }
    }

    public static function setupTypoScriptProvider(): \Generator
    {
        yield 'simple value assignment' => [
            'page.title = My Page',
            [['equals', ['page', 'c', 'title'], ['v' => 'My Page']]],
        ];

        // Regression test for the crash reported as forge #84534 / its trailing
        // escaped-dot remainder: a value-assignment node whose name ends in a dot
        // (here a CASE selector key) must be kept as a value ('v') and not be
        // treated as a (non-existing) subtree, which used to throw a TypeError.
        yield 'value assignment with trailing escaped dot (CASE selector)' => [
            "lib.foo = CASE\nlib.foo {\n    Mr\\. = TEXT\n    Mr\\. {\n        value = Hallo\n    }\n}",
            [
                ['equals', ['lib', 'c', 'foo', 'v'], 'CASE'],
                ['equals', ['lib', 'c', 'foo', 'c', 'Mr.'], ['v' => 'TEXT']],
            ],
        ];

        yield 'value assignment with escaped dot in the middle' => [
            'page.meta.geo\.region = DE-RP',
            [['equals', ['page', 'c', 'meta', 'c', 'geo.region'], ['v' => 'DE-RP']]],
        ];

        yield 'mixed dotted meta keys next to a plain one' => [
            "page.meta.title = Hello\npage.meta.geo\.region = DE-RP\npage.meta.foo\. = baz",
            [
                ['equals', ['page', 'c', 'meta', 'c', 'title'], ['v' => 'Hello']],
                ['equals', ['page', 'c', 'meta', 'c', 'geo.region'], ['v' => 'DE-RP']],
                ['equals', ['page', 'c', 'meta', 'c', 'foo.'], ['v' => 'baz']],
            ],
        ];

        // A node carrying both a value and child properties becomes a single
        // completion entry (value under 'v', subtree under 'c').
        yield 'value with child properties' => [
            "page = MY_PAGE\npage.10 = TEXT\npage.10.value = Hello",
            [
                ['equals', ['page', 'v'], 'MY_PAGE'],
                ['equals', ['page', 'c', '10'], ['v' => 'TEXT', 'c' => ['value' => ['v' => 'Hello']]]],
            ],
        ];

        yield 'numeric object keys' => [
            "10 = TEXT\n10.value = Hello",
            [['equals', ['10'], ['v' => 'TEXT', 'c' => ['value' => ['v' => 'Hello']]]]],
        ];

        // Values longer than 20 characters are shortened for the completion popup.
        yield 'value truncated to twenty characters' => [
            'page.meta.long = ' . str_repeat('a', 26),
            [['equals', ['page', 'c', 'meta', 'c', 'long'], ['v' => str_repeat('a', 20)]]],
        ];

        // Empty values are not part of the completion tree.
        yield 'empty value assignment is skipped' => [
            "page = MY_PAGE\npage.ok = fine\npage.foo = ",
            [
                ['equals', ['page', 'v'], 'MY_PAGE'],
                ['equals', ['page', 'c', 'ok'], ['v' => 'fine']],
                ['absent', ['page', 'c', 'foo'], null],
            ],
        ];

        // The copy operator shares the source node (value and children) with the target.
        yield 'copy operator shares subtree' => [
            "lib.nav = TEXT\nlib.footer < lib.nav",
            [
                ['equals', ['lib', 'c', 'nav'], ['v' => 'TEXT']],
                ['equals', ['lib', 'c', 'footer'], ['v' => 'TEXT']],
            ],
        ];

        // Deeply nested extension settings are a common completion target.
        yield 'nested extension settings' => [
            "plugin.tx_news.settings.list = 5\nplugin.tx_news.settings.detail = 42",
            [
                ['equals', ['plugin', 'c', 'tx_news', 'c', 'settings', 'c', 'list'], ['v' => '5']],
                ['equals', ['plugin', 'c', 'tx_news', 'c', 'settings', 'c', 'detail'], ['v' => '42']],
            ],
        ];
    }
}
