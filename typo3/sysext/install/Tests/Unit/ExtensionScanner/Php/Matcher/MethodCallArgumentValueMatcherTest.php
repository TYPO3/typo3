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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallArgumentValueMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MethodCallArgumentValueMatcherTest extends UnitTestCase
{
    #[Test]
    public function hitsFromFixtureAreFound(): void
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $fixtureFile = __DIR__ . '/Fixtures/MethodCallArgumentValueMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'TYPO3\CMS\Backend\Clipboard\Clipboard->confirmMsgString' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 'argOld',
                    ],
                ],
                'restFiles' => [
                    'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\Backend\Clipboard\Clipboard->confirmMsgInt' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 13,
                    ],
                ],
                'restFiles' => [
                    'Breaking-80701-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\Backend\Clipboard\Clipboard->confirmMsgFloat' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 13.37,
                    ],
                ],
                'restFiles' => [
                    'Breaking-80702-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\GeneralUtility::confirmMsgString' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 'argOld',
                    ],
                ],
                'restFiles' => [
                    'Breaking-80710-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\GeneralUtility::confirmMsgInt' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 13,
                    ],
                ],
                'restFiles' => [
                    'Breaking-80711-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\GeneralUtility::confirmMsgFloat' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 13.37,
                    ],
                ],
                'restFiles' => [
                    'Breaking-80712-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\GeneralUtility::confirmMsgMultiple' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 'string',
                    ],
                    [
                        'argumentIndex' => 1,
                        'argumentValue' => 42,
                    ],
                    [
                        'argumentIndex' => 2,
                        'argumentValue' => 13.37,
                    ],
                ],
                'restFiles' => [
                    'Breaking-80712-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
            'TYPO3\CMS\GeneralUtility::confirmMsgMultipleNotAllMatched' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 'string',
                    ],
                    [
                        'argumentIndex' => 1,
                        'argumentValue' => 99,
                    ],
                    [
                        'argumentIndex' => 2,
                        'argumentValue' => 12.34,
                    ],
                ],
                'restFiles' => [
                    'Breaking-80712-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
        ];
        $subject = new MethodCallArgumentValueMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            28,
            31,
            34,
            37,
            40,
            43,
            48,
            51,
            54,
            57,
            60,
            63,
            66,
        ];
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $hit) {
            $actualHitLineNumbers[] = $hit['line'];
        }
        self::assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }

    #[Test]
    public function matchIsIgnoredIfIgnoreFileIsSet(): void
    {
        $phpCode = <<<'EOC'
<?php
/**
 * Some comment
 * @extensionScannerIgnoreFile This file is ignored
 */
class foo
{
    public function aTest()
    {
        // This valid match should not match since the entire file is ignored
        $foo->confirmMsg('argOld');
    }
}
EOC;

        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);

        $traverser = new NodeTraverser();
        $configuration = [
            'TYPO3\CMS\Backend\Clipboard\Clipboard->confirmMsg' => [
                'argumentMatches' => [
                    [
                        'argumentIndex' => 0,
                        'argumentValue' => 'argOld',
                    ],
                ],
                'restFiles' => [
                    'Breaking-80700-DeprecatedFunctionalityRemoved.rst',
                ],
            ],
        ];
        $subject = new MethodCallArgumentValueMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        self::assertEmpty($subject->getMatches());
    }
}
