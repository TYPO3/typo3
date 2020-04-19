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
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstructorArgumentMatcher;
use TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\Subject;

/**
 * Test case
 */
class ConstructorArgumentMatcherTest extends TestCase
{
    public function hitsFromFixtureAreFoundDataProvider(): array
    {
        $defaults = [
            'restFiles' => [
                'Breaking-87193-DeprecatedFunctionalityRemoved.rst',
            ],
        ];

        return [
            'required' => [
                [
                    'required' => array_merge($defaults, [
                        'numberOfMandatoryArguments' => 4,
                    ]),
                ],
                [34, 35, 36, 37, 44, 45],
            ],
            'dropped' => [
                [
                    'dropped' => array_merge($defaults, [
                        'maximumNumberOfArguments' => 2,
                    ]),
                ],
                [34, 35, 36, 37, 44, 45],
            ],
            'called' => [
                [
                    'called' => array_merge($defaults, [
                        'numberOfMandatoryArguments' => 1,
                        'maximumNumberOfArguments' => 3,
                    ]),
                ],
                [34, 35, 36, 37, 44, 45],
            ],
            'unused' => [
                [
                    'unused' => array_merge($defaults, [
                        'unusedArgumentNumbers' => [2],
                    ]),
                ],
                [34, 35, 36, 37],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @param array $expectation
     *
     * @test
     * @dataProvider hitsFromFixtureAreFoundDataProvider
     */
    public function hitsFromFixtureAreFound(array $configuration, array $expectation)
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/ConstructorArgumentMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        // first process completely to resolve fully qualified names of arguments
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        // second process to actually work on the pre-resolved statements
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $subject = new ConstructorArgumentMatcher([
            Subject::class => $configuration
        ]);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $lineNumbers = array_column($subject->getMatches(), 'line');
        self::assertEquals($expectation, $lineNumbers);
    }
}
