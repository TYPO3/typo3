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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration\Parser;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\ConditionMatcherInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageTsConfigParserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invalidCacheAlwaysExecutesMatcher(): void
    {
        $input = 'mod.web_layout = disabled';
        $expectedParsedTsConfig = ['mod' => ['web_layout' => 'disabled']];
        $matcherMock = $this->createMock(ConditionMatcherInterface::class);
        $typoScriptParserMock = $this->createMock(TypoScriptParser::class);
        $parseClosure = function () use ($expectedParsedTsConfig) {
            /** @var TypoScriptParser $this */
            $this->setup = $expectedParsedTsConfig;
        };
        $typoScriptParserMock->expects(self::once())->method('parse')->with($input, $matcherMock)->willReturnCallback(\Closure::bind($parseClosure, $typoScriptParserMock));
        $cache = new NullFrontend('runtime');
        $subject = new PageTsConfigParser(
            $typoScriptParserMock,
            $cache
        );
        $parsedTsConfig = $subject->parse($input, $matcherMock);
        self::assertEquals($expectedParsedTsConfig, $parsedTsConfig);
    }

    /**
     * @test
     */
    public function cachedHitOnlyExecutesMatcher(): void
    {
        $cachedSection = 'mod.web_layout = disabled';
        $input = 'mod.web_layout = disabled';
        $expectedParsedTsConfig = ['mod' => ['web_layout' => 'disabled']];
        $matcherMock = $this->createMock(ConditionMatcherInterface::class);
        $matcherMock->expects(self::once())->method('match')->with($cachedSection)->willReturn(true);
        $typoScriptParserMock = $this->createMock(TypoScriptParser::class);
        $typoScriptParserMock->expects(self::never())->method('parse')->with($input, $matcherMock);
        $cache = new VariableFrontend('runtime', new TransientMemoryBackend('nothing', ['logger' => new NullLogger()]));
        $cache->set(
            '1d0a3029a36cc56a82bfdb0642fcd912',
            [
            0 => [
                'sections' => [$cachedSection],
                'TSconfig' => ['mod' => ['web_layout' => 'disabled']],
            ],
            1 => 'fb3c41ea55f42a993fc143a54e09bbdd', ]
        );
        $subject = new PageTsConfigParser(
            $typoScriptParserMock,
            $cache
        );
        $parsedTsConfig = $subject->parse($input, $matcherMock);
        self::assertEquals($expectedParsedTsConfig, $parsedTsConfig);
    }

    /**
     * @test
     */
    public function parseReplacesSiteSettings(): void
    {
        $input = 'mod.web_layout = {$numberedThings.1}' . "\n" .
                 'mod.no-replace = {$styles.content}' . "\n" .
                 'mod.content = {$styles.content.loginform.pid}';
        $expectedParsedTsConfig = [
            'mod.' => [
                'web_layout' => 'foo',
                'no-replace' => '{$styles.content}',
                'content' => '123',
            ],
        ];

        $matcherMock = $this->createMock(ConditionMatcherInterface::class);
        $cache = new NullFrontend('runtime');
        $site = new Site(
            'dummy',
            13,
            [
                'base' => 'https://example.com',
            ],
            new SiteSettings(
                [
                    'random' => 'value',
                    'styles' => [
                        'content' => [
                            'loginform' => [
                                'pid' => 123,
                            ],
                        ],
                    ],
                    'numberedThings' => [
                        1 => 'foo',
                        99 => 'bar',
                    ],
                ]
            )
        );
        $subject = new PageTsConfigParser(
            new TypoScriptParser(),
            $cache
        );
        $parsedTsConfig = $subject->parse($input, $matcherMock, $site);
        self::assertEquals($expectedParsedTsConfig, $parsedTsConfig);
    }

    /**
     * @test
     */
    public function parseReplacesSiteSettingsWithMultipleSitesAndCache(): void
    {
        $input = 'mod.web_layout = {$numberedThings.1}';
        $expectedParsedTsConfig = [
            'mod.' => [
                'web_layout' => 'foo',
            ],
        ];
        $expectedParsedTsConfig2 = [
            'mod.' => [
                'web_layout' => 'bar',
            ],
        ];

        $matcherMock = $this->createMock(ConditionMatcherInterface::class);
        $cache = new VariableFrontend('runtime', new TransientMemoryBackend('nothing', ['logger' => new NullLogger()]));

        $site = new Site('dummy', 13, [
            'base' => 'https://example.com',
            'settings' => [
                'numberedThings' => [
                    1 => 'foo',
                ],
            ],
        ]);
        $subject = new PageTsConfigParser(
            new TypoScriptParser(),
            $cache
        );
        $parsedTsConfig = $subject->parse($input, $matcherMock, $site);
        self::assertEquals($expectedParsedTsConfig, $parsedTsConfig);

        $site2 = new Site('dummy2', 14, [
            'base' => 'https://example2.com',
            'settings' => [
                'numberedThings' => [
                    1 => 'bar',
                ],
            ],
        ]);
        $subject2 = new PageTsConfigParser(
            new TypoScriptParser(),
            $cache
        );
        $parsedTsConfig2 = $subject2->parse($input, $matcherMock, $site2);
        self::assertEquals($expectedParsedTsConfig2, $parsedTsConfig2);
    }
}
