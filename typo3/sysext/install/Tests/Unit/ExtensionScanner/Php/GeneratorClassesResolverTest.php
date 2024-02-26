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

use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractCoreMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GeneratorClassesResolverTest extends UnitTestCase
{
    #[Test]
    public function visitorCreatesFullyQualifiedNameFromStringArgumentInMakeInstance(): void
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Does\\Not\\Exist');
EOC;
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        $node = $statements[0]->expr ?? null;
        $argValue = $node->args[0]->value ?? null;
        self::assertInstanceOf(StaticCall::class, $node);
        self::assertInstanceOf(ClassConstFetch::class, $argValue);
        self::assertInstanceOf(FullyQualified::class, $argValue->class);
        self::assertEquals(['TYPO3', 'CMS', 'Does', 'Not', 'Exist'], $argValue->class->getParts());
        self::assertInstanceOf(New_::class, $node->getAttribute(AbstractCoreMatcher::NODE_RESOLVED_AS));
    }

    #[Test]
    public function visitorDoesNotTransformDynamicallyCreatesFullyQualifiedNameFromStringArgumentInMakeInstance(): void
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Does\\Not\\' . $foo);
EOC;
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        $argValue = $statements[0]->expr->args[0]->value ?? null;
        // the fixture source above is a binary concatenation
        self::assertInstanceOf(Concat::class, $argValue);
        self::assertNotInstanceOf(FullyQualified::class, $argValue->class ?? null);
    }
}
