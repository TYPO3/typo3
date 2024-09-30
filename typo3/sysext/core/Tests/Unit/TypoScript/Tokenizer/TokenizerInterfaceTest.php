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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Tokenizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\BlockCloseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\CommentLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionElseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionStopLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\EmptyLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierAssignmentLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierBlockOpenLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierCopyLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierFunctionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierReferenceLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierUnsetLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportOldLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\InvalidLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\ConstantAwareTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierToken;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * This tests LosslessTokenizer, LossyTokenizer and LosslessTokenizer->__toString()
 */
final class TokenizerInterfaceTest extends UnitTestCase
{
    /**
     * @deprecated: Remove INCLUDE_TYPOSCRIPT related cases in v14, search for keyword INCLUDE_TYPOSCRIPT
     */
    public static function tokenizeStringDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                new LineStream(),
                new LineStream(),
            ],
            'whitespaces' => [
                '  ',
                (new LineStream())
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_BLANK, '  ', 0, 0))
                        )
                    ),
                new LineStream(),
            ],
            'tabs' => [
                "\t\t",
                (new LineStream())
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                               ->append(new Token(TokenType::T_BLANK, "\t\t", 0, 0))
                        )
                    ),
                new LineStream(),
            ],
            'mixed whitespaces and tabs' => [
                " \t\t  ",
                (new LineStream())
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_BLANK, " \t\t  ", 0, 0))
                        )
                    ),
                new LineStream(),
            ],
            'newline' => [
                "\n",
                (new LineStream())
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 0))
                        )
                    ),
                new LineStream(),
            ],
            'two newline' => [
                "\n\n",
                (new LineStream())
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 0))
                        )
                    )
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 0))
                        )
                    ),
                new LineStream(),
            ],
            'carriage return, newline' => [
                "\r\n",
                (new LineStream())
                    ->append(
                        (new EmptyLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_NEWLINE, "\r\n", 0, 0))
                        )
                    ),
                new LineStream(),
            ],

            'one identifier' => [
                'foo',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier, newline' => [
                "foo\n",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 3))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier, carriage return, newline' => [
                "foo\r\n",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\r\n", 0, 3))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier umlaut' => [
                'föo',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'föo', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier with hash sign recognized as identifier' => [
                'foo#bar',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo#bar', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier with @-sign recognized as identifier' => [
                'foo@bar',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo@bar', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier, hash comment' => [
                'foo # a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 4))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier, doubleslash comment' => [
                'foo // a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 4))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier, multiline comment' => [
                "foo /* a comment\n" .
                "finish = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 6))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'finish = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 17))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 19))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'two identifiers' => [
                'foo.bar',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 3))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 4))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier with backslash' => [
                'foo\bar',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo\bar', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'one identifier, quoted dot' => [
                'foo\.bar',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo.bar', 0, 0))
                            )
                    ),
                new LineStream(),
            ],

            'identifier, assignment, value' => [
                'foo=bar',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 3))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 4))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    ),
            ],
            'identifier, assignment, value umlaut' => [
                'foo=bär',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 3))
                                    ->append(new Token(TokenType::T_VALUE, 'bär', 0, 4))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bär', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bär'))
                            )
                    ),
            ],
            'identifier, assignment, whitespace, value' => [
                'foo = bar',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    ),
            ],
            'identifier, assignment, whitespace, value with < is considered an assignment line, not a reference' => [
                'foo = <bar',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '<bar', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '<bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '<bar'))
                            )
                    ),
            ],
            'identifier with colon, assignment, value with whitespaces' => [
                'foo:bar = fooValue',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo:bar', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 8))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 9))
                                    ->append(new Token(TokenType::T_VALUE, 'fooValue', 0, 10)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo:bar', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'fooValue', 0, 10))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo:bar'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'fooValue'))
                            )
                    ),
            ],
            'identifier is email address, assignment, value' => [
                'foo@example\.com = fooValue',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo@example.com', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 16))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 17))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 18))
                                    ->append(new Token(TokenType::T_VALUE, 'fooValue', 0, 19)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo@example.com', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'fooValue', 0, 19))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo@example.com'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'fooValue'))
                            )
                    ),
            ],
            'identifier, assignment, whitespace, no value' => [
                'foo = ',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ''))
                            )
                    ),
            ],
            'identifier, assignment, value zero with whitespaces' => [
                'foo = 0',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '0', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '0', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '0'))
                            )
                    ),
            ],
            'identifier as number, assignment, value with whitespaces' => [
                '42 = bar',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '42', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 2))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 3))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 5)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '42', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '42'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    ),
            ],
            'identifier, identifier as number, assignment, value with whitespaces' => [
                'foo.42 = bar',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 3))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '42', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 9)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '42', 0, 4))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 9))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '42'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    ),
            ],
            'identifier, assignment, complex value' => [
                'foo = LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title'))
                            )
                    ),
            ],
            'identifier, assignment, value with tabs' => [
                "foo\t=\tbar",
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    ),
            ],
            'identifier, assignment, value with multi whitespaces' => [
                'foo = bar baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar baz', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with hash' => [
                'foo = bar # baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar # baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar # baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar # baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with doubleslash' => [
                'foo = bar // baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar // baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar // baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar // baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with slash star' => [
                'foo = bar /* baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar /* baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar /* baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar /* baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with star slash' => [
                'foo = bar */ baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar */ baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar */ baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar */ baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with parenthesis' => [
                'foo = bar ( baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar ( baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar ( baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar ( baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with close parenthesis' => [
                'foo = bar ) baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar ) baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar ) baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar ) baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with curly brace' => [
                'foo = bar { baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar { baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar { baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar { baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with curly close brace' => [
                'foo = bar } baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar } baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar } baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar } baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with bracket' => [
                'foo = bar [ baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar [ baz', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar [ baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar [ baz'))
                            )
                    ),
            ],
            'identifier, assignment, value with close bracket' => [
                'foo = bar ] baz',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar ] baz', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar ] baz', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar ] baz'))
                            )
                    ),
            ],

            'identifier, assignment multi line, no value' => [
                'foo()',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 4))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, assignment multi line, value' => [
                'foo(bar)',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 3))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 4))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 7))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with whitespaces' => [
                'foo ( bar )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' bar ', 0, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 10))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar ', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar '))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with tab' => [
                "foo\t( bar )",
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' bar ', 0, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 10))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar ', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar '))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with multi whitespaces' => [
                'foo ( bar baz )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' bar baz ', 0, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 14))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar baz ', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar baz '))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with hash' => [
                'foo ( bar # baz )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' bar # baz ', 0, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 16))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar # baz ', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar # baz '))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with doubleslash' => [
                'foo ( bar // baz )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' bar // baz ', 0, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar // baz ', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar // baz '))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with slash star' => [
                'foo ( bar /* baz )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' bar /* baz ', 0, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar /* baz ', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' bar /* baz '))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with multiple lines' => [
                "foo (\n"
                . "    bar\n"
                . "    baz\n"
                . ')',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, '    baz', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 7))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 3, 0)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, '    baz', 2, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    baz'))
                            )
                    ),
            ],
            'identifier, assignment multi line, fake comment but value, newline' => [
                "foo ( # not a comment\n",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' # not a comment', 0, 5))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 21)),
                            )
                    ),
                new LineStream(),
            ],
            'identifier, assignment multi line, value with multiple lines, mixed line break types' => [
                "foo (\n"
                . "    bar\n"
                . "    baz\r\n"
                . ')',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, '    baz', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\r\n", 2, 7))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 3, 0)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, '    baz', 2, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    baz'))
                            )
                    ),
            ],
            'identifier, assignment multi line, value with multiple lines, first value after ( already, mixed line break types' => [
                "foo ( what is this\n"
                . "    bar\n"
                . "    baz\r\n"
                . ')',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' what is this', 0, 5))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 18))
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, '    baz', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\r\n", 2, 7))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 3, 0)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' what is this', 0, 5))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 18))
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, '    baz', 2, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' what is this'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    bar'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    baz'))
                            )
                    ),
            ],
            'identifier, assignment multi line, value, missing closing close at end of stream' => [
                "foo (\n" .
                "    bar\n",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, assignment multi line, multiline value has offending opening round bracket' => [
                "foo (\n" .
                "    bar(\n" .
                ')',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    bar(', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 8))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 2, 0)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar(', 1, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar('))
                            )
                    ),
            ],
            'identifier, assignment multi line, indented close still closes' => [
                "foo (\n" .
                "    bar\n" .
                '    )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 7))
                                    ->append(new Token(TokenType::T_BLANK, '    ', 2, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 2, 4)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar', 1, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar'))
                            )
                    ),
            ],
            'identifier, assignment multi line, multiline value has opening round bracket with pseudo function assign' => [
                "foo (\n" .
                "    bar := baz()\n" .
                "    stillWithinAssignment\n" .
                ")\n" .
                'bar',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    bar := baz()', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 16))
                                    ->append(new Token(TokenType::T_VALUE, '    stillWithinAssignment', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 25))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 3, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 3, 1))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar := baz()', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 16))
                                    ->append(new Token(TokenType::T_VALUE, '    stillWithinAssignment', 2, 0))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 4, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    bar := baz()'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    stillWithinAssignment'))
                            )
                    ),
            ],

            'identifier, reference, identifier' => [
                'foo=<bar',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 3))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 5))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    ),
            ],
            'identifier, reference, relative identifier' => [
                'foo=<.bar',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 3))
                                    ->append(new token(TokenType::T_DOT, '.', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, reference, whitespace, identifiers' => [
                'foo =< bar1.bar2',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, reference, whitespace, relative identifiers' => [
                'foo =< .bar1.bar2',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 8))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 12))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 13))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 8))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 13))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, reference, identifiers, next line' => [
                "foo =< bar1.bar2\n" .
                'someIdentifier',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 16))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 1, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, reference, identifiers with tabs' => [
                "foo\t=< bar1.bar2",
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, reference, identifiers, hash comment' => [
                'foo =< bar1.bar2 # a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 16))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, reference, relative identifiers, hash comment' => [
                'foo =< .bar1.bar2 # a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 8))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 12))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 13))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 17))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 18))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 8))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 13))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, reference, identifiers, broken comment' => [
                'foo =< bar1.bar2 a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 16))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, reference, identifiers, doubleslash comment' => [
                'foo =< bar1.bar2 // a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 16))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, reference, identifiers, multiline comment' => [
                "foo =< bar1.bar2 /* a comment\n" .
                "finish = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 16))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 17))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 19))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 29))
                                    ->append(new Token(TokenType::T_VALUE, 'finish = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 17))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 19))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'identifier, reference, hash comment' => [
                'foo =< # a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 7))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, reference, doubleslash comment' => [
                'foo =< // a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 7))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, reference, multiline comment' => [
                'foo =< /* a comment */',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 7))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment ', 0, 9))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 20))
                            )
                    ),
                new LineStream(),
            ],

            'identifier, copy, identifier' => [
                'foo<bar',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 3))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 4))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    ),
            ],
            'identifier, copy, relative identifier' => [
                'foo<.bar',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 3))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 4))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 5))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, copy, identifiers with whitespaces' => [
                'foo < bar1.bar2',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 6))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 10))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 11))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 11))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, copy, relative identifiers with whitespaces' => [
                'foo < .bar1.bar2',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, copy, identifiers with tabs' => [
                "foo\t<\tbar1.bar2",
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 6))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 10))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 11))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 11))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                            )
                    ),
            ],
            'identifier, copy, relative identifiers with tabs' => [
                "foo\t<\t.bar1.bar2",
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 5))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 11))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1', 0, 7))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2', 0, 12))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar1'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar2'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, copy, identifier, hash comment' => [
                'foo < bar # a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 9))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 10))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    ),
            ],
            'identifier, copy, relative identifier, hash comment' => [
                'foo < .bar # a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_DOT, '.', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 10))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 11))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 7))
                                    ->setRelative()
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                                    ->setRelative()
                            )
                    ),
            ],
            'identifier, copy, identifier, doubleslash comment' => [
                'foo < bar // a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 9))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 10))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    ),
            ],
            'identifier, copy, identifier, multiline comment' => [
                "foo < bar /* a comment\n" .
                "endOf = comment*/\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 9))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 10))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 12))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 22))
                                    ->append(new Token(TokenType::T_VALUE, 'endOf = comment', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 15))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'identifier, copy, identifier, multiline comment one line' => [
                'foo < bar /* a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 9))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 10))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    ),
            ],
            'identifier, copy, identifier, broken comment recognized as comment' => [
                'foo < bar forced comment',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 9))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'forced comment', 0, 10))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar'))
                            )
                    ),
            ],
            'identifier, copy, hash comment' => [
                'foo < # a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 6))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, copy, doubleslash comment' => [
                'foo < // a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 6))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, copy, multiline comment' => [
                "foo < /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 8))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 18))
                                    ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'identifier, copy, multiline comment one line' => [
                'foo < /* a comment',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 8)),
                            )
                    ),
                new LineStream(),
            ],

            'identifier, unset' => [
                'foo>',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 3)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset with whitespace' => [
                'foo >',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset with tabs' => [
                "foo\t>",
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset, hash line comment' => [
                'foo > # a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset, recognized broken single line comment (no hash)' => [
                'foo > a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset, doubleslash comment' => [
                'foo > // a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 6)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset, multiline comment one line' => [
                'foo > /* a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 8)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, unset, multiline comment' => [
                "foo > /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_UNSET, '>', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 8))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 18))
                                    ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierUnsetLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],

            'identifier, function' => [
                'foo:=',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 3))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, function, newline, identifier' => [
                // broken TS scenario: if there is no function name after := but a line break,
                // next line should end function related recognition and set next token as identifier.
                "foo:=\n" .
                'bar',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 3))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 1, 0))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, function, function name, missing (' => [
                'foo := addToList',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            )
                    ),
                new LineStream(),
            ],
            'identifier, function, function name empty value' => [
                'foo := addToList()',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream()))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream()))
                    ),
            ],
            'identifier, function, function name whitespace value' => [
                'foo := addToList( )',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, ' ', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, ' ', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, ' ')))
                    ),
            ],
            'identifier, function, function name whitespace encapsulated value' => [
                'foo := addToList( bar )',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, ' bar ', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 22))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, ' bar ', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, ' bar ')))
                    ),
            ],
            'identifier, function, function name tabs value' => [
                "foo := addToList(\t)",
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, "\t", 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, "\t", 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, "\t")))
                    ),
            ],
            'identifier, function, function name tabs encapsulated value' => [
                "foo := addToList(\tbar\t)",
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, "\tbar\t", 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 22))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, "\tbar\t", 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, "\tbar\t")))
                    ),
            ],
            'identifier, tabs, function, function name empty value' => [
                "foo\t:=\taddToList()",
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream()))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream()))
                    ),
            ],
            'identifier, function, function name with value' => [
                'foo := addToList(1)',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '1', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1')))
                    ),
            ],
            'identifier, function, function name, value is constant' => [
                'foo := addToList({$some.constant})',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant}', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 33))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant}', 0, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant}'))
                            )
                    ),
            ],
            'identifier, function, function name, value is string with fallback constant' => [
                'foo := addToList({$some.constant ?? $other.constant}, 42)',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant ?? $other.constant}', 0, 17))
                                    ->append(new Token(TokenType::T_VALUE, ', 42', 0, 52))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 56))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant ?? $other.constant}', 0, 17))
                                    ->append(new Token(TokenType::T_VALUE, ', 42', 0, 52))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant ?? $other.constant}'))
                                    ->append(new Token(TokenType::T_VALUE, ', 42'))
                            )
                    ),
            ],
            'identifier, function, function name, value with constants and values' => [
                'foo := addToList(23{$some.constant}{$other.constant}42)',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '23', 0, 17))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant}', 0, 19))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$other.constant}', 0, 35))
                                    ->append(new Token(TokenType::T_VALUE, '42', 0, 52))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 54))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '23', 0, 17))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant}', 0, 19))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$other.constant}', 0, 35))
                                    ->append(new Token(TokenType::T_VALUE, '42', 0, 52))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '23'))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$some.constant}'))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$other.constant}'))
                                    ->append(new Token(TokenType::T_VALUE, '42'))
                            )
                    ),
            ],
            'identifier, function, function name with value, hash comment' => [
                'foo := addToList(1) # a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '1', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 19))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 20))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1')))
                    ),
            ],
            'identifier, function, function name with value, broken forced comment' => [
                'foo := addToList(1) a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '1', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 19))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 20))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1')))
                    ),
            ],
            'identifier, function, function name with value, doubleslash comment' => [
                'foo := addToList(1) // a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '1', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 19))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 20)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1')))
                    ),
            ],
            'identifier, function, function name with value, multiline comment' => [
                'foo := addToList(1) /* a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '1', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 19))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 20))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 22)),
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1', 0, 17)))
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1')))
                    ),
            ],
            'identifier, function, function name with value, multiline comment, identifier' => [
                "foo := addToList(1) /* a comment\n" .
                "continued = comment\n" .
                "finish comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', 0, 16))
                                    ->append(new Token(TokenType::T_VALUE, '1', 0, 17))
                                    ->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', 0, 18))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 19))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 20))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 22))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 32))
                                    ->append(new Token(TokenType::T_VALUE, 'continued = comment', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 19))
                                    ->append(new Token(TokenType::T_VALUE, 'finish comment ', 2, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 2, 15))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList', 0, 7))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1', 0, 17)))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 3, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 3, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 3, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 3, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 3, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 3, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 3, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierFunctionLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setFunctionNameToken(new Token(TokenType::T_FUNCTION_NAME, 'addToList'))
                            ->setFunctionValueTokenStream((new TokenStream())->append(new Token(TokenType::T_VALUE, '1')))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],

            'hash' => [
                '#',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '#', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'hash comment' => [
                '#foo',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '#foo', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'whitespace, hash comment' => [
                ' #foo',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '#foo', 0, 1)),
                        )
                    ),
                new LineStream(),
            ],
            'hash comment with whitespaces' => [
                '# foo bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# foo bar', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'hash comment with tabs' => [
                "#\tfoo\tbar",
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, "#\tfoo\tbar", 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'hash comment with multi hash chars' => [
                '##foo bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '##foo bar', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'hash comment with multi hash chars and whitespaces' => [
                '# # foo bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# # foo bar', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'hash comment, new line, comment' => [
                "# foo\n"
                . '# bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# foo', 0, 0))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                        )
                    )->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# bar', 1, 0))
                        )
                    ),
                new LineStream(),
            ],
            'hash comment, new line, identifier, new line, comment' => [
                "# foo\n"
                . "bar\n"
                . '# baz',
                (new LineStream())
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# foo', 0, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 3))
                            )
                    )
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# baz', 2, 0))
                            )
                    ),
                new LineStream(),
            ],

            'doubleslash' => [
                '//',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '//', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment' => [
                '//foo',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '//foo', 0, 0))
                        )
                    ),
                new LineStream(),
            ],
            'whitespace, doubleslash comment' => [
                ' //foo',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '//foo', 0, 1)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment with whitespaces' => [
                '// foo bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// foo bar', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment with tabs' => [
                "//\tfoo\tbar",
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, "//\tfoo\tbar", 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment with multi doubleslash chars' => [
                '////foo bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '////foo bar', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment with multi doubleslash chars and whitespaces' => [
                '// // foo bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// // foo bar', 0, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment, new line, comment' => [
                "// foo\n"
                . '// bar',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// foo', 0, 0))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 6))
                        )
                    )
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// bar', 1, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'doubleslash comment, new line, identifier, new line, comment' => [
                "// foo\n"
                . "bar\n"
                . '// baz',
                (new LineStream())
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// foo', 0, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 6))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 3))
                            )
                    )
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// baz', 2, 0))
                            )
                    ),
                new LineStream(),
            ],

            'multiline comment' => [
                '/*foo*/',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_VALUE, 'foo', 0, 2))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 5)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment empty' => [
                '/**/',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 2)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment newline' => [
                "/*\n" .
                '*/',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 2))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 0)),
                        )
                    ),
                new LineStream(),
            ],
            'whitespace, multiline comment' => [
                ' /*foo*/',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 1))
                                ->append(new Token(TokenType::T_VALUE, 'foo', 0, 3))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 6)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment with whitespaces' => [
                '/* foo bar */',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_VALUE, ' foo bar ', 0, 2))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 11)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment with tabs' => [
                "/*\tfoo\tbar\t*/",
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_VALUE, "\tfoo\tbar\t", 0, 2))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 11)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment with multi starts' => [
                '/*/*foo bar*/',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_VALUE, '/*foo bar', 0, 2))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 11)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment with multi starts and whitespaces' => [
                '/* /* foo /* bar */',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_VALUE, ' /* foo /* bar ', 0, 2))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 0, 17)),
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment, new line, continue comment' => [
                "/* foo\n"
                . 'bar */',
                (new LineStream())
                    ->append(
                        (new CommentLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                ->append(new Token(TokenType::T_VALUE, ' foo', 0, 2))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 6))
                                ->append(new Token(TokenType::T_VALUE, 'bar ', 1, 0))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 4))
                        )
                    ),
                new LineStream(),
            ],
            'multiline comment, new line, continue comment, new line, identifier, new line, hash comment' => [
                "/* foo\n"
                . "bar */\n"
                . "baz\n"
                . '// foobar',
                (new LineStream())
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' foo', 0, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, 'bar ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 6))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'baz', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 3))
                            )
                    )
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// foobar', 3, 0))
                            )
                    ),
                new LineStream(),
            ],
            'multiline comment, mixed line break types' => [
                "/* foo\n"
                . "bar\r\n"
                . "*/\n"
                . 'baz',
                (new LineStream())
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' foo', 0, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\r\n", 1, 3))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 2))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'baz', 3, 0))
                            )
                    ),
                new LineStream(),
            ],
            'multiline comment, commenting valid code' => [
                "page = PAGE\n" .
                "/*\n" .
                "[foo == \"foo\"]\n" .
                "  page.10.value = foo\n" .
                "[else]\n" .
                "  page.10.value = bar\n" .
                "[end]\n" .
                "*/\n" .
                "page.10 = TEXT\n",
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'page', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 4))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 5))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, 'PAGE', 0, 7))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 11))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'page', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'PAGE', 0, 7))
                            )
                    )
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 2))
                                    ->append(new Token(TokenType::T_VALUE, '[foo == "foo"]', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 14))
                                    ->append(new Token(TokenType::T_VALUE, '  page.10.value = foo', 3, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 3, 21))
                                    ->append(new Token(TokenType::T_VALUE, '[else]', 4, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 4, 6))
                                    ->append(new Token(TokenType::T_VALUE, '  page.10.value = bar', 5, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 5, 21))
                                    ->append(new Token(TokenType::T_VALUE, '[end]', 6, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 6, 5))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 7, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 7, 2))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'page', 8, 0))
                                    ->append(new Token(TokenType::T_DOT, '.', 8, 4))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '10', 8, 5))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 8, 7))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 8, 8))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 8, 9))
                                    ->append(new Token(TokenType::T_VALUE, 'TEXT', 8, 10))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 8, 14))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'page', 8, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '10', 8, 5))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'TEXT', 8, 10))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'page'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'PAGE'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'page'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '10'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'TEXT'))
                            )
                    ),
            ],

            'two lines with new line' => [
                "foo = bar\n" .
                'foo2 = bar2',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 9))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 1, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 4))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 1, 5))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 6))
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 1, 7))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 1, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 1, 7))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2'))
                            )
                    ),
            ],
            'two lines with carriage return, new line' => [
                "foo = bar\r\n" .
                'foo2 = bar2',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6))
                                    ->append(new Token(TokenType::T_NEWLINE, "\r\n", 0, 9))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar', 0, 6))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 1, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 4))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 1, 5))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 6))
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 1, 7))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 1, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 1, 7))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2'))
                            )
                    ),
            ],

            'identifier, curly open, curly close' => [
                'foo{}',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 3))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, whitespace, curly open, whitespace, curly close' => [
                'foo { }',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, tab, curly open, tab, curly close' => [
                "foo\t{\t}",
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, whitespace, curly open, no curly close' => [
                'foo {',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, whitespace, curly open, comment, no curly close' => [
                'foo { forced comment including the closing bracket }',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'forced comment including the closing bracket }', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    ),
            ],
            'identifier, whitespace, curly open, newline, curly close' => [
                "foo {\n" .
                '}',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 1, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, whitespace, curly open, newline, comment, newline, curly close' => [
                "foo {\n" .
                "  # comment = foo\n" .
                '}',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new CommentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# comment = foo', 1, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 17))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 2, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, whitespace, curly open, newline, identifier, newline, curly close' => [
                "foo {\n"
                . "bar\n"
                . '}',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'bar', 1, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 3))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 2, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, whitespaces, curly open, assignment rows, curly close' => [
                "foo {\n" .
                "  foo1 = bar1\n" .
                "  foo2 = bar2\n" .
                "  foo3 = bar3\n" .
                "  foo3 {\n" .
                "    foo4 = bar4 {\n" .
                "  }\n" .
                "  foo5.foo6 = bar6\n" .
                '}',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 1, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo1', 1, 2))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 6))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 1, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'bar1', 1, 9))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 13))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo1', 1, 2))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar1', 1, 9))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 2, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 2, 2))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 6))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 2, 9))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 13))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 2, 2))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 2, 9))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 3, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 3, 2))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 3, 6))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 3, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 3, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'bar3', 3, 9))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 3, 13))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 3, 2))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar3', 3, 9))
                            )
                    )
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 4, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 4, 2))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 4, 6))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 4, 7))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 4, 8))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 4, 2))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '    ', 5, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo4', 5, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 5, 8))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 5, 9))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 5, 10))
                                    ->append(new Token(TokenType::T_VALUE, 'bar4 {', 5, 11))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 5, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo4', 5, 4))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar4 {', 5, 11))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 6, 0))
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 6, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 6, 3))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, '  ', 7, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo5', 7, 2))
                                    ->append(new Token(TokenType::T_DOT, '.', 7, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo6', 7, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 7, 11))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 7, 12))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 7, 13))
                                    ->append(new Token(TokenType::T_VALUE, 'bar6', 7, 14))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 7, 18))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo5', 7, 2))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo6', 7, 7))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar6', 7, 14))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 8, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo1'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar1'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar3'))
                            )
                    )
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo4'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar4 {'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo5'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo6'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar6'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'identifier, tabs, curly open, assignment rows, curly close' => [
                "foo\t{\n" .
                "\tfoo1\t=\tbar1\n" .
                "\tfoo2\t=\tbar2\n" .
                "\tfoo3\t=\tbar3\n" .
                "\tfoo3\t{\n" .
                "\t\tfoo4\t=\tbar4 {\n" .
                "\t}\n" .
                "\tfoo5.foo6\t=\tbar6\n" .
                '}',
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 3))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 1, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo1', 1, 1))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 1, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 1, 6))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 1, 7))
                                    ->append(new Token(TokenType::T_VALUE, 'bar1', 1, 8))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo1', 1, 1))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar1', 1, 8))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 2, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 2, 1))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 2, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 6))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 2, 7))
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 2, 8))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2', 2, 1))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2', 2, 8))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 3, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 3, 1))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 3, 5))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 3, 6))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 3, 7))
                                    ->append(new Token(TokenType::T_VALUE, 'bar3', 3, 8))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 3, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 3, 1))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar3', 3, 8))
                            )
                    )
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 4, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 4, 1))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 4, 5))
                                    ->append(new Token(TokenType::T_BLOCK_START, '{', 4, 6))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 4, 7))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3', 4, 1))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t\t", 5, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo4', 5, 2))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 5, 6))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 5, 7))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 5, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'bar4 {', 5, 9))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 5, 15))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo4', 5, 2))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar4 {', 5, 9))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 6, 0))
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 6, 1))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 6, 2))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 7, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo5', 7, 1))
                                    ->append(new Token(TokenType::T_DOT, '.', 7, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo6', 7, 6))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 7, 10))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 7, 11))
                                    ->append(new Token(TokenType::T_BLANK, "\t", 7, 12))
                                    ->append(new Token(TokenType::T_VALUE, 'bar6', 7, 13))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 7, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo5', 7, 1))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo6', 7, 6))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar6', 7, 13))
                            )
                    )
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 8, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo1'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar1'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo2'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar2'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar3'))
                            )
                    )
                    ->append(
                        (new IdentifierBlockOpenLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo3'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo4'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar4 {'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo5'))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo6'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'bar6'))
                            )
                    )
                    ->append(
                        new BlockCloseLine()
                    ),
            ],

            'condition start' => [
                '[',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'condition stop alone is recognized as invalid line' => [
                ']',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, ']', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'condition start, condition stop' => [
                '[]',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 1))
                            )
                    ),
                new LineStream(),
            ],
            'condition start, body, condition stop' => [
                '[foo = bar]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 10))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'condition start, body, condition stop, newline' => [
                "[foo = bar]\n",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 10))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 11))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 1))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'condition start, body is not trimmed, condition stop' => [
                "[ foo = bar\t]",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, " foo = bar\t", 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 12))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, " foo = bar\t", 0, 1))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, " foo = bar\t"))
                    ),
            ],
            'whitespace, condition start, body, condition stop' => [
                ' [foo = bar]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'tab, condition start, body, condition stop' => [
                "\t[foo = bar]",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'condition start, body with umlaut, condition stop, hash comment' => [
                '[foo = bär] # a comment',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bär', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 10))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 11))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 12))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bär', 0, 1))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bär'))
                    ),
            ],
            'tab, condition start, body, condition stop, hash comment' => [
                "\t[foo = bar] # a comment",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 12))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 13))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'tab, condition start, body, condition stop, broken forced comment' => [
                "\t[foo = bar] a comment",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 12))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 13))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'tab, condition start, body, condition stop, doubleslash comment' => [
                "\t[foo = bar] // a comment",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 12))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 13))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'tab, condition start, body, condition stop, multiline comment one line' => [
                "\t[foo = bar] /* a comment",
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 12))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 13))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 15))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    ),
            ],
            'tab, condition start, body, condition stop, multiline comment' => [
                "\t[foo = bar] /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 1))
                                    ->append(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 11))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 12))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 13))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 15))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 25))
                                    ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar', 0, 2))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'foo = bar'))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'condition start, body with brackets, condition stop' => [
                '[page["uid"] in [17,24]]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'page["uid"] in [17,24]', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 23))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'page["uid"] in [17,24]', 0, 1))
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'page["uid"] in [17,24]'))
                    ),
            ],
            'condition else' => [
                '[ELSE]',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'ELSE', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    ),
            ],
            'condition else, hash comment' => [
                '[ELSE] # a comment',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'ELSE', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 7))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    ),
            ],
            'condition else, broken forced comment' => [
                '[ELSE] a comment',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'ELSE', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 7))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    ),
            ],
            'condition else, doubleslash comment' => [
                '[ELSE] // a comment',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'ELSE', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 7))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    ),
            ],
            'condition else, multiline comment' => [
                "[ELSE] /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'ELSE', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 7))
                                ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 9))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 19))
                                ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                        )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'condition else, multiline comment one line' => [
                '[ELSE] /* a comment',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'ELSE', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 7))
                                ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 9))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    ),
            ],
            'condition else lowercase is recognized as T_CONDITION_ELSE' => [
                '[else]',
                (new LineStream())
                    ->append(
                        (new ConditionElseLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_ELSE, 'else', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionElseLine()
                    ),
            ],
            'condition start, whitespace, ELSE, condition stop, recognized as T_VALUE' => [
                '[ ELSE]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' ELSE', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 6))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, ' ELSE', 0, 1)
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, ' ELSE')
                            )
                    ),
            ],
            'condition start, ELSE, whitespace, condition stop, recognized as T_VALUE' => [
                '[ELSE ]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'ELSE ', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 6))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'ELSE ', 0, 1)
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'ELSE ')
                            )
                    ),
            ],
            'condition end' => [
                '[END]',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'END', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition end, hash comment' => [
                '[END] # a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'END', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 6))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition end, broken forced comment' => [
                '[END] a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'END', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 6))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition end, doubleslash comment' => [
                '[END] // a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'END', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 6))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition end, multiline comment' => [
                "[END] /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'END', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 6))
                                ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 8))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 18))
                                ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                        )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'condition end, multiline comment one line' => [
                '[END] /* a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'END', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 6))
                                ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 8))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition end lowercase is recognized as T_CONDITION_END' => [
                '[end]',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_END, 'end', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 4))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition start, whitespace, END, condition stop, recognized as T_VALUE' => [
                '[ END]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' END', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, ' END', 0, 1)
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, ' END')
                            )
                    ),
            ],
            'condition start, END, whitespace, condition stop, recognized as T_VALUE' => [
                '[END ]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'END ', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 5))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'END ', 0, 1)
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'END ')
                            )
                    ),
            ],
            'condition global' => [
                '[GLOBAL]',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition global, newline, import' => [
                "[GLOBAL]\n" .
                "@import 'EXT:felogin/Configuration/TypoScript/constants.typoscript'\n",
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 8))
                            )
                    )
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 1, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 1, 7))
                                    ->append(new token(TokenType::T_IMPORT_START, '\'', 1, 8))
                                    ->append(new token(TokenType::T_VALUE, 'EXT:felogin/Configuration/TypoScript/constants.typoscript', 1, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '\'', 1, 66))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 67))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'EXT:felogin/Configuration/TypoScript/constants.typoscript', 1, 9)
                            )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    )
                    ->append(
                        (new ImportLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'EXT:felogin/Configuration/TypoScript/constants.typoscript')
                            )
                    ),
            ],
            'condition global, hash comment' => [
                '[GLOBAL] # a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 9))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition global, broken forced comment' => [
                '[GLOBAL] a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 9))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition global, doubleslash comment' => [
                '[GLOBAL] // a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 9))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition global, multiline comment' => [
                "[GLOBAL] /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 9))
                                ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 11))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 21))
                                ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                        )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'condition global, multiline comment one line' => [
                '[GLOBAL] /* a comment',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'GLOBAL', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                                ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 9))
                                ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 11))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition global lowercase is recognized as T_CONDITION_GLOBAL' => [
                '[global]',
                (new LineStream())
                    ->append(
                        (new ConditionStopLine())->setTokenStream(
                            (new TokenStream())
                                ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                ->append(new Token(TokenType::T_CONDITION_GLOBAL, 'global', 0, 1))
                                ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 7))
                        )
                    ),
                (new LineStream())
                    ->append(
                        new ConditionStopLine()
                    ),
            ],
            'condition start, whitespace, GLOBAL, condition stop, recognized as T_VALUE' => [
                '[ GLOBAL]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' GLOBAL', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 8))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, ' GLOBAL', 0, 1)
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, ' GLOBAL')
                            )
                    ),
            ],
            'condition start, GLOBAL, whitespace, condition stop, recognized as T_VALUE' => [
                '[GLOBAL ]',
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_CONDITION_START, '[', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'GLOBAL ', 0, 1))
                                    ->append(new Token(TokenType::T_CONDITION_STOP, ']', 0, 8))
                            )
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'GLOBAL ', 0, 1)
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ConditionLine())
                            ->setValueToken(
                                new Token(TokenType::T_VALUE, 'GLOBAL ')
                            )
                    ),
            ],

            'pseudo constant start is recognized as identifier' => [
                '{$',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'pseudo constant stop alone is recognized as block stop' => [
                '}',
                (new LineStream())
                    ->append(
                        (new BlockCloseLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLOCK_STOP, '}', 0, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        new BlockCloseLine()
                    ),
            ],
            'pseudo constant start, constant stop is recognized as identifier' => [
                '{$}',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$}', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'pseudo constant start, constant body, constant stop is recognized as identifier' => [
                '{$foo}',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo}', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'pseudo constant start, constant body, constant stop, linebreak is recognized as identifier with linebreak' => [
                "{\$foo}\n",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo}', 0, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 6))
                            )
                    ),
                new LineStream(),
            ],
            'pseudo constant start, constant body, no constant stop, linebreak is recognized as block start with comment' => [
                "{\$foo\n",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo', 0, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                            )
                    ),
                new LineStream(),
            ],
            'whitespace, pseudo constant start, constant body, constant stop is recognized as block start with comment' => [
                ' {$foo}',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo}', 0, 1))
                            )
                    ),
                new LineStream(),
            ],
            'pseudo constant start, assignment, constant is recognized as identifier' => [
                '{$foo} = {$bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo}', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 7))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 8))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 9))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo}', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 9))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$foo}'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    ),
            ],
            'identifier, whitespace, copy, whitespace, pseudo constant start recognized as identifier' => [
                'foo < {$bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_COPY, '<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$bar}', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$bar}', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierCopyLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$bar}'))
                            )
                    ),
            ],
            'identifier, whitespace, reference, whitespace, pseudo constant recognized as identifier' => [
                'foo =< {$bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 6))
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$bar}', 0, 7))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$bar}', 0, 7))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierReferenceLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '{$bar}'))
                            )
                    ),
            ],
            'identifier, assignment, constant start, constant body, constant stop' => [
                'foo={$bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 3))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 4))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    ),
            ],
            'identifier, assignment, constant start, constant body with null coalesce, constant stop' => [
                'foo={$bar ?? $baz}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 3))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar ?? $baz}', 0, 4))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar ?? $baz}', 0, 4))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar ?? $baz}'))
                            )
                    ),
            ],
            'identifier, whitespace, assignment, whitespace, constant start, constant body, constant stop' => [
                'foo = {$bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    ),
            ],
            'identifier, assignment, dotted constant' => [
                'foo = {$foo.bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$foo.bar}', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$foo.bar}', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$foo.bar}'))
                            )
                    ),
            ],
            'identifier, assignment, dot quoted constant' => [
                'foo = {$foo\.bar}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$foo\.bar}', 0, 6))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$foo\.bar}', 0, 6))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$foo\.bar}'))
                            )
                    ),
            ],
            'identifier, assignment, constant, value' => [
                'foo = {$bar} continued value',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' continued value', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' continued value', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, ' continued value'))
                            )
                    ),
            ],
            'identifier, assignment, constant, value, constant' => [
                'foo = {$bar} continued value {$baz}',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' continued value ', 0, 12))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$baz}', 0, 29))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' continued value ', 0, 12))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$baz}', 0, 29))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, ' continued value '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$baz}'))
                            )
                    ),
            ],
            'identifier, assignment, constant, whitespace, hash comment understood as value' => [
                'foo = {$bar} # not a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' # not a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' # not a comment', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, ' # not a comment'))
                            )
                    ),
            ],
            'identifier, assignment, constant, tab, hash comment understood as value' => [
                "foo = {\$bar}\t# not a comment",
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, "\t# not a comment", 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, "\t# not a comment", 0, 12)),
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, "\t# not a comment")),
                            )
                    ),
            ],
            'identifier, assignment, constant, hash comment understood as value' => [
                'foo = {$bar}# not a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, '# not a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, '# not a comment', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, '# not a comment'))
                            )
                    ),
            ],
            'identifier, assignment, constant, whitespace, doubleslash comment understood as value' => [
                'foo = {$bar} // not a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' // not a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' // not a comment', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, ' // not a comment'))
                            )
                    ),
            ],
            'identifier, assignment, constant, tab, doubleslash comment understood as value' => [
                "foo = {\$bar}\t// not a comment",
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, "\t// not a comment", 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, "\t// not a comment", 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, "\t// not a comment"))
                            )
                    ),
            ],
            'identifier, assignment, constant, doubleslash comment understood as value' => [
                'foo = {$bar}// not a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, '// not a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, '// not a comment', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, '// not a comment'))
                            )
                    ),
            ],
            'identifier, assignment, constant, whitespace, multiline comment understood as value' => [
                'foo = {$bar} /* not a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' /* not a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' /* not a comment', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, ' /* not a comment'))
                            )
                    ),
            ],
            'identifier, assignment, constant, tab, multiline comment understood as value' => [
                "foo = {\$bar}\t/* not a comment",
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, "\t/* not a comment", 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, "\t/* not a comment", 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, "\t/* not a comment"))
                            )
                    ),
            ],
            'identifier, assignment, constant, multiline comment understood as value' => [
                'foo = {$bar}/* not a comment',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 0, 4))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, '/* not a comment', 0, 12))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, '/* not a comment', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, '/* not a comment'))
                            )
                    ),
            ],
            'identifier, multi line assignment, constant' => [
                'foo ( {$bar} )',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_VALUE, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' ', 0, 12))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 0, 13))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' ', 0, 5))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
                                    ->append(new Token(TokenType::T_VALUE, ' ', 0, 12))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, ' '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                                    ->append(new Token(TokenType::T_VALUE, ' '))
                            )
                    ),
            ],
            'identifier, multi line assignment, mixed value and constant' => [
                "foo (\n" .
                "    {\$bar.bar}\n" .
                "    someValue\n" .
                "    {\$baz\.baz}\n" .
                ")\n" .
                'someIdentifier',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar.bar}', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 14))
                                    ->append(new Token(TokenType::T_VALUE, '    someValue', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 13))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 3, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$baz\.baz}', 3, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 3, 15))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 4, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 4, 1))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar.bar}', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 14))
                                    ->append(new Token(TokenType::T_VALUE, '    someValue', 2, 0))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 13))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 3, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$baz\.baz}', 3, 4))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 5, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar.bar}'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    someValue'))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n"))
                                    ->append(new Token(TokenType::T_VALUE, '    '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$baz\.baz}'))
                            )
                    ),
            ],
            'identifier, multi line assignment, hash comment, identifier' => [
                "foo (\n" .
                "    {\$bar}\n" .
                ") # a comment\n" .
                'someIdentifier',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 10))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 1))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 2, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 13))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 3, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    ),
            ],
            'identifier, multi line assignment, forced comment, identifier' => [
                "foo (\n" .
                "    {\$bar}\n" .
                ") a comment\n" .
                'someIdentifier',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 10))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 1))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 2, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 11))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 3, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    ),
            ],
            'identifier, multi line assignment, doublehash comment, identifier' => [
                "foo (\n" .
                "    {\$bar}\n" .
                ") // a comment\n" .
                'someIdentifier',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 10))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 1))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 2, 2))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 14))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                            )
                    )
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 3, 0))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    ),
            ],
            'identifier, multi line assignment, multiline comment, identifier' => [
                "foo (\n" .
                "    {\$bar}\n" .
                ") /* a comment\n" .
                "comment = end */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 3))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', 0, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 5))
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 10))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 1))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 2, 2))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 2, 4))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 2, 14))
                                    ->append(new Token(TokenType::T_VALUE, 'comment = end ', 3, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 3, 14))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 3, 16))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 0, 0))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    ', 1, 0))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 1, 4))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 4, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 4, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 4, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 4, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 4, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 4, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 4, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'foo'))
                            )
                            ->setValueTokenStream(
                                (new ConstantAwareTokenStream())
                                    ->append(new Token(TokenType::T_VALUE, '    '))
                                    ->append(new Token(TokenType::T_CONSTANT, '{$bar}'))
                            )
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],

            'import keyword' => [
                '@import',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'whitespace, import keyword' => [
                ' @import',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 1))
                            )
                    ),
                new LineStream(),
            ],
            'tab, import keyword' => [
                "\t@import",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 1))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword invalid is recognized as T_IDENTIFIER' => [
                '@impfoo',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '@impfoo', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, comment' => [
                '@import # foo',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# foo', 0, 8))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, broken comment forced due to missing \' or "' => [
                '@import somethingInvalid',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'somethingInvalid', 0, 8))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, start tick, stop tick' => [
                "@import''",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 8))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, start doubletick, stop doubletick' => [
                '@import""',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_START, '"', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '"', 0, 8))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, start tick, stop doubletick' => [
                "@import'\"",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '"', 0, 8))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, start doubletick, stop tick' => [
                '@import"\'',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_START, '"', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 8))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, whitespace, start tick, stop tick' => [
                "@import ''",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 9))
                            )
                    ),
                new LineStream(),
            ],
            'import keyword, whitespace, start tick, value, no stop tick' => [
                "@import 'EXT:foo/Resources/Private/TypoScript/*.typoscript",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/Resources/Private/TypoScript/*.typoscript', 0, 9))
                            )
                            ->setValueToken((new Token(TokenType::T_VALUE, 'EXT:foo/Resources/Private/TypoScript/*.typoscript', 0, 9)))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken((new Token(TokenType::T_VALUE, 'EXT:foo/Resources/Private/TypoScript/*.typoscript')))
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop tick' => [
                "@import 'EXT:foo/Resources/Private/TypoScript/*.typoscript'",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/Resources/Private/TypoScript/*.typoscript', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 58)),
                            )
                            ->setValueToken((new Token(TokenType::T_VALUE, 'EXT:foo/Resources/Private/TypoScript/*.typoscript', 0, 9)))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken((new Token(TokenType::T_VALUE, 'EXT:foo/Resources/Private/TypoScript/*.typoscript')))
                    ),
            ],
            'import keyword, whitespace, start tick, value with whitespaces, stop tick' => [
                "@import ' EXT:foo/Resources/Pri vate/TypoScript/*.typoscript '",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, ' EXT:foo/Resources/Pri vate/TypoScript/*.typoscript ', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 61)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' EXT:foo/Resources/Pri vate/TypoScript/*.typoscript ', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' EXT:foo/Resources/Pri vate/TypoScript/*.typoscript '))
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop tick, comment' => [
                "@import 'EXT:foo/...' # a comment",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 22)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    ),
            ],
            'import keyword, whitespace, start tick, value with umlaut, stop tick, comment, newline' => [
                "@import 'EXT:föö/...' # a comment\n",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:föö/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 22))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 33)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:föö/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:föö/...'))
                    ),
            ],
            'import keyword, whitespace, start doubletick, value, stop tick, comment' => [
                "@import \"EXT:foo/...' # a comment",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, '"', 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 22))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop doubletick, hash comment' => [
                "@import 'EXT:foo/...\" # a comment",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '"', 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 22)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop doubletick, doubleslash comment' => [
                "@import 'EXT:foo/...\" // a comment",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '"', 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 22)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop doubletick, multiline comment' => [
                "@import 'EXT:foo/...\" /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '"', 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 22))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 24))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 34))
                                    ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop doubletick, multiline comment one line' => [
                "@import 'EXT:foo/...\" /* a comment",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, '"', 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 22))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 24)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    ),
            ],
            'import keyword, whitespace, start tick, value, stop doubletick, forced comment' => [
                "@import 'EXT:foo/...' a comment",
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', 0, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 7))
                                    ->append(new Token(TokenType::T_IMPORT_START, "'", 0, 8))
                                    ->append(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                                    ->append(new Token(TokenType::T_IMPORT_STOP, "'", 0, 20))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 21))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 22)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...', 0, 9))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'EXT:foo/...'))
                    ),
            ],

            'old import keyword' => [
                '<INCLUDE_TYPOSCRIPT:',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0)),
                            )
                    ),
                new LineStream(),
            ],
            'whitespace, old import keyword' => [
                ' <INCLUDE_TYPOSCRIPT:',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 1)),
                            )
                    ),
                new LineStream(),
            ],
            'tab, old import keyword' => [
                "\t<INCLUDE_TYPOSCRIPT:",
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_BLANK, "\t", 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 1)),
                            )
                    ),
                new LineStream(),
            ],
            'old import keyword invalid is recognized as T_IDENTIFIER' => [
                '<INCLUDE_TYPOFOO',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, '<INCLUDE_TYPOFOO', 0, 0))
                            )
                    ),
                new LineStream(),
            ],
            'old import keyword, stop' => [
                '<INCLUDE_TYPOSCRIPT:>',
                (new LineStream())
                    ->append(
                        (new InvalidLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 20)),
                            )
                    ),
                new LineStream(),
            ],
            'old import keyword, whitespace, value looks like hash comment' => [
                '<INCLUDE_TYPOSCRIPT: # not recognized as comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' # not recognized as comment', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' # not recognized as comment', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' # not recognized as comment'))
                    ),
            ],
            'old import keyword, tab, value looks like hash comment' => [
                "<INCLUDE_TYPOSCRIPT:\t# not recognized as comment",
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, "\t# not recognized as comment", 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, "\t# not recognized as comment", 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, "\t# not recognized as comment"))
                    ),
            ],
            'old import keyword, value looks like hash comment' => [
                '<INCLUDE_TYPOSCRIPT:# not recognized as comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, '# not recognized as comment', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, '# not recognized as comment', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, '# not recognized as comment'))
                    ),
            ],
            'old import keyword, whitespace, value looks like doubleslash comment' => [
                '<INCLUDE_TYPOSCRIPT: // not recognized as comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' // not recognized as comment', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' // not recognized as comment', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' // not recognized as comment'))
                    ),
            ],
            'old import keyword, tab, value looks like doubleslash comment' => [
                "<INCLUDE_TYPOSCRIPT:\t// not recognized as comment",
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, "\t// not recognized as comment", 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, "\t// not recognized as comment", 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, "\t// not recognized as comment"))
                    ),
            ],
            'old import keyword, value looks like doubleslash comment' => [
                '<INCLUDE_TYPOSCRIPT:// not recognized as comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, '// not recognized as comment', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, '// not recognized as comment', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, '// not recognized as comment'))
                    ),
            ],
            'old import keyword, whitespace, value looks like multiline comment' => [
                '<INCLUDE_TYPOSCRIPT: /* not recognized as comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' /* not recognized as comment', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' /* not recognized as comment', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' /* not recognized as comment'))
                    ),
            ],
            'old import keyword, tab, value looks like multiline comment' => [
                "<INCLUDE_TYPOSCRIPT:\t/* not recognized as comment",
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, "\t/* not recognized as comment", 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, "\t/* not recognized as comment", 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, "\t/* not recognized as comment"))
                    ),
            ],
            'old import keyword, value looks like multiline comment' => [
                '<INCLUDE_TYPOSCRIPT:/* not recognized as comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, '/* not recognized as comment', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, '/* not recognized as comment', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, '/* not recognized as comment'))
                    ),
            ],
            'old import keyword, invalid value, no stop but still ok' => [
                '<INCLUDE_TYPOSCRIPT:somethingValid',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, 'somethingValid', 0, 20)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, 'somethingValid', 0, 20)),
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, 'somethingValid')),
                    ),
            ],
            'old import keyword, value, no stop' => [
                '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    // Note whitespace is include in 'T_VALUE' here, and not parsed
                                    ->append(new Token(TokenType::T_VALUE, ' source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"', 0, 20))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"'))
                    ),
            ],
            'old import keyword, value, stop' => [
                '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript">',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    // Note whitespace is include in 'T_VALUE' here, and not parsed
                                    ->append(new Token(TokenType::T_VALUE, ' source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 86)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="FILE:EXT:foo/Resources/Private/TypoScript/bar.typoscript"'))
                    ),
            ],
            'old import keyword, longer value, stop' => [
                '<INCLUDE_TYPOSCRIPT: source="DIR:..." condition="...">',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    // Note whitespace is included in 'T_VALUE' here, and not parsed
                                    ->append(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="..."', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 53)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="..."', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="..."'))
                    ),
            ],
            'old import keyword, condition, stop' => [
                '<INCLUDE_TYPOSCRIPT: source="DIR:..." condition="[tree.level = 2]">',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    // Note whitespace is included in 'T_VALUE' here, and not parsed
                                    ->append(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[tree.level = 2]"', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 66)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[tree.level = 2]"', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[tree.level = 2]"'))
                    ),
            ],
            'old import keyword, condition syntax with greater sign, stop' => [
                '<INCLUDE_TYPOSCRIPT: source="DIR:..." condition="[tree.level >= 2]">',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    // Note whitespace is included in 'T_VALUE' here, and not parsed
                                    ->append(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[tree.level >= 2]"', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 67)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[tree.level >= 2]"', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[tree.level >= 2]"'))
                    ),
            ],
            'old import keyword, condition syntax with greater sign and quoted doubleticks, stop' => [
                '<INCLUDE_TYPOSCRIPT: source="DIR:..." condition="[traverse(page, \"title\") == \"fo>o\"]">',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    // Note whitespace is included in 'T_VALUE' here, and not parsed
                                    ->append(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[traverse(page, \"title\") == \"fo>o\"]"', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 89)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[traverse(page, \"title\") == \"fo>o\"]"', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="DIR:..." condition="[traverse(page, \"title\") == \"fo>o\"]"'))
                    ),
            ],
            'old import keyword, value, stop, comment' => [
                '<INCLUDE_TYPOSCRIPT: source="..."> # a comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 33))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 34))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, '# a comment', 0, 35)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."'))
                    ),
            ],
            'old import keyword, value, stop, doubleslash comment' => [
                '<INCLUDE_TYPOSCRIPT: source="..."> // a comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 33))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 34))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, '// a comment', 0, 35)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."'))
                    ),
            ],
            'old import keyword, value, stop, multiline comment' => [
                "<INCLUDE_TYPOSCRIPT: source=\"...\"> /* a comment\n" .
                "endOf = comment */\n" .
                'someIdentifier = someValue',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 33))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 34))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 35))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 37))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 0, 47))
                                    ->append(new Token(TokenType::T_VALUE, 'endOf = comment ', 1, 0))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', 1, 16))
                                    ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 18))
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 14))
                                    ->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', 2, 15))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 2, 16))
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier', 2, 0))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue', 2, 17))
                            )
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."'))
                    )
                    ->append(
                        (new IdentifierAssignmentLine())
                            ->setIdentifierTokenStream(
                                (new IdentifierTokenStream())
                                    ->append(new IdentifierToken(TokenType::T_IDENTIFIER, 'someIdentifier'))
                            )
                            ->setValueTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_VALUE, 'someValue'))
                            )
                    ),
            ],
            'old import keyword, value, stop, multiline comment one line' => [
                '<INCLUDE_TYPOSCRIPT: source="..."> /* a comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 33))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 34))
                                    ->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', 0, 35))
                                    ->append(new Token(TokenType::T_VALUE, ' a comment', 0, 37)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."'))
                    ),
            ],
            'old import keyword, value, stop, force comment' => [
                '<INCLUDE_TYPOSCRIPT: source="..."> a comment',
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setTokenStream(
                                (new TokenStream())
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', 0, 0))
                                    ->append(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                                    ->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', 0, 33))
                                    ->append(new Token(TokenType::T_BLANK, ' ', 0, 34))
                                    ->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, 'a comment', 0, 35)),
                            )
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."', 0, 20))
                    ),
                (new LineStream())
                    ->append(
                        (new ImportOldLine())
                            ->setValueToken(new Token(TokenType::T_VALUE, ' source="..."'))
                    ),
            ],
        ];
    }

    #[DataProvider('tokenizeStringDataProvider')]
    #[Test]
    public function tokenize(string $source, LineStream $expected): void
    {
        $tokens = (new LosslessTokenizer())->tokenize($source);
        self::assertEquals($expected, $tokens);
    }

    #[DataProvider('tokenizeStringDataProvider')]
    #[Test]
    public function tokenizeLossy(string $source, LineStream $_, LineStream $expected): void
    {
        $tokens = (new LossyTokenizer())->tokenize($source);
        self::assertEquals($expected, $tokens);
    }

    #[DataProvider('tokenizeStringDataProvider')]
    #[Test]
    public function untokenize(string $source): void
    {
        $tokenizer = new LosslessTokenizer();
        $tokenLines =  $tokenizer->tokenize($source);
        $newSource = (string)$tokenLines;
        self::assertEquals($source, $newSource);
    }
}
