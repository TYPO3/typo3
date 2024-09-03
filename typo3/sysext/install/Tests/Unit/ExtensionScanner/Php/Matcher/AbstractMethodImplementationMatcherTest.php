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
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractMethodImplementationMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractMethodImplementationMatcherTest extends UnitTestCase
{
    #[Test]
    public function hitsFromFixtureAreFound(): void
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $fixtureFile = __DIR__ . '/Fixtures/AbstractMethodImplementationMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\AbstractClassFixture->aNormalMethod' => [
                'restFiles' => [
                    'Breaking-12345-Something.rst',
                ],
            ],
            'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\AbstractClassFixture::aStaticMethod' => [
                'restFiles' => [
                    'Breaking-12345-Something.rst',
                ],
            ],
        ];

        $subject = new AbstractMethodImplementationMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            33,
            36,
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
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $fixtureFile = __DIR__ . '/Fixtures/AbstractMethodImplementationMatcherFixture.php';
        $statements = $parser->parse(str_replace('§extensionScannerIgnoreFile', '@extensionScannerIgnoreFile', file_get_contents($fixtureFile)));

        $traverser = new NodeTraverser();
        $configuration = [
            'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\AbstractClassFixture->aNormalMethod' => [
                'restFiles' => [
                    'Breaking-12345-Something.rst',
                ],
            ],
            'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\AbstractClassFixture::aStaticMethod' => [
                'restFiles' => [
                    'Breaking-12345-Something.rst',
                ],
            ],
        ];

        $subject = new AbstractMethodImplementationMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        self::assertEmpty($subject->getMatches());
    }
}
