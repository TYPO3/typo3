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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer;

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
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\ConstantAwareTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierToken;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;

/**
 * A lossless tokenizer for TypoScript syntax.
 *
 * tokenize() creates a flat stream of tokens from a TypoScript string. It is lossless
 * and never "looses" characters to allow syntax linting and creating linter-fixed source
 * strings: tokenize() to create a TokenStream and using string cast (__toString()) on
 * that stream creates *the same* source string again.
 *
 * The tokenizer *does not* parse conditions or includes itself (no file / db lookups),
 * this is part of the IncludeTree parser.
 *
 * This class is unit test covered by TokenizerInterfaceTest and paired with LossyTokenizer.
 * Never change anything in this class without additional test coverage!
 *
 * @internal: Internal tokenizer structure.
 */
final class LosslessTokenizer implements TokenizerInterface
{
    private LineStream $lineStream;

    private TokenStreamInterface $tokenStream;
    private IdentifierTokenStream $identifierStream;
    private TokenStreamInterface $valueStream;

    private array $lines;
    private int $currentLineNumber;
    private string $currentLineString;
    private \closure $currentLinebreakCallback;
    private int $currentColumnInLine = 0;

    public function tokenize(string $source): LineStream
    {
        $this->lineStream = new LineStream();
        $this->currentLineNumber = -1;
        $this->lines = [];
        $this->splitLines($source);

        while (true) {
            $this->tokenStream = new TokenStream();
            $this->currentLineNumber++;
            if (!array_key_exists($this->currentLineNumber, $this->lines)) {
                break;
            }
            $this->currentColumnInLine = 0;
            $this->currentLineString = $this->lines[$this->currentLineNumber]['line'];
            $this->currentLinebreakCallback = $this->lines[$this->currentLineNumber]['linebreakCallback'];
            $this->parseTabsAndWhitespaces();
            $nextChar = substr($this->currentLineString, 0, 1);
            if ($nextChar === '') {
                ($this->currentLinebreakCallback)();
                if (!$this->tokenStream->isEmpty()) {
                    $this->createEmptyLine();
                }
                continue;
            }
            $nextTwoChars = substr($this->currentLineString, 0, 2);
            if ($nextChar === '#') {
                $this->createHashCommentLine();
            } elseif ($nextTwoChars === '//') {
                $this->createDoubleSlashCommentLine();
            } elseif ($nextTwoChars === '/*') {
                $this->createMultilineCommentLine();
            } elseif ($nextChar === '[') {
                $this->createConditionLine();
            } elseif ($nextChar === '}') {
                $this->createBlockStopLine();
            } elseif (str_starts_with($this->currentLineString, '@import')) {
                $this->parseImportLine();
            } elseif (str_starts_with($this->currentLineString, '<INCLUDE_TYPOSCRIPT:')) {
                $this->parseImportOld();
            } else {
                $this->parseIdentifier();
            }
        }

        return $this->lineStream;
    }

    private function splitLines($source): void
    {
        $vanillaLines = explode(chr(10), $source);
        $this->lines = array_map(
            fn (int $lineNumber, string $vanillaLine): array => [
                'line' => rtrim($vanillaLine, "\r"),
                'linebreakCallback' => str_ends_with($vanillaLine, "\r")
                    ? fn () => $this->tokenStream->append(new Token(TokenType::T_NEWLINE, "\r\n", $lineNumber, mb_strlen($vanillaLine) - 1))
                    : fn () => $this->tokenStream->append(new Token(TokenType::T_NEWLINE, "\n", $lineNumber, mb_strlen($vanillaLine))),
            ],
            array_keys($vanillaLines),
            $vanillaLines
        );
        // Set the linebreak callback of last line to empty to suppress dangling linebreak tokens
        $this->lines[count($vanillaLines) - 1]['linebreakCallback'] = function () {};
    }

    private function createEmptyLine(): void
    {
        $this->lineStream->append((new EmptyLine())->setTokenStream($this->tokenStream));
    }

    /**
     * Add tabs and whitespaces until some different char appears.
     */
    private function parseTabsAndWhitespaces(): void
    {
        $matches = [];
        if (preg_match('#^(\s+)(.*)$#', $this->currentLineString, $matches)) {
            $this->tokenStream->append(new Token(TokenType::T_BLANK, $matches[1], $this->currentLineNumber, $this->currentColumnInLine));
            $this->currentLineString = $matches[2];
            $this->currentColumnInLine = $this->currentColumnInLine + strlen($matches[1]);
        }
    }

    private function makeComment(): void
    {
        $nextChar = substr($this->currentLineString, 0, 1);
        if ($nextChar === '') {
            ($this->currentLinebreakCallback)();
            return;
        }
        $nextTwoChars = substr($this->currentLineString, 0, 2);
        if ($nextChar === '#') {
            $this->parseHashComment();
        } elseif ($nextTwoChars === '//') {
            $this->parseDoubleSlashComment();
        } elseif ($nextTwoChars === '/*') {
            $this->parseMultilineComment();
        } else {
            $this->parseHashComment();
        }
    }

    private function createHashCommentLine(): void
    {
        $this->parseHashComment();
        $this->lineStream->append((new CommentLine())->setTokenStream($this->tokenStream));
    }

    private function parseHashComment(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_COMMENT_ONELINE_HASH, $this->currentLineString, $this->currentLineNumber, $this->currentColumnInLine));
        ($this->currentLinebreakCallback)();
    }

    private function createDoubleSlashCommentLine(): void
    {
        $this->parseDoubleSlashComment();
        $this->lineStream->append((new CommentLine())->setTokenStream($this->tokenStream));
    }

    private function parseDoubleSlashComment(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_COMMENT_ONELINE_DOUBLESLASH, $this->currentLineString, $this->currentLineNumber, $this->currentColumnInLine));
        ($this->currentLinebreakCallback)();
    }

    private function createMultilineCommentLine(): void
    {
        $this->parseMultilineComment();
        $this->lineStream->append((new CommentLine())->setTokenStream($this->tokenStream));
    }

    private function parseMultilineComment(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_COMMENT_MULTILINE_START, '/*', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine += 2;
        $this->currentLineString = substr($this->currentLineString, 2);
        while (true) {
            if (str_ends_with($this->currentLineString, '*/')) {
                if (strlen($this->currentLineString) > 2) {
                    $this->tokenStream->append(new Token(TokenType::T_VALUE, substr($this->currentLineString, 0, -2), $this->currentLineNumber, $this->currentColumnInLine));
                }
                $this->tokenStream->append(new Token(TokenType::T_COMMENT_MULTILINE_STOP, '*/', $this->currentLineNumber, $this->currentColumnInLine + strlen($this->currentLineString) - 2));
                ($this->currentLinebreakCallback)();
                return;
            }
            if (strlen($this->currentLineString)) {
                $this->tokenStream->append(new Token(TokenType::T_VALUE, $this->currentLineString, $this->currentLineNumber, $this->currentColumnInLine));
            }
            ($this->currentLinebreakCallback)();
            if (!array_key_exists($this->currentLineNumber + 1, $this->lines)) {
                return;
            }
            $this->currentLineNumber++;
            $this->currentColumnInLine = 0;
            $this->currentLineString = $this->lines[$this->currentLineNumber]['line'];
            $this->currentLinebreakCallback = $this->lines[$this->currentLineNumber]['linebreakCallback'];
        }
    }

    /**
     * Create a condition line from token stream of this line.
     */
    private function createConditionLine(): void
    {
        $upperCaseLine = strtoupper($this->currentLineString);
        $this->tokenStream->append(new Token(TokenType::T_CONDITION_START, '[', $this->currentLineNumber, $this->currentColumnInLine));
        if (str_starts_with($upperCaseLine, '[ELSE]')) {
            $this->tokenStream->append(new Token(TokenType::T_CONDITION_ELSE, substr($this->currentLineString, 1, 4), $this->currentLineNumber, $this->currentColumnInLine + 1));
            $this->tokenStream->append(new Token(TokenType::T_CONDITION_STOP, ']', $this->currentLineNumber, $this->currentColumnInLine + 5));
            $this->currentLineString = substr($this->currentLineString, 6);
            $this->currentColumnInLine += 6;
            $this->parseTabsAndWhitespaces();
            $this->makeComment();
            $this->lineStream->append((new ConditionElseLine())->setTokenStream($this->tokenStream));
            return;
        }
        if (str_starts_with($upperCaseLine, '[END]')) {
            $this->tokenStream->append(new Token(TokenType::T_CONDITION_END, substr($this->currentLineString, 1, 3), $this->currentLineNumber, $this->currentColumnInLine + 1));
            $this->tokenStream->append(new Token(TokenType::T_CONDITION_STOP, ']', $this->currentLineNumber, $this->currentColumnInLine + 4));
            $this->currentLineString = substr($this->currentLineString, 5);
            $this->currentColumnInLine += 5;
            $this->parseTabsAndWhitespaces();
            $this->makeComment();
            $this->lineStream->append((new ConditionStopLine())->setTokenStream($this->tokenStream));
            return;
        }
        if (str_starts_with($upperCaseLine, '[GLOBAL]')) {
            $this->tokenStream->append(new Token(TokenType::T_CONDITION_GLOBAL, substr($this->currentLineString, 1, 6), $this->currentLineNumber, $this->currentColumnInLine + 1));
            $this->tokenStream->append(new Token(TokenType::T_CONDITION_STOP, ']', $this->currentLineNumber, $this->currentColumnInLine + 7));
            $this->currentLineString = substr($this->currentLineString, 8);
            $this->currentColumnInLine += 8;
            $this->parseTabsAndWhitespaces();
            $this->makeComment();
            $this->lineStream->append((new ConditionStopLine())->setTokenStream($this->tokenStream));
            return;
        }
        $conditionBody = '';
        $conditionBodyStartPosition = $this->currentColumnInLine + 1;
        $conditionBodyCharCount = 0;
        $conditionBodyChars = mb_str_split(substr($this->currentLineString, 1), 1, 'UTF-8');
        $bracketCount = 1;
        while (true) {
            $nextChar = $conditionBodyChars[$conditionBodyCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                if ($conditionBodyCharCount) {
                    $this->tokenStream->append(new Token(TokenType::T_VALUE, $conditionBody, $this->currentLineNumber, $conditionBodyStartPosition));
                }
                ($this->currentLinebreakCallback)();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($nextChar === '[') {
                $bracketCount++;
                $conditionBody .= $nextChar;
                $conditionBodyCharCount++;
                continue;
            }
            if ($nextChar === ']') {
                $bracketCount--;
                if ($bracketCount === 0) {
                    if ($conditionBodyCharCount) {
                        $conditionBodyToken = new Token(TokenType::T_VALUE, $conditionBody, $this->currentLineNumber, $conditionBodyStartPosition);
                        $this->tokenStream->append($conditionBodyToken);
                        $this->tokenStream->append(new Token(TokenType::T_CONDITION_STOP, ']', $this->currentLineNumber, $this->currentColumnInLine + $conditionBodyCharCount + 1));
                        $this->currentLineString = mb_substr($this->currentLineString, $conditionBodyCharCount + 2);
                        $this->currentColumnInLine = $this->currentColumnInLine + $conditionBodyCharCount + 2;
                        $this->parseTabsAndWhitespaces();
                        $this->makeComment();
                        $this->lineStream->append((new ConditionLine())->setTokenStream($this->tokenStream)->setValueToken($conditionBodyToken));
                        return;
                    }
                    $this->tokenStream->append(new Token(TokenType::T_CONDITION_STOP, ']', $this->currentLineNumber, $this->currentColumnInLine + $conditionBodyCharCount + 1));
                    $this->currentLineString = mb_substr($this->currentLineString, $conditionBodyCharCount + 2);
                    $this->currentColumnInLine = $this->currentColumnInLine + $conditionBodyCharCount + 2;
                    $this->parseTabsAndWhitespaces();
                    $this->makeComment();
                    $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                    return;
                }
                $conditionBody .= $nextChar;
                $conditionBodyCharCount++;
                continue;
            }
            $conditionBody .= $nextChar;
            $conditionBodyCharCount++;
        }
    }

    private function createBlockStopLine(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_BLOCK_STOP, $this->currentLineString, $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->makeComment();
        $this->lineStream->append((new BlockCloseLine())->setTokenStream($this->tokenStream));
    }

    private function parseBlockStart(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_BLOCK_START, '{', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
        if (str_starts_with($this->currentLineString, '}')) {
            // Edge case: foo = { } in one line. Note content within {} is not parsed, everything behind { ends up as comment.
            $this->lineStream->append((new IdentifierBlockOpenLine())->setIdentifierTokenStream($this->identifierStream)->setTokenStream($this->tokenStream));
            $this->tokenStream = new TokenStream();
            $this->tokenStream->append(new Token(TokenType::T_BLOCK_STOP, '}', $this->currentLineNumber, $this->currentColumnInLine));
            $this->currentLineString = substr($this->currentLineString, 1);
            $this->currentColumnInLine++;
            $this->makeComment();
            $this->lineStream->append((new BlockCloseLine())->setTokenStream($this->tokenStream));
            return;
        }
        $this->makeComment();
        $this->lineStream->append((new IdentifierBlockOpenLine())->setIdentifierTokenStream($this->identifierStream)->setTokenStream($this->tokenStream));
    }

    private function parseImportLine(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_IMPORT_KEYWORD, '@import', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine += 7;
        $this->currentLineString = substr($this->currentLineString, 7);
        $this->parseTabsAndWhitespaces();

        // Next char should be the opening tick or doubletick, otherwise we create a comment until end of line
        $nextChar = substr($this->currentLineString, 0, 1);
        if ($nextChar !== '\'' && $nextChar !== '"') {
            $this->makeComment();
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        $this->tokenStream->append(new Token(TokenType::T_IMPORT_START, $nextChar, $this->currentLineNumber, $this->currentColumnInLine));

        $importBody = '';
        $importBodyStartPosition = $this->currentColumnInLine + 1;
        $importBodyCharCount = 0;
        $importBodyChars = mb_str_split(substr($this->currentLineString, 1), 1, 'UTF-8');
        while (true) {
            $nextChar = $importBodyChars[$importBodyCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                if ($importBodyCharCount) {
                    $importBodyToken = (new Token(TokenType::T_VALUE, $importBody, $this->currentLineNumber, $importBodyStartPosition));
                    $this->tokenStream->append($importBodyToken);
                    ($this->currentLinebreakCallback)();
                    $this->lineStream->append((new ImportLine())->setTokenStream($this->tokenStream)->setValueToken($importBodyToken));
                    return;
                }
                ($this->currentLinebreakCallback)();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($nextChar === '\'' || $nextChar === '"') {
                if ($importBodyCharCount) {
                    $importBodyToken = new Token(TokenType::T_VALUE, $importBody, $this->currentLineNumber, $importBodyStartPosition);
                    $this->tokenStream->append($importBodyToken);
                    $this->tokenStream->append(new Token(TokenType::T_IMPORT_STOP, $nextChar, $this->currentLineNumber, $this->currentColumnInLine + $importBodyCharCount + 1));
                    $this->currentLineString = mb_substr($this->currentLineString, $importBodyCharCount + 2);
                    $this->currentColumnInLine = $this->currentColumnInLine + $importBodyCharCount + 2;
                    $this->parseTabsAndWhitespaces();
                    $this->makeComment();
                    $this->lineStream->append((new ImportLine())->setTokenStream($this->tokenStream)->setValueToken($importBodyToken));
                    return;
                }
                $this->tokenStream->append(new Token(TokenType::T_IMPORT_STOP, $nextChar, $this->currentLineNumber, $this->currentColumnInLine + $importBodyCharCount + 1));
                $this->currentLineString = mb_substr($this->currentLineString, $importBodyCharCount + 2);
                $this->currentColumnInLine = $this->currentColumnInLine + $importBodyCharCount + 2;
                $this->parseTabsAndWhitespaces();
                $this->makeComment();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            $importBody .= $nextChar;
            $importBodyCharCount++;
        }
    }

    /**
     * Parse everything behind <INCLUDE_TYPOSCRIPT: at least until end of line or
     * more if there is a multiline comment at end.
     */
    private function parseImportOld(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD, '<INCLUDE_TYPOSCRIPT:', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine += 20;
        $this->currentLineString = substr($this->currentLineString, 20);
        $importBody = '';
        $importBodyStartPosition = $this->currentColumnInLine;
        $importBodyCharCount = 0;
        $importBodyChars = mb_str_split($this->currentLineString, 1, 'UTF-8');
        while (true) {
            $nextChar = $importBodyChars[$importBodyCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                if ($importBodyCharCount) {
                    $importBodyToken = (new Token(TokenType::T_VALUE, $importBody, $this->currentLineNumber, $importBodyStartPosition));
                    $this->tokenStream->append($importBodyToken);
                    ($this->currentLinebreakCallback)();
                    $this->lineStream->append((new ImportOldLine())->setTokenStream($this->tokenStream)->setValueToken($importBodyToken));
                    return;
                }
                ($this->currentLinebreakCallback)();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($nextChar === '>') {
                if ($importBodyCharCount) {
                    $importBodyToken = new Token(TokenType::T_VALUE, $importBody, $this->currentLineNumber, $importBodyStartPosition);
                    $this->tokenStream->append($importBodyToken);
                    $this->tokenStream->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', $this->currentLineNumber, $this->currentColumnInLine + $importBodyCharCount));
                    $this->currentLineString = mb_substr($this->currentLineString, $importBodyCharCount + 1);
                    $this->currentColumnInLine = $this->currentColumnInLine + $importBodyCharCount + 1;
                    $this->parseTabsAndWhitespaces();
                    $this->makeComment();
                    $this->lineStream->append((new ImportOldLine())->setTokenStream($this->tokenStream)->setValueToken($importBodyToken));
                    return;
                }
                $this->tokenStream->append(new Token(TokenType::T_IMPORT_KEYWORD_OLD_STOP, '>', $this->currentLineNumber, $this->currentColumnInLine + $importBodyCharCount));
                $this->currentLineString = mb_substr($this->currentLineString, $importBodyCharCount + 1);
                $this->currentColumnInLine = $this->currentColumnInLine + $importBodyCharCount + 1;
                $this->parseTabsAndWhitespaces();
                $this->makeComment();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            $importBody .= $nextChar;
            $importBodyCharCount++;
        }
    }

    private function parseIdentifier(): void
    {
        $splitLine = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $currentPosition = $this->parseIdentifierUntilStopChar($splitLine);
        if (!$currentPosition) {
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        $this->currentLineString = substr($this->currentLineString, $currentPosition);
        $this->currentColumnInLine = $this->currentColumnInLine + $currentPosition;
        $currentColumnInLineBefore = $this->currentColumnInLine;
        $this->parseTabsAndWhitespaces();
        $currentPosition = $currentPosition + $this->currentColumnInLine - $currentColumnInLineBefore;
        $nextChar = $splitLine[$currentPosition] ?? null;
        $nextTwoChars = $nextChar . ($splitLine[$currentPosition + 1] ?? '');
        if ($nextTwoChars === '=<') {
            $this->parseOperatorReference();
            return;
        }
        if ($nextChar === '=') {
            $this->parseOperatorAssignment();
            return;
        }
        if ($nextChar === '{') {
            $this->parseBlockStart();
            return;
        }
        if ($nextChar === '>') {
            $this->parseOperatorUnset();
            return;
        }
        if ($nextChar === '<') {
            $this->parseOperatorCopy();
            return;
        }
        if ($nextChar === '(') {
            $this->parseOperatorMultilineAssignment();
            return;
        }
        if ($nextTwoChars === ':=') {
            $this->parseOperatorFunction();
            return;
        }
        if ($nextChar === '#') {
            $this->parseHashComment();
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        if ($nextTwoChars === '//') {
            $this->parseDoubleSlashComment();
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        if ($nextTwoChars === '/*') {
            $this->parseMultilineComment();
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        if ($nextChar === null) {
            ($this->currentLinebreakCallback)();
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
        }
    }

    private function parseOperatorAssignment(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT, '=', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
        $this->valueStream = new TokenStream();
        $this->parseValueForConstants();
        ($this->currentLinebreakCallback)();
        $this->lineStream->append((new IdentifierAssignmentLine())->setTokenStream($this->tokenStream)->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
    }

    private function parseOperatorMultilineAssignment(): void
    {
        $this->valueStream = new TokenStream();
        $this->tokenStream->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_START, '(', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        // True if we're currently in the line with the opening '('
        $isFirstLine = true;
        // True if the first line has a first value token: "foo ( thisIsTheFirstValueToken"
        $valueOnFirstLine = false;
        // True if the line after '(' is parsed
        $isSecondLine = false;
        $previousLineCallback = function () {};
        while (true) {
            if (str_starts_with(ltrim($this->currentLineString), ')')) {
                $this->parseTabsAndWhitespaces();
                $this->tokenStream->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', $this->currentLineNumber, $this->currentColumnInLine));
                $this->currentLineString = substr($this->currentLineString, 1);
                $this->currentColumnInLine++;
                $this->parseTabsAndWhitespaces();
                $this->makeComment();
                if ($this->valueStream->isEmpty()) {
                    $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                } else {
                    $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream)->setTokenStream($this->tokenStream));
                }
                return;
            }
            if ($isFirstLine && str_ends_with($this->currentLineString, ')')) {
                // Special case if the ')' is on same line as the opening '('
                $this->currentLineString = substr($this->currentLineString, 0, -1);
                if (strlen($this->currentLineString) > 1) {
                    $this->parseValueForConstants();
                    $this->tokenStream->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', $this->currentLineNumber, $this->currentColumnInLine + strlen($this->currentLineString)));
                    // Tricky to swap the streams here, but that's the most effective solution I could come up with for the line endings here.
                    ($this->currentLinebreakCallback)();
                    $tempStream = $this->tokenStream;
                    $this->tokenStream = $this->valueStream;
                    ($this->currentLinebreakCallback)();
                    $this->tokenStream = $tempStream;
                    $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream)->setTokenStream($this->tokenStream));
                    return;
                }
                $this->tokenStream->append(new Token(TokenType::T_OPERATOR_ASSIGNMENT_MULTILINE_STOP, ')', $this->currentLineNumber, $this->currentColumnInLine + strlen($this->currentLineString)));
                ($this->currentLinebreakCallback)();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($isFirstLine && strlen($this->currentLineString)) {
                $this->parseValueForConstants();
                $valueOnFirstLine = true;
                $previousLineCallback = $this->currentLinebreakCallback;
            }
            if (($isFirstLine && $valueOnFirstLine)
                || (!$isFirstLine && !$isSecondLine)
            ) {
                $tempStream = $this->tokenStream;
                $this->tokenStream = $this->valueStream;
                $previousLineCallback();
                $this->tokenStream = $tempStream;
            }
            if (!$isFirstLine && strlen($this->currentLineString)) {
                $this->parseValueForConstants();
            }
            $previousLineCallback = $this->currentLinebreakCallback;
            ($this->currentLinebreakCallback)();
            if (!array_key_exists($this->currentLineNumber + 1, $this->lines)) {
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($isFirstLine) {
                $isSecondLine = true;
            } else {
                $isSecondLine = false;
            }
            $isFirstLine = false;
            $valueOnFirstLine = false;
            $this->currentLineNumber++;
            $this->currentColumnInLine = 0;
            $this->currentLineString = $this->lines[$this->currentLineNumber]['line'];
            $this->currentLinebreakCallback = $this->lines[$this->currentLineNumber]['linebreakCallback'];
        }
    }

    private function parseOperatorUnset(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_OPERATOR_UNSET, '>', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
        $this->makeComment();
        $this->lineStream->append((new IdentifierUnsetLine())->setTokenStream($this->tokenStream)->setIdentifierTokenStream($this->identifierStream));
    }

    private function parseOperatorCopy(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_OPERATOR_COPY, '<', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
        $identifierStream = $this->identifierStream;
        $this->parseIdentifierAtEndOfLine();
        $referenceStream = $this->identifierStream;
        if ($referenceStream->isEmpty()) {
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        $this->lineStream->append(
            (new IdentifierCopyLine())
                ->setIdentifierTokenStream($identifierStream)
                ->setValueTokenStream($referenceStream)
                ->setTokenStream($this->tokenStream)
        );
    }

    private function parseOperatorReference(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_OPERATOR_REFERENCE, '=<', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine += 2;
        $this->currentLineString = substr($this->currentLineString, 2);
        $this->parseTabsAndWhitespaces();
        $identifierStream = $this->identifierStream;
        $this->parseIdentifierAtEndOfLine();
        $referenceStream = $this->identifierStream;
        if ($referenceStream->isEmpty()) {
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        $this->lineStream->append(
            (new IdentifierReferenceLine())
                ->setIdentifierTokenStream($identifierStream)
                ->setValueTokenStream($referenceStream)
                ->setTokenStream($this->tokenStream)
        );
    }

    private function parseIdentifierAtEndOfLine(): void
    {
        $this->identifierStream = new IdentifierTokenStream();
        $isRelative = false;
        $splitLine = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $char = $splitLine[0] ?? null;
        if ($char === null) {
            return;
        }
        $nextTwoChars = $char . ($splitLine[1] ?? '');
        if ($char === '.') {
            // A relative right side: foo.bar < .foo (note the dot!). we identifierStream->setRelative() and
            // get rid of the dot for the rest of the processing.
            $isRelative = true;
            $this->tokenStream->append((new Token(TokenType::T_DOT, '.', 0, $this->currentColumnInLine)));
            array_shift($splitLine);
            $this->currentColumnInLine++;
            $this->currentLineString = substr($this->currentLineString, 1);
        }
        if ($char === '#') {
            $this->parseHashComment();
            return;
        }
        if ($nextTwoChars === '//') {
            $this->parseDoubleSlashComment();
            return;
        }
        if ($nextTwoChars === '/*') {
            $this->parseMultilineComment();
            return;
        }
        $currentPosition = $this->parseIdentifierUntilStopChar($splitLine, $isRelative);
        if (!$currentPosition) {
            return;
        }
        $this->currentLineString = substr($this->currentLineString, $currentPosition);
        $this->currentColumnInLine = $this->currentColumnInLine + $currentPosition;
        $this->parseTabsAndWhitespaces();
        $this->makeComment();
    }

    private function parseIdentifierUntilStopChar(array $splitLine, bool $isRelative = false): ?int
    {
        $this->identifierStream = new IdentifierTokenStream();
        if ($isRelative) {
            $this->identifierStream->setRelative();
        }
        $currentPosition = 0;
        $currentIdentifierStartPosition = $this->currentColumnInLine;
        $currentIdentifierBody = '';
        $currentIdentifierCharCount = 0;
        while (true) {
            $nextChar = $splitLine[$currentPosition] ?? null;
            if ($nextChar === null) {
                if ($currentIdentifierCharCount) {
                    $identifierToken = new IdentifierToken(TokenType::T_IDENTIFIER, $currentIdentifierBody, $this->currentLineNumber, $currentIdentifierStartPosition);
                    $this->tokenStream->append($identifierToken);
                    $this->identifierStream->append($identifierToken);
                }
                ($this->currentLinebreakCallback)();
                return null;
            }
            $nextTwoChars = $nextChar . ($splitLine[$currentPosition + 1] ?? null);
            if ($currentPosition > 0
                && ($nextChar === ' ' || $nextChar === "\t" || $nextChar === '=' || $nextChar === '<' || $nextChar === '>' || $nextChar === '{' || $nextTwoChars === ':=' || $nextChar === '(')
            ) {
                if ($currentIdentifierCharCount) {
                    $identifierToken = new IdentifierToken(TokenType::T_IDENTIFIER, $currentIdentifierBody, $this->currentLineNumber, $currentIdentifierStartPosition);
                    $this->tokenStream->append($identifierToken);
                    $this->identifierStream->append($identifierToken);
                }
                break;
            }
            if ($nextTwoChars === '\\.') {
                // A quoted dot is part of *this* identifier
                $currentIdentifierBody .= '.';
                $currentPosition += 2;
                $currentIdentifierCharCount++;
            } elseif ($nextChar === '.') {
                if ($currentIdentifierCharCount) {
                    $identifierToken = new IdentifierToken(TokenType::T_IDENTIFIER, $currentIdentifierBody, $this->currentLineNumber, $currentIdentifierStartPosition);
                    $this->tokenStream->append($identifierToken);
                    $this->identifierStream->append($identifierToken);
                    $currentIdentifierCharCount = 0;
                    $currentIdentifierBody = '';
                }
                $this->tokenStream->append(new Token(TokenType::T_DOT, '.', $this->currentLineNumber, $this->currentColumnInLine + $currentPosition));
                $currentPosition++;
                $currentIdentifierStartPosition = $this->currentColumnInLine + $currentPosition;
            } else {
                $currentIdentifierBody .= $nextChar;
                $currentIdentifierCharCount++;
                $currentPosition++;
            }
        }
        return $currentPosition;
    }

    private function parseOperatorFunction(): void
    {
        $this->tokenStream->append(new Token(TokenType::T_OPERATOR_FUNCTION, ':=', $this->currentLineNumber, $this->currentColumnInLine));
        $this->currentColumnInLine += 2;
        $this->currentLineString = substr($this->currentLineString, 2);
        $this->parseTabsAndWhitespaces();
        if ($this->currentLineString === '') {
            ($this->currentLinebreakCallback)();
            $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
            return;
        }
        $functionName = '';
        $functionNameStartPosition = $this->currentColumnInLine;
        $functionNameCharCount = 0;
        $functionChars = mb_str_split($this->currentLineString, 1, 'UTF-8');
        while (true) {
            $nextChar = $functionChars[$functionNameCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                if ($functionNameCharCount) {
                    $this->tokenStream->append(new Token(TokenType::T_FUNCTION_NAME, $functionName, $this->currentLineNumber, $functionNameStartPosition));
                }
                ($this->currentLinebreakCallback)();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($nextChar === '(') {
                if ($functionNameCharCount) {
                    $functionNameToken = new Token(TokenType::T_FUNCTION_NAME, $functionName, $this->currentLineNumber, $functionNameStartPosition);
                    $this->tokenStream->append($functionNameToken);
                    $this->tokenStream->append(new Token(TokenType::T_FUNCTION_VALUE_START, '(', $this->currentLineNumber, $this->currentColumnInLine + $functionNameCharCount));
                    $functionNameCharCount++;
                    break;
                }
                $this->makeComment();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            $functionName .= $nextChar;
            $functionNameCharCount++;
        }
        $functionBodyStartPosition = $functionNameCharCount;
        $functionBody = '';
        $functionBodyCharCount = 0;
        $functionValueToken = false;
        while (true) {
            $nextChar = $functionChars[$functionBodyStartPosition + $functionBodyCharCount] ?? null;
            if ($nextChar === null) {
                if ($functionBodyCharCount) {
                    $this->tokenStream->append(new Token(TokenType::T_VALUE, $functionBody, $this->currentLineNumber, $functionBodyCharCount));
                }
                ($this->currentLinebreakCallback)();
                $this->lineStream->append((new InvalidLine())->setTokenStream($this->tokenStream));
                return;
            }
            if ($nextChar === ')') {
                if ($functionBodyCharCount) {
                    $functionValueToken = new Token(TokenType::T_VALUE, $functionBody, $this->currentLineNumber, $this->currentColumnInLine + $functionNameCharCount);
                    $this->tokenStream->append($functionValueToken);
                }
                $this->tokenStream->append(new Token(TokenType::T_FUNCTION_VALUE_STOP, ')', $this->currentLineNumber, $this->currentColumnInLine + $functionNameCharCount + $functionBodyCharCount));
                $functionBodyCharCount++;
                break;
            }
            $functionBody .= $nextChar;
            $functionBodyCharCount++;
        }
        $this->currentColumnInLine = $this->currentColumnInLine + $functionNameCharCount + $functionBodyCharCount;
        $this->currentLineString = substr($this->currentLineString, $functionNameCharCount + $functionBodyCharCount);
        $this->parseTabsAndWhitespaces();
        $this->makeComment();
        $line = (new IdentifierFunctionLine())
            ->setIdentifierTokenStream($this->identifierStream)
            ->setFunctionNameToken($functionNameToken) /** @phpstan-ignore-line phpstan is wrong here. We *know* a $functionNameToken exists at this point. */
            ->setTokenStream($this->tokenStream);
        if ($functionValueToken) {
            $line->setFunctionValueToken($functionValueToken);
        }
        $this->lineStream->append($line);
    }

    private function parseValueForConstants(): void
    {
        if (!str_contains($this->currentLineString, '{$')) {
            $valueToken = new Token(TokenType::T_VALUE, $this->currentLineString, $this->currentLineNumber, $this->currentColumnInLine);
            $this->tokenStream->append($valueToken);
            $this->valueStream->append($valueToken);
            return;
        }
        $splitLine = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $isInConstant = false;
        $currentPosition = 0;
        $currentString = '';
        $currentStringLength = 0;
        $lastTokenEndPosition = 0;
        while (true) {
            $char = $splitLine[$currentPosition] ?? null;
            if ($char === null) {
                if ($currentStringLength) {
                    $valueToken = new Token(TokenType::T_VALUE, $currentString, $this->currentLineNumber, $this->currentColumnInLine + $lastTokenEndPosition);
                    $this->tokenStream->append($valueToken);
                    $this->valueStream->append($valueToken);
                }
                break;
            }
            $nextTwoChars = $char . ($splitLine[$currentPosition + 1] ?? '');
            if ($nextTwoChars === '{$') {
                $isInConstant = true;
                if ($currentStringLength) {
                    $valueToken = new Token(TokenType::T_VALUE, $currentString, $this->currentLineNumber, $this->currentColumnInLine + $lastTokenEndPosition);
                    $this->tokenStream->append($valueToken);
                    $this->valueStream->append($valueToken);
                    $lastTokenEndPosition = $currentPosition;
                }
                $currentString = '{$';
                $currentPosition += 2;
                continue;
            }
            if ($isInConstant && $char === '}') {
                $valueToken = new Token(TokenType::T_CONSTANT, $currentString . '}', $this->currentLineNumber, $this->currentColumnInLine + $lastTokenEndPosition);
                $this->tokenStream->append($valueToken);
                if (!$this->valueStream instanceof ConstantAwareTokenStream) {
                    $this->valueStream = (new ConstantAwareTokenStream())->setAll($this->valueStream->getAll());
                }
                $this->valueStream->append($valueToken);
                $currentPosition++;
                $currentString = '';
                $currentStringLength = 0;
                $lastTokenEndPosition = $currentPosition;
                $isInConstant = false;
                continue;
            }
            $currentPosition++;
            $currentStringLength++;
            $currentString .= $char;
        }
    }
}
