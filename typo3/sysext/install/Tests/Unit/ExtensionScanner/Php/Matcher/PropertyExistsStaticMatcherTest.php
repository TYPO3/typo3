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
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyExistsStaticMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PropertyExistsStaticMatcherTest extends UnitTestCase
{
    public static function hitsFromFixturesAreFoundDataProvider(): array
    {
        return [
            'fixture1' => [
                __DIR__ . '/Fixtures/PropertyExistsStaticMatcherFixture1.php',
                [22],
            ],
            'fixture2' => [
                __DIR__ . '/Fixtures/PropertyExistsStaticMatcherFixture2.php',
                [],
            ],
            'fixture3' => [
                __DIR__ . '/Fixtures/PropertyExistsStaticMatcherFixture3.php',
                [],
            ],
            'fixture4' => [
                __DIR__ . '/Fixtures/PropertyExistsStaticMatcherFixture4.php',
                [],
            ],
            'fixture5' => [
                __DIR__ . '/Fixtures/PropertyExistsStaticMatcherFixture5.php',
                [],
            ],
        ];
    }

    #[DataProvider('hitsFromFixturesAreFoundDataProvider')]
    #[Test]
    public function hitsFromFixturesAreFound(string $fixtureFile, array $expectedHitLineNumbers): void
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'iAmAMatch' => [
                'restFiles' => [
                    'Breaking-12345-DeprecateFoo.rst',
                ],
            ],
        ];
        $subject = new PropertyExistsStaticMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $hit) {
            $actualHitLineNumbers[] = $hit['line'];
        }
        self::assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }
}
