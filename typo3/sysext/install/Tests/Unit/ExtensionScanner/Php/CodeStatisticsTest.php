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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use TYPO3\CMS\Install\ExtensionScanner\Php\CodeStatistics;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CodeStatisticsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function enterNodeSumsStatistics()
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $fixtureFile = __DIR__ . '/Fixtures/CodeStatisticsFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $subject = new CodeStatistics();
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        self::assertTrue($subject->isFileIgnored());
        self::assertEquals(2, $subject->getNumberOfIgnoredLines());
        self::assertEquals(6, $subject->getNumberOfEffectiveCodeLines());
    }
}
