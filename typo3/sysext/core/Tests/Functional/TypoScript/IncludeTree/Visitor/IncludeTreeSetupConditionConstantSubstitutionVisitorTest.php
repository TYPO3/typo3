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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\FileInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IncludeTreeSetupConditionConstantSubstitutionVisitorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function visitDataProvider(): iterable
    {
        yield 'nothing to substitute, no condition' => [
            [],
            new FileInclude(),
            new FileInclude(),
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, 'foo = foo', 0, 0));
        yield 'nothing to substitute, no constant' => [
            [],
            $inputNode,
            $inputNode,
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, '{$foo} = bar', 0, 0));
        $expectedNode = new ConditionInclude();
        $expectedNode->setConditionToken(new Token(TokenType::T_VALUE, 'fooValue = bar', 0, 0));
        $expectedNode->setOriginalConditionToken(new Token(TokenType::T_VALUE, '{$foo} = bar', 0, 0));
        yield 'single substitution' => [
            ['foo' => 'fooValue'],
            $inputNode,
            $expectedNode,
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, '{$foo} = bar', 0, 0));
        yield 'single substitution, but no match in constants' => [
            ['notFoo' => 'fooValue'],
            $inputNode,
            $inputNode,
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, '{$foo} = {$bar}', 0, 0));
        $expectedNode = new ConditionInclude();
        $expectedNode->setConditionToken(new Token(TokenType::T_VALUE, 'fooValue = barValue', 0, 0));
        $expectedNode->setOriginalConditionToken(new Token(TokenType::T_VALUE, '{$foo} = {$bar}', 0, 0));
        yield 'double substitution' => [
            ['foo' => 'fooValue', 'bar' => 'barValue'],
            $inputNode,
            $expectedNode,
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, '{$foo.bar} = {$foo\.baz}', 0, 0));
        $expectedNode = new ConditionInclude();
        $expectedNode->setConditionToken(new Token(TokenType::T_VALUE, 'barValue = bazValue', 0, 0));
        $expectedNode->setOriginalConditionToken(new Token(TokenType::T_VALUE, '{$foo.bar} = {$foo\.baz}', 0, 0));
        yield 'double substitution with dots and quotes' => [
            ['foo.bar' => 'barValue', 'foo\.baz' => 'bazValue'],
            $inputNode,
            $expectedNode,
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, '{$foo}{$bar} = {$baz}', 0, 0));
        $expectedNode = new ConditionInclude();
        $expectedNode->setConditionToken(new Token(TokenType::T_VALUE, 'fooValuebarValue = bazValue', 0, 0));
        $expectedNode->setOriginalConditionToken(new Token(TokenType::T_VALUE, '{$foo}{$bar} = {$baz}', 0, 0));
        yield 'triple substitution' => [
            ['foo' => 'fooValue', 'bar' => 'barValue', 'baz' => 'bazValue'],
            $inputNode,
            $expectedNode,
        ];

        $inputNode = new ConditionInclude();
        $inputNode->setConditionToken(new Token(TokenType::T_VALUE, '{$foo}{$bar} = {$baz}', 0, 0));
        $expectedNode = new ConditionInclude();
        $expectedNode->setConditionToken(new Token(TokenType::T_VALUE, 'fooValue{$bar} = bazValue', 0, 0));
        $expectedNode->setOriginalConditionToken(new Token(TokenType::T_VALUE, '{$foo}{$bar} = {$baz}', 0, 0));
        yield 'triple substitution, one without match' => [
            ['foo' => 'fooValue', 'baz' => 'bazValue'],
            $inputNode,
            $expectedNode,
        ];
    }

    /**
     * @test
     * @dataProvider visitDataProvider
     */
    public function visit(array $flattenedConstants, IncludeInterface $node, IncludeInterface $expectedNode): void
    {
        $subject = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
        $subject->setFlattenedConstants($flattenedConstants);
        $subject->visitBeforeChildren($node, 0);
        self::assertEquals($expectedNode, $node);
    }
}
