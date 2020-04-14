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
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\InterfaceMethodChangedMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class InterfaceMethodChangedMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/InterfaceMethodChangedMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $matcherDefinitions = [
            'like1' => [
                'newNumberOfArguments' => 2,
                'restFiles' => [
                    'Foo-1.rst',
                ],
            ],
            'like2' => [
                'newNumberOfArguments' => 2,
                'restFiles' => [
                    'Foo-2.rst',
                ],
            ],
            'like3' => [
                'newNumberOfArguments' => 2,
                'restFiles' => [
                    'Foo-3.rst',
                ],
            ],
            'like4' => [
                'newNumberOfArguments' => 2,
                'restFiles' => [
                    'Foo-3.rst',
                ],
            ],
        ];
        $subject = new InterfaceMethodChangedMatcher($matcherDefinitions);

        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            32,
            35,
        ];
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $match) {
            $actualHitLineNumbers[] = $match['line'];
        }
        self::assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }
}
