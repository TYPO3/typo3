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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredStaticMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MethodArgumentRequiredStaticMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/MethodArgumentRequiredStaticMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent' => [
                'numberOfMandatoryArguments' => 3,
                'maximumNumberOfArguments' => 3,
                'restFiles' => [
                    'Breaking-82899-MoreRestrictingChecksForAPIMethodsInExtensionManagementUtility.rst',
                ],
            ],
        ];
        $subject = new MethodArgumentRequiredStaticMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            30,
            32,
            34,
        ];
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $hit) {
            $actualHitLineNumbers[] = $hit['line'];
        }
        self::assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }

    /**
     * @return array
     */
    public function matchesReturnsExpectedRestFilesDataProvider(): array
    {
        return [
            'two rest candidates with same number of arguments' => [
                [
                    'Foo::aMethod' => [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'Bar-1.rst',
                            'Bar-2.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod();',
                [
                    0 => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                            'Bar-1.rst',
                            'Bar-2.rst',
                        ],
                    ],
                ],
            ],
            'three candidates, first and second hits' => [
                [
                    'Foo::aMethod' => [
                        'numberOfMandatoryArguments' => 3,
                        'maximumNumberOfArguments' => 3,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'numberOfMandatoryArguments' => 3,
                        'maximumNumberOfArguments' => 3,
                        'restFiles' => [
                            'Bar-1.rst',
                        ],
                    ],
                    'FooBar::aMethod' => [
                        'numberOfMandatoryArguments' => 2,
                        'maximumNumberOfArguments' => 3,
                        'restFiles' => [
                            'FooBar-1.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod(\'foo\', \'bar\');',
                [
                    0 => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Bar-1.rst',
                        ],
                    ],
                ],
            ],
            'one candidate, does not hit, enough arguments given' => [
                [
                    'Foo::aMethod' => [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod(\'foo\');',
                [], // no hit
            ],
            'no match, method call using argument unpacking' => [
                [
                    'Foo::aMethod' => [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                ],
                '<?php
                $args = [\'arg1\', \'arg2\', \'arg3\'];
                $someVar::aMethod(...$args);',
                [],
            ],
            'double linked .rst file is returned only once' => [
                [
                    'Foo::aMethod' => [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod();',
                [
                    0 => [
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                ],
            ],
       ];
    }

    /**
     * @test
     * @dataProvider matchesReturnsExpectedRestFilesDataProvider
     * @param array $configuration
     * @param string $phpCode
     * @param array $expected
     */
    public function matchesReturnsExpectedRestFiles(array $configuration, string $phpCode, array $expected)
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $statements = $parser->parse($phpCode);

        $subject = new MethodArgumentRequiredStaticMatcher($configuration);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $result = $subject->getMatches();
        if (isset($expected[0], $result[0])) {
            self::assertEquals($expected[0]['restFiles'], $result[0]['restFiles']);
        } else {
            self::assertEquals($expected, $result);
        }
    }
}
