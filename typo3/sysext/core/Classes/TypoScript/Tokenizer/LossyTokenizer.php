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

    private int $currentColumnInLine = 0;

    public function tokenize(string $source): LineStream
    {
        $this->lineStream = new LineStream();
        $this->currentLineNumber = -1;
        $this->lines = [];
        $this->splitLines($source);

        while (true) {
            $this->currentLineNumber++;
            if (!array_key_exists($this->currentLineNumber, $this->lines)) {
                break;
            }
            $this->currentColumnInLine = 0;
            $this->currentLineString = trim($this->lines[$this->currentLineNumber]['line']);
            $nextChar = substr($this->currentLineString, 0, 1);
            if ($nextChar === '') {
                continue;
            }
            $nextTwoChars = substr($this->currentLineString, 0, 2);
            if ($nextChar === '#' || $nextTwoChars === '//' || $nextChar === '/*') {
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

    private function splitLines($source): void
    {
        $vanillaLines = explode(chr(10), $source);
        $this->lines = array_map(
            fn (int $lineNumber, string $vanillaLine): array => [
                'line' => rtrim($vanillaLine, "\r"),
            ],
            array_keys($vanillaLines),
            $vanillaLines
        );
    }

    /**
     * Add tabs and whitespaces until some different char appears.
     */
    private function parseTabsAndWhitespaces(): void
    {
        $matches = [];
        if (preg_match('#^(\s+)(.*)$#', $this->currentLineString, $matches)) {
            $this->currentLineString = $matches[2];
            $this->currentColumnInLine = $this->currentColumnInLine + strlen($matches[1]);
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
            return;
        }
        if (str_starts_with($upperCaseLine, '[END]')) {
            $this->lineStream->append((new ConditionStopLine()));
            return;
        }
        if (str_starts_with($upperCaseLine, '[GLOBAL]')) {
            $this->lineStream->append((new ConditionStopLine()));
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
                        return;
                    }
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

    private function parseBlockStart(): void
    {
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
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
        $this->currentColumnInLine += 7;
        $this->currentLineString = substr($this->currentLineString, 7);
        $this->parseTabsAndWhitespaces();

        // Next char should be the opening tick or doubletick, otherwise we create a comment until end of line
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
                    return;
                }
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
        $this->currentColumnInLine += 20;
        $this->currentLineString = substr($this->currentLineString, 20);
        $importBody = '';
        $importBodyCharCount = 0;
        $importBodyChars = mb_str_split($this->currentLineString, 1, 'UTF-8');
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
            if ($nextChar === '>') {
                if ($importBodyCharCount) {
                    $importBodyToken = new Token(TokenType::T_VALUE, $importBody);
                    $this->lineStream->append((new ImportOldLine())->setValueToken($importBodyToken));
                    return;
                }
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
            $this->lineStream->append((new IdentifierUnsetLine())->setIdentifierTokenStream($this->identifierStream));
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
    }

    private function parseOperatorAssignment(): void
    {
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
        $this->valueStream = new TokenStream();
        $this->parseValueForConstants();
        $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
    }

    private function parseOperatorMultilineAssignment(): void
    {
        $this->valueStream = new TokenStream();
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        // True if we're currently in the line with the opening '('
        $isFirstLine = true;
        // True if the first line has a first value token: "foo ( thisIsTheFirstValueToken"
        $valueOnFirstLine = false;
        // True if the line after '(' is parsed
        $isSecondLine = false;
        while (true) {
            if (str_starts_with(ltrim($this->currentLineString), ')')) {
                $this->currentLineString = substr($this->currentLineString, 1);
                $this->currentColumnInLine++;
                $this->parseTabsAndWhitespaces();
                if (!$this->valueStream->isEmpty()) {
                    $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
                }
                return;
            }
            if ($isFirstLine && str_ends_with($this->currentLineString, ')')) {
                $this->currentLineString = substr($this->currentLineString, 0, -1);
                if (strlen($this->currentLineString) > 1) {
                    $this->parseValueForConstants();
                    $this->lineStream->append((new IdentifierAssignmentLine())->setIdentifierTokenStream($this->identifierStream)->setValueTokenStream($this->valueStream));
                    return;
                }
                return;
            }
            if ($isFirstLine && strlen($this->currentLineString)) {
                $this->parseValueForConstants();
                $valueOnFirstLine = true;
            }
            if (($isFirstLine && $valueOnFirstLine)
                || (!$isFirstLine && !$isSecondLine)
            ) {
                $this->valueStream->append(new Token(TokenType::T_NEWLINE, "\n"));
            }
            if (!$isFirstLine && strlen($this->currentLineString)) {
                $this->parseValueForConstants();
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
            $this->currentColumnInLine = 0;
            $this->currentLineString = $this->lines[$this->currentLineNumber]['line'];
        }
    }

    private function parseOperatorCopy(): void
    {
        $this->currentColumnInLine ++;
        $this->currentLineString = substr($this->currentLineString, 1);
        $this->parseTabsAndWhitespaces();
        $identifierStream = $this->identifierStream;
        $this->parseIdentifierAtEndOfLine();
        $referenceStream = $this->identifierStream;
        if ($referenceStream->isEmpty()) {
            return;
        }
        $this->lineStream->append(
            (new IdentifierCopyLine())
                ->setIdentifierTokenStream($identifierStream)
                ->setValueTokenStream($referenceStream)
        );
    }

    private function parseOperatorReference(): void
    {
        $this->currentColumnInLine += 2;
        $this->currentLineString = substr($this->currentLineString, 2);
        $this->parseTabsAndWhitespaces();
        $identifierStream = $this->identifierStream;
        $this->parseIdentifierAtEndOfLine();
        $referenceStream = $this->identifierStream;
        if ($referenceStream->isEmpty()) {
            return;
        }
        $this->lineStream->append(
            (new IdentifierReferenceLine())
                ->setIdentifierTokenStream($identifierStream)
                ->setValueTokenStream($referenceStream)
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
            array_shift($splitLine);
            $this->currentColumnInLine++;
            $this->currentLineString = substr($this->currentLineString, 1);
        }
        if ($char === '#' || $nextTwoChars === '//' || $nextTwoChars === '/*') {
            return;
        }
        $this->parseIdentifierUntilStopChar($splitLine, $isRelative);
    }

    private function parseIdentifierUntilStopChar(array $splitLine, bool $isRelative = false): ?int
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
                return null;
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
        $this->currentColumnInLine += 2;
        $this->currentLineString = substr($this->currentLineString, 2);
        $this->parseTabsAndWhitespaces();
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
        $functionBody = '';
        $functionBodyCharCount = 0;
        $functionValueToken = false;
        while (true) {
            $nextChar = $functionChars[$functionBodyStartPosition + $functionBodyCharCount] ?? null;
            if ($nextChar === null) {
                return;
            }
            if ($nextChar === ')') {
                if ($functionBodyCharCount) {
                    $functionValueToken = new Token(TokenType::T_VALUE, $functionBody);
                }
                break;
            }
            $functionBody .= $nextChar;
            $functionBodyCharCount++;
        }
        $line = (new IdentifierFunctionLine())
            ->setIdentifierTokenStream($this->identifierStream)
            ->setFunctionNameToken($functionNameToken); /** @phpstan-ignore-line phpstan is wrong here. We *know* a $functionNameToken exists at this point. */
        if ($functionValueToken) {
            $line->setFunctionValueToken($functionValueToken);
        }
        $this->lineStream->append($line);
    }

    private function parseValueForConstants(): void
    {
        if (!str_contains($this->currentLineString, '{$')) {
            $valueToken = new Token(TokenType::T_VALUE, $this->currentLineString);
            $this->valueStream->append($valueToken);
            return;
        }
        $splitLine = mb_str_split($this->currentLineString, 1, 'UTF-8');
        $isInConstant = false;
        $currentPosition = 0;
        $currentString = '';
        $currentStringLength = 0;
        while (true) {
            $char = $splitLine[$currentPosition] ?? null;
            if ($char === null) {
                if ($currentStringLength) {
                    $valueToken = new Token(TokenType::T_VALUE, $currentString);
                    $this->valueStream->append($valueToken);
                }
                break;
            }
            $nextTwoChars = $char . ($splitLine[$currentPosition + 1] ?? '');
            if ($nextTwoChars === '{$') {
                $isInConstant = true;
                if ($currentStringLength) {
                    $valueToken = new Token(TokenType::T_VALUE, $currentString);
                    $this->valueStream->append($valueToken);
                }
                $currentString = '{$';
                $currentPosition += 2;
                continue;
            }
            if ($isInConstant && $char === '}') {
                $valueToken = new Token(TokenType::T_CONSTANT, $currentString . '}');
                if (!$this->valueStream instanceof ConstantAwareTokenStream) {
                    $this->valueStream = (new ConstantAwareTokenStream())->setAll($this->valueStream->getAll());
                }
                $this->valueStream->append($valueToken);
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
    }
}
