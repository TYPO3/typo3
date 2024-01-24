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
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionElseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionStopLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierAssignmentLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierBlockOpenLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierCopyLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierFunctionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierReferenceLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierUnsetLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportOldLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\ConstantAwareTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierToken;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;

/**
 * A lossy tokenizer implementation: Do not create invalid lines, do not create empty lines,
 * do not create token line and column positions.
 *
 * This tokenizer creates a much smaller streams of only relevant lines. All information
 * not essential for the AstBuilder is skipped. This tokenizer is used in frontend rendering
 * for quicker AST building.
 *
 * An instance of this tokenizer is injected by DI when injecting TokenizerInterface.
 *
 * This class is unit test covered by TokenizerInterfaceTest and paired with LossyTokenizer.
 * Never change anything in this class without additional test coverage!
 *
 * @internal: Internal tokenizer structure.
 */
final class LossyTokenizer implements TokenizerInterface
{
    private LineStream $lineStream;

    private IdentifierTokenStream $identifierStream;
    private TokenStreamInterface $valueStream;

    private array $lines;
    private int $currentLineNumber;
    private string $currentLineString;

    public function tokenize(string $source): LineStream
    {
        $this->lineStream = new LineStream();
        $this->currentLineNumber = -1;
        $this->lines = $this->splitLines($source);

        while (true) {
            $this->currentLineNumber++;
            if (!array_key_exists($this->currentLineNumber, $this->lines)) {
                break;
            }
            $this->currentLineString = trim($this->lines[$this->currentLineNumber]['line']);
            $nextChar = substr($this->currentLineString, 0, 1);
            if ($nextChar === '') {
                continue;
            }
            $nextTwoChars = substr($this->currentLineString, 0, 2);
            if ($nextChar === '#' || $nextTwoChars === '//') {
                continue;
            }
            if ($nextTwoChars === '/*') {
                // @todo: This is one of multiple places where multiline "/*" comments are parsed in this tokenizer. Other
                //        places are cluttered in detail methods. It might be more straight to have an early scanning
                //        phase through all lines to remove comments up front, to not wire especially the multiline comment
                //        parsing to single places, and throw away commented lines early. This isn't trivial though, since
                //        for instance "foo = bar /* not a comment */" then needs to be sorted out, too. Having an early
                //        "kick comments" loop however might be quicker in the end and would make the main parsing
                //        methods more concise and probably more bullet proof.
                //        Also note there are currently not-unit-tested edge cases, that will currently not parse as
                //        (maybe) expected. In the example below, "foo2 = bar2" is ignored. This is an issue with the
                //        LosslessTokenizer as well, probably, and we may rather want to declare this as invalid syntax?!
                //          foo = bar /* comment start
                //          comment end */ foo2 = bar2
                $this->ignoreUntilEndOfMultilineComment();
                continue;
            }
            if ($nextChar === '[') {
                $this->createConditionLine();
            } elseif ($nextChar === '}') {
                $this->lineStream->append((new BlockCloseLine()));
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

    private function splitLines($source): array
    {
        $vanillaLines = explode(chr(10), $source);
        return array_map(
            fn(int $lineNumber, string $vanillaLine): array => [
                'line' => rtrim($vanillaLine, "\r"),
            ],
            array_keys($vanillaLines),
            $vanillaLines
        );
    }

    private function ignoreUntilEndOfMultilineComment(): void
    {
        while (true) {
            if (str_contains($this->currentLineString, '*/')) {
                return;
            }
            if (!array_key_exists($this->currentLineNumber + 1, $this->lines)) {
                return;
            }
            $this->currentLineNumber++;
            $this->currentLineString = trim($this->lines[$this->currentLineNumber]['line']);
        }
    }

    /**
     * Create a condition line from token stream of this line.
     */
    private function createConditionLine(): void
    {
        $upperCaseLine = strtoupper($this->currentLineString);
        if (str_starts_with($upperCaseLine, '[ELSE]')) {
            $this->lineStream->append((new ConditionElseLine()));
            $this->currentLineString = trim(substr($this->currentLineString, 6));
            if (str_starts_with($this->currentLineString, '/*')) {
                $this->ignoreUntilEndOfMultilineComment();
            }
            return;
        }
        if (str_starts_with($upperCaseLine, '[END]')) {
            $this->lineStream->append((new ConditionStopLine()));
            $this->currentLineString = trim(substr($this->currentLineString, 5));
            if (str_starts_with($this->currentLineString, '/*')) {
                $this->ignoreUntilEndOfMultilineComment();
            }
            return;
        }
        if (str_starts_with($upperCaseLine, '[GLOBAL]')) {
            $this->lineStream->append((new ConditionStopLine()));
            $this->currentLineString = trim(substr($this->currentLineString, 8));
            if (str_starts_with($this->currentLineString, '/*')) {
                $this->ignoreUntilEndOfMultilineComment();
            }
            return;
        }
        $conditionBody = '';
        $conditionBodyCharCount = 0;
        $conditionBodyChars = mb_str_split(substr($this->currentLineString, 1), 1, 'UTF-8');
        $bracketCount = 1;
        while (true) {
            $nextChar = $conditionBodyChars[$conditionBodyCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
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
                        $conditionBodyToken = new Token(TokenType::T_VALUE, $conditionBody);
                        $this->lineStream->append((new ConditionLine())->setValueToken($conditionBodyToken));
                        $conditionBodyCharCount++;
                        break;
                    }
                    $conditionBodyCharCount++;
                    break;
                }
                $conditionBody .= $nextChar;
                $conditionBodyCharCount++;
                continue;
            }
            $conditionBody .= $nextChar;
            $conditionBodyCharCount++;
        }
        $this->currentLineString = trim(mb_substr($this->currentLineString, $conditionBodyCharCount + 1));
        if (str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseBlockStart(): void
    {
        $this->currentLineString = trim(substr($this->currentLineString, 1));
        if (str_starts_with($this->currentLineString, '}')) {
            // Edge case: foo = { } in one line. Note content within {} is not parsed, everything behind { ends up as comment.
            $this->lineStream->append((new IdentifierBlockOpenLine())->setIdentifierTokenStream($this->identifierStream));
            $this->lineStream->append((new BlockCloseLine()));
            return;
        }
        $this->lineStream->append((new IdentifierBlockOpenLine())->setIdentifierTokenStream($this->identifierStream));
    }

    private function parseImportLine(): void
    {
        $this->currentLineString = trim(substr($this->currentLineString, 7));

        // Next char should be the opening tick or doubletick, otherwise treat it as ignored comment
        $nextChar = substr($this->currentLineString, 0, 1);
        if ($nextChar !== '\'' && $nextChar !== '"') {
            return;
        }

        $importBody = '';
        $importBodyCharCount = 0;
        $importBodyChars = mb_str_split(substr($this->currentLineString, 1), 1, 'UTF-8');
        while (true) {
            $nextChar = $importBodyChars[$importBodyCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                if ($importBodyCharCount) {
                    $importBodyToken = (new Token(TokenType::T_VALUE, $importBody));
                    $this->lineStream->append((new ImportLine())->setValueToken($importBodyToken));
                    return;
                }
                return;
            }
            if ($nextChar === '\'' || $nextChar === '"') {
                if ($importBodyCharCount) {
                    $importBodyToken = new Token(TokenType::T_VALUE, $importBody);
                    $this->lineStream->append((new ImportLine())->setValueToken($importBodyToken));
                    break;
                }
                break;
            }
            $importBody .= $nextChar;
            $importBodyCharCount++;
        }
        $this->currentLineString = trim(mb_substr($this->currentLineString, $importBodyCharCount + 2));
        if (str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    /**
     * Parse everything behind <INCLUDE_TYPOSCRIPT: at least until end of line or
     * more if there is a multiline comment at end.
     */
    private function parseImportOld(): void
    {
        $this->currentLineString = substr($this->currentLineString, 20);
        $importBody = '';
        $importBodyCharCount = 0;
        $importBodyChars = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $isWithinDoubleTick = false;
        $previousCharWasQuote = false;
        while (true) {
            $nextChar = $importBodyChars[$importBodyCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                if ($importBodyCharCount) {
                    $importBodyToken = (new Token(TokenType::T_VALUE, $importBody));
                    $this->lineStream->append((new ImportOldLine())->setValueToken($importBodyToken));
                    return;
                }
                return;
            }
            if ($nextChar === '"' && !$previousCharWasQuote) {
                $isWithinDoubleTick = !$isWithinDoubleTick;
            }
            $previousCharWasQuote = $nextChar === '\\';
            if ($nextChar === '>' && !$isWithinDoubleTick) {
                if ($importBodyCharCount) {
                    $importBodyToken = new Token(TokenType::T_VALUE, $importBody);
                    $this->lineStream->append((new ImportOldLine())->setValueToken($importBodyToken));
                    break;
                }
                break;
            }
            $importBody .= $nextChar;
            $importBodyCharCount++;
        }
        $this->currentLineString = trim(mb_substr($this->currentLineString, $importBodyCharCount + 2));
        if (str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseIdentifier(): void
    {
        $splitLine = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $currentPosition = $this->parseIdentifierUntilStopChar($splitLine);
        if (!$currentPosition) {
            return;
        }
        $this->currentLineString = trim(substr($this->currentLineString, $currentPosition));
        $nextChar = substr($this->currentLineString, 0, 1);
        $nextTwoChars = $nextChar . substr($this->currentLineString, 1, 1);
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
        }
        if ($nextTwoChars === '/*') {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseOperatorUnset(): void
    {
        $this->lineStream->append((new IdentifierUnsetLine())->setIdentifierTokenStream($this->identifierStream));
        $this->currentLineString = trim(trim(trim($this->currentLineString), '>'));
        if (str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseOperatorAssignment(): void
    {
        $this->currentLineString = trim(substr($this->currentLineString, 1));
        $this->valueStream = $this->parseValueForConstants(new TokenStream(), $this->currentLineString);
        $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
    }

    private function parseOperatorMultilineAssignment(): void
    {
        $this->valueStream = new TokenStream();
        $this->currentLineString = substr($this->currentLineString, 1);
        // True if we're currently in the line with the opening '('
        $isFirstLine = true;
        // True if the first line has a first value token: "foo ( thisIsTheFirstValueToken"
        $valueOnFirstLine = false;
        // True if the line after '(' is parsed
        $isSecondLine = false;
        while (true) {
            if (str_starts_with(ltrim($this->currentLineString), ')')) {
                $this->currentLineString = trim(substr($this->currentLineString, 1));
                if (!$this->valueStream->isEmpty()) {
                    $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
                }
                if (str_starts_with($this->currentLineString, '/*')) {
                    $this->ignoreUntilEndOfMultilineComment();
                }
                return;
            }
            if ($isFirstLine && str_ends_with($this->currentLineString, ')')) {
                $this->currentLineString = substr($this->currentLineString, 0, -1);
                if (strlen($this->currentLineString) > 1) {
                    $this->valueStream = $this->parseValueForConstants($this->valueStream, $this->currentLineString);
                    $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
                    return;
                }
                return;
            }
            if ($isFirstLine && strlen($this->currentLineString)) {
                $this->valueStream = $this->parseValueForConstants($this->valueStream, $this->currentLineString);
                $valueOnFirstLine = true;
            }
            if (($isFirstLine && $valueOnFirstLine)
                || (!$isFirstLine && !$isSecondLine)
            ) {
                $this->valueStream->append(new Token(TokenType::T_NEWLINE, "\n"));
            }
            if (!$isFirstLine && strlen($this->currentLineString)) {
                $this->valueStream = $this->parseValueForConstants($this->valueStream, $this->currentLineString);
            }
            if (!array_key_exists($this->currentLineNumber + 1, $this->lines)) {
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
            $this->currentLineString = $this->lines[$this->currentLineNumber]['line'];
        }
    }

    private function parseOperatorCopy(): void
    {
        $this->currentLineString = trim(substr($this->currentLineString, 1));
        $identifierStream = $this->identifierStream;
        $charsHandled = $this->parseIdentifierAtEndOfLine();
        $referenceStream = $this->identifierStream;
        if ($referenceStream->isEmpty()) {
            return;
        }
        $this->lineStream->append(
            (new IdentifierCopyLine())
                ->setIdentifierTokenStream($identifierStream)
                ->setValueTokenStream($referenceStream)
        );
        $this->currentLineString = trim(mb_substr($this->currentLineString, $charsHandled));
        if (str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseOperatorReference(): void
    {
        $this->currentLineString = trim(substr($this->currentLineString, 2));
        $identifierStream = $this->identifierStream;
        $charsHandled = $this->parseIdentifierAtEndOfLine();
        $referenceStream = $this->identifierStream;
        if ($referenceStream->isEmpty()) {
            return;
        }
        $this->lineStream->append(
            (new IdentifierReferenceLine())
                ->setIdentifierTokenStream($identifierStream)
                ->setValueTokenStream($referenceStream)
        );
        $this->currentLineString = trim(mb_substr($this->currentLineString, $charsHandled));
        if (str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseIdentifierAtEndOfLine(): int
    {
        $this->identifierStream = new IdentifierTokenStream();
        $isRelative = false;
        $splitLine = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $char = $splitLine[0] ?? null;
        if ($char === null) {
            return 0;
        }
        $nextTwoChars = $char . ($splitLine[1] ?? '');
        if ($char === '.') {
            // A relative right side: foo.bar < .foo (note the dot!). we identifierStream->setRelative() and
            // get rid of the dot for the rest of the processing.
            $isRelative = true;
            array_shift($splitLine);
            $this->currentLineString = substr($this->currentLineString, 1);
        }
        if ($char === '#') {
            return 1;
        }
        if ($nextTwoChars === '//') {
            return 2;
        }
        if ($nextTwoChars === '/*') {
            $this->ignoreUntilEndOfMultilineComment();
            return 0;
        }
        return $this->parseIdentifierUntilStopChar($splitLine, $isRelative);
    }

    private function parseIdentifierUntilStopChar(array $splitLine, bool $isRelative = false): int
    {
        $this->identifierStream = new IdentifierTokenStream();
        if ($isRelative) {
            $this->identifierStream->setRelative();
        }
        $currentPosition = 0;
        $currentIdentifierBody = '';
        $currentIdentifierCharCount = 0;
        while (true) {
            $nextChar = $splitLine[$currentPosition] ?? null;
            if ($nextChar === null) {
                if ($currentIdentifierCharCount) {
                    $identifierToken = new IdentifierToken(TokenType::T_IDENTIFIER, $currentIdentifierBody);
                    $this->identifierStream->append($identifierToken);
                }
                return $currentPosition;
            }
            $nextTwoChars = $nextChar . ($splitLine[$currentPosition + 1] ?? null);
            if ($currentPosition > 0
                && ($nextChar === ' ' || $nextChar === "\t" || $nextChar === '=' || $nextChar === '<' || $nextChar === '>' || $nextChar === '{' || $nextTwoChars === ':=' || $nextChar === '(')
            ) {
                if ($currentIdentifierCharCount) {
                    $identifierToken = new IdentifierToken(TokenType::T_IDENTIFIER, $currentIdentifierBody);
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
                    $identifierToken = new IdentifierToken(TokenType::T_IDENTIFIER, $currentIdentifierBody);
                    $this->identifierStream->append($identifierToken);
                    $currentIdentifierCharCount = 0;
                    $currentIdentifierBody = '';
                }
                $currentPosition++;
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
        $this->currentLineString = trim(substr($this->currentLineString, 2));
        if ($this->currentLineString === '') {
            return;
        }
        $functionName = '';
        $functionNameCharCount = 0;
        $functionChars = mb_str_split($this->currentLineString, 1, 'UTF-8');
        while (true) {
            $nextChar = $functionChars[$functionNameCharCount] ?? null;
            if ($nextChar === null) {
                // end of chars
                return;
            }
            if ($nextChar === '(') {
                if ($functionNameCharCount) {
                    $functionNameToken = new Token(TokenType::T_FUNCTION_NAME, $functionName);
                    $functionNameCharCount++;
                    break;
                }
                return;
            }
            $functionName .= $nextChar;
            $functionNameCharCount++;
        }
        $functionBodyStartPosition = $functionNameCharCount;
        $functionBodyPart = '';
        $functionBodyCharCount = 0;
        $functionValueStream = new TokenStream();
        while (true) {
            $nextChar = $functionChars[$functionBodyStartPosition + $functionBodyCharCount] ?? null;
            if ($nextChar === null) {
                return;
            }
            if ($nextChar === ')') {
                if ($functionBodyCharCount) {
                    $functionValueStream = $this->parseValueForConstants($functionValueStream, $functionBodyPart);
                    $functionBodyCharCount++;
                }
                break;
            }
            $functionBodyPart .= $nextChar;
            $functionBodyCharCount++;
        }
        $this->lineStream->append(
            (new IdentifierFunctionLine())
                ->setIdentifierTokenStream($this->identifierStream)
                ->setFunctionNameToken($functionNameToken) /** @phpstan-ignore-line phpstan is wrong here. We *know* a $functionNameToken exists. */
                ->setFunctionValueTokenStream($functionValueStream)
        );
        // Check for multiline comment
        $this->currentLineString = implode('', array_slice($functionChars, $functionBodyStartPosition + $functionBodyCharCount + 1));
        if (mb_strlen($this->currentLineString) >= 1 && str_starts_with($this->currentLineString, '/*')) {
            $this->ignoreUntilEndOfMultilineComment();
        }
    }

    private function parseValueForConstants(TokenStreamInterface $valueStream, string $value): TokenStreamInterface
    {
        if (!str_contains($value, '{$')) {
            $valueStream->append(new Token(TokenType::T_VALUE, $value));
            return $valueStream;
        }
        $splitLine = mb_str_split($value, 1, 'UTF-8');
        $isInConstant = false;
        $currentPosition = 0;
        $currentString = '';
        $currentStringLength = 0;
        while (true) {
            $char = $splitLine[$currentPosition] ?? null;
            if ($char === null) {
                if ($currentStringLength) {
                    $valueToken = new Token(TokenType::T_VALUE, $currentString);
                    $valueStream->append($valueToken);
                }
                break;
            }
            $nextTwoChars = $char . ($splitLine[$currentPosition + 1] ?? '');
            if ($nextTwoChars === '{$') {
                $isInConstant = true;
                if ($currentStringLength) {
                    $valueToken = new Token(TokenType::T_VALUE, $currentString);
                    $valueStream->append($valueToken);
                }
                $currentString = '{$';
                $currentPosition += 2;
                continue;
            }
            if ($isInConstant && $char === '}') {
                $valueToken = new Token(TokenType::T_CONSTANT, $currentString . '}');
                if (!$valueStream instanceof ConstantAwareTokenStream) {
                    $valueStream = (new ConstantAwareTokenStream())->setAll($valueStream->getAll());
                }
                $valueStream->append($valueToken);
                $currentPosition++;
                $currentString = '';
                $currentStringLength = 0;
                $isInConstant = false;
                continue;
            }
            $currentPosition++;
            $currentStringLength++;
            $currentString .= $char;
        }
        return $valueStream;
    }
}
