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
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\FunctionCallMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FunctionCallMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hitsFromFixtureAreFound()
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/FunctionCallMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'debugBegin' => [
                'numberOfMandatoryArguments' => 0,
                'maximumNumberOfArguments' => 0,
                'restFiles' => [
                    'Breaking-37180-RemovedExtDirectDebugAndGLOBALSerror.rst',
                ],
            ],
        ];
        $subject = new FunctionCallMatcher($configuration);
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
     * @test
     */
    public function matchIsIgnoredIfIgnoreFileIsSet()
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
        debugBegin();
    }
}
EOC;

        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $statements = $parser->parse($phpCode);

        $traverser = new NodeTraverser();
        $configuration = [
            'debugBegin' => [
                'numberOfMandatoryArguments' => 0,
                'maximumNumberOfArguments' => 0,
                'restFiles' => [
                    'Breaking-37180-RemovedExtDirectDebugAndGLOBALSerror.rst',
                ],
            ],
        ];
        $subject = new FunctionCallMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        self::assertEmpty($subject->getMatches());
    }
}
