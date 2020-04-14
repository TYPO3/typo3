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
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedStaticMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MethodArgumentDroppedStaticMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/MethodArgumentDroppedStaticMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName' => [
                'maximumNumberOfArguments' => 1,
                'restFiles' => [
                    'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
                    'Deprecation-73516-VariousGeneralUtilityMethods.rst'
                ],
            ],
        ];
        $subject = new MethodArgumentDroppedStaticMatcher($configuration);
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
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => [
                            'Bar-1.rst',
                            'Bar-2.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod(\'foo\');',
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
            'two candidates, only one hits because second candidate needs one argument' => [
                [
                    'Foo::aMethod' => [
                        'maximumNumberOfArguments' => 2,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'maximumNumberOfArguments' => 3,
                        'restFiles' => [
                            'Bar-1.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod(\'arg1\', \'arg2\', \'arg3\');',
                [
                    0 => [
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                ],
            ],
            'three candidates, first and second hits' => [
                [
                    'Foo::aMethod' => [
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'Bar-1.rst',
                        ],
                    ],
                    'FooBar::aMethod' => [
                        'maximumNumberOfArguments' => 2,
                        'restFiles' => [
                            'FooBar-1.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod(\'arg1\', \'arg2\');',
                [
                    0 => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Bar-1.rst',
                        ],
                    ],
                ],
            ],
            'one candidate, does not hit, not enough arguments given' => [
                [
                    'Foo::aMethod' => [
                        'maximumNumberOfArguments' => 1,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod();',
                [], // no hit
            ],
            'no match, method call using argument unpacking' => [
                [
                    'Foo::aMethod' => [
                        'maximumNumberOfArguments' => 2,
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
            'method call using argument unpacking with more than max number of args given arguments' => [
                [
                    'Foo::aMethod' => [
                        'maximumNumberOfArguments' => 2,
                        'restFiles' => [
                            'Foo-1.rst',
                        ],
                    ],
                ],
                '<?php
                $args1 = [\'arg1\', \'arg2\', \'arg3\'];
                $args2 = [\'arg4\', \'arg5\', \'arg6\'];
                $args3 = [\'arg7\', \'arg8\', \'arg9\'];
                $someVar::aMethod(...$args1, ...$args2, ...$args3);',
                [],
            ],
            'double linked .rst file is returned only once' => [
                [
                    'Foo::aMethod' => [
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                    'Bar::aMethod' => [
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar::aMethod(\'foo\');',
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

        $subject = new MethodArgumentDroppedStaticMatcher($configuration);

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
