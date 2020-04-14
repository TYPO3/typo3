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

use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class GeneratorClassesResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function visitorCreatesFullyQualifiedNameFromStringArgumentInMakeInstance()
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Does\\Not\\Exist');
EOC;
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $statements = $parser->parse($phpCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        self::assertInstanceOf(FullyQualified::class, $statements[0]->expr->args[0]->value);
        self::assertEquals(['TYPO3', 'CMS', 'Does', 'Not', 'Exist'], $statements[0]->expr->args[0]->value->parts);
    }

    /**
     * @test
     */
    public function visitorDoesNotTransformDynamicallyCreatesFullyQualifiedNameFromStringArgumentInMakeInstance()
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Does\\Not\\' . $foo);
EOC;
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $statements = $parser->parse($phpCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        self::assertNotInstanceOf(FullyQualified::class, $statements[0]->expr->args[0]->value);
    }
}
