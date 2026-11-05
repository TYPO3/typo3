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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Visitor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractCoreMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Visitor\GeneratorClassesResolver;
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
        self::assertIsArray($statements, 'Code could not be parsed.');
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        $statement = $statements[0] ?? null;
        self::assertInstanceOf(Expression::class, $statement);
        $node = $statement->expr;
        self::assertInstanceOf(StaticCall::class, $node);
        $firstArg = $node->args[0] ?? null;
        $argValue = $firstArg instanceof Arg ? $firstArg->value : null;
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
        self::assertIsArray($statements, 'Code could not be parsed.');
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        $statement = $statements[0] ?? null;
        self::assertInstanceOf(Expression::class, $statement);
        $node = $statement->expr;
        self::assertInstanceOf(StaticCall::class, $node);
        $firstArg = $node->args[0] ?? null;
        $argValue = $firstArg instanceof Arg ? $firstArg->value : null;
        // the fixture source above is a binary concatenation
        self::assertInstanceOf(Concat::class, $argValue);
        self::assertNull($node->getAttribute(AbstractCoreMatcher::NODE_RESOLVED_AS));
    }

    #[Test]
    public function visitorDoesNotTransformStaticCallWithDynamicMethodName(): void
    {
        $phpCode = <<<'EOC'
<?php
\TYPO3\CMS\Core\Utility\GeneralUtility::$makeInstance('TYPO3\\CMS\\Does\\Not\\Exist');
EOC;
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        $statements = $parser->parse($phpCode);
        self::assertIsArray($statements, 'Code could not be parsed.');
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new GeneratorClassesResolver());
        $statements = $traverser->traverse($statements);
        $statement = $statements[0] ?? null;
        self::assertInstanceOf(Expression::class, $statement);
        $node = $statement->expr;
        self::assertInstanceOf(StaticCall::class, $node);
        self::assertInstanceOf(Variable::class, $node->name);
        $firstArg = $node->args[0] ?? null;
        $argValue = $firstArg instanceof Arg ? $firstArg->value : null;
        self::assertInstanceOf(String_::class, $argValue, 'Argument must not be substituted.');
        self::assertNull($node->getAttribute(AbstractCoreMatcher::NODE_RESOLVED_AS));
    }
}
