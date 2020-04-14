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
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyProtectedMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PropertyProtectedMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/PropertyProtectedMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'TYPO3\CMS\Core\DataHandling\DataHandler->recUpdateAccessCache' => [
                'restFiles' => [
                    'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
                    'Deprecation-79441-ChangeVisibilityInternalCacheDatahandler.rst',
                ],
            ],
        ];
        $subject = new PropertyProtectedMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            28,
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
    public function matchesReturnsExpectedRestFilesDataProvider()
    {
        return [
            'two candidates' => [
                [
                    'Foo->aProperty' => [
                        'restFiles' => [
                            'Foo-1.rst',
                            'Foo-2.rst',
                        ],
                    ],
                    'Bar->aProperty' => [
                        'restFiles' => [
                            'Bar-1.rst',
                            'Bar-2.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar->aProperty;',
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
                    'Foo->aProperty' => [
                        'unusedArgumentNumbers' => [ 1 ],
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                    'Bar->aProperty' => [
                        'unusedArgumentNumbers' => [ 1 ],
                        'restFiles' => [
                            'aRest.rst',
                        ],
                    ],
                ],
                '<?php
                $someVar->aProperty;',
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
     */
    public function matchesReturnsExpectedRestFiles(array $configuration, string $phpCode, array $expected)
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $statements = $parser->parse($phpCode);

        $subject = new PropertyProtectedMatcher($configuration);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        $result = $subject->getMatches();
        self::assertEquals($expected[0]['restFiles'], $result[0]['restFiles']);
    }
}
