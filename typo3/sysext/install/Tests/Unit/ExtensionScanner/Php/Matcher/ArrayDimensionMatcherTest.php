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
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayDimensionMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ArrayDimensionMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/ArrayDimensionMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            '$GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'maxSessionDataSize\']' => [
                'restFiles' => [
                    'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
                    'Deprecation-70316-FrontendBasketWithRecs.rst',
                ],
            ],
        ];
        $subject = new ArrayDimensionMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            28,
            29,
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
            'two candidates' => [
                [
                    '$foo[\'bar\'][\'findMe\']' => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                    '$foo[\'findMe\']' => [
                        'restFiles' => [
                            'Bar-1.rst',
                            'Bar-2.rst',
                        ],
                    ],
                ],
                '<?php
                $bar = $foo[\'findMe\'];',
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
            'double linked .rst file is returned only once' => [
                [
                    '$foo[\'bar\'][\'findMe\']' => [
                        'unusedArgumentNumbers' => [ 1 ],
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                    '$foo[\'findMe\']' => [
                        'unusedArgumentNumbers' => [ 1 ],
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                ],
                '<?php
                $bar = $foo[\'findMe\'];',
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

        $subject = new ArrayDimensionMatcher($configuration);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $result = $subject->getMatches();
        self::assertEquals($expected[0]['restFiles'], $result[0]['restFiles']);
    }
}
