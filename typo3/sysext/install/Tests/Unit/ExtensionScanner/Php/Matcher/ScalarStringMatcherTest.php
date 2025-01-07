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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ScalarStringMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ScalarStringMatcherTest extends UnitTestCase
{
    #[Test]
    public function hitsFromFixtureAreFound(): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/ScalarStringMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new GeneratorClassesResolver());

        $configuration = [
            'TYPO3_MODE' => [
                'restFiles' => [
                    'Deprecation-92947-DeprecateTYPO3_MODEAndTYPO3_REQUESTTYPEConstants.rst',
                    'Breaking-96107-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'list_type' => [
                'restFiles' => [
                    'Breaking-105377-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
        ];
        $subject = new ScalarStringMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            28,
            29,
            30,
            31,
            34,
            49,
        ];
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $hit) {
            $actualHitLineNumbers[] = $hit['line'];
        }
        self::assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }

    public static function matchesReturnsExpectedRestFilesDataProvider(): array
    {
        return [
            'a straight match, double quote' => [
                [
                    'TYPO3_MODE' => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                ],
                '<?php
                $foo = "TYPO3_MODE";',
                [
                    0 => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('matchesReturnsExpectedRestFilesDataProvider')]
    #[Test]
    public function matchesReturnsExpectedRestFiles(array $configuration, string $phpCode, array $expected): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $statements = $parser->parse($phpCode);

        $subject = new ScalarStringMatcher($configuration);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $result = $subject->getMatches();
        self::assertSame($expected[0]['restFiles'], $result[0]['restFiles']);
    }
}
