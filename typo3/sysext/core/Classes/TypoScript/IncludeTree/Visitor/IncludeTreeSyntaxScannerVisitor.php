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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\AtImportInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionElseInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionIncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\BlockCloseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierBlockOpenLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportOldLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\InvalidLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineInterface;

/**
 * This implements a simple TypoScript syntax scanner. It is used in page TSconfig
 * and TypoScript "include" submodules to find and show broken syntax.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class IncludeTreeSyntaxScannerVisitor implements IncludeTreeVisitorInterface
{
    /**
     * @var list<array{type: string, include: IncludeInterface, line: LineInterface, lineNumber: int}>
     */
    private array $errors = [];

    /**
     * @return list<array{type: string, include: IncludeInterface, line: LineInterface, lineNumber: int}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void {}

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        $this->brokenLinesAndBraces($include);
        $this->emptyImports($include);

        // Add the line number of the first token of the line object to the error array.
        // Not strictly needed, but more convenient in Fluid template to render.
        foreach ($this->errors as &$error) {
            /** @var LineInterface $line */
            $line = $error['line'];
            $error['lineNumber'] = $line->getTokenStream()->reset()->peekNext()->getLine();
        }

        // Sort array by line number to list them top->bottom in view.
        usort($this->errors, fn($a, $b) => $a['lineNumber'] <=> $b['lineNumber']);
    }

    /**
     * Scan for invalid lines ("foo.bar <" is invalid since there must be something after "<"),
     * and scan for "too many" and "not enough" "}" braces.
     */
    private function brokenLinesAndBraces(IncludeInterface $include): void
    {
        if ($include->isSplit()) {
            // If this node is split, don't check for syntax errors, this is
            // done for child nodes.
            return;
        }
        $lineStream = $include->getLineStream();
        if (!$lineStream) {
            return;
        }
        $braceCount = 0;
        $lastLine = null;
        foreach ($lineStream->getNextLine() as $line) {
            $lastLine = $line;
            if ($line instanceof InvalidLine) {
                $this->errors[] = [
                    'type' => 'line.invalid',
                    'include' => $include,
                    'line' => $line,
                ];
            }
            if ($line instanceof IdentifierBlockOpenLine) {
                $braceCount++;
            }
            if ($line instanceof BlockCloseLine) {
                $braceCount--;
                if ($braceCount < 0) {
                    $braceCount = 0;
                    $this->errors[] = [
                        'type' => 'brace.excess',
                        'include' => $include,
                        'line' => $line,
                    ];
                }
            }
        }
        if ($braceCount !== 0) {
            $this->errors[] = [
                'type' => 'brace.missing',
                'include' => $include,
                'line' => $lastLine,
            ];
        }
    }

    /**
     * Look for @import and INCLUDE_TYPOSCRIPT that don't find to-include file(s).
     *
     * @todo: This code is far more complex than it could be. See #102102 and #102103 for
     *        changes we should apply to the include tree structure to simplify this.
     */
    private function emptyImports(IncludeInterface $include): void
    {
        if (!$include->isSplit()) {
            // Nodes containing @import are always split
            return;
        }
        $lineStream = $include->getLineStream();
        if (!$lineStream) {
            // A node that is split should never have an empty line stream,
            // this may be obsolete, but does not hurt much.
            return;
        }
        // Find @import lines in this include, index by
        // combination of line number and column position.
        $allImportLines = [];
        foreach ($lineStream->getNextLine() as $line) {
            if ($line instanceof ImportLine || $line instanceof ImportOldLine) {
                $valueToken = $line->getValueToken();
                $allImportLines[$valueToken->getLine() . '-' . $valueToken->getColumn()] = $line;
            }
        }
        // Now iterate children to exclude valid allImportLines, those that included something.
        foreach ($include->getNextChild() as $child) {
            if ($child instanceof AtImportInclude || $child instanceof IncludeTyposcriptInclude) {
                /** @var ImportLine|ImportOldLine $originalLine */
                $originalLine = $child->getOriginalLine();
                $valueToken = $originalLine->getValueToken();
                unset($allImportLines[$valueToken->getLine() . '-' . $valueToken->getColumn()]);
            }
            // Condition includes don't have the "body" lines itself (or a "body" sub node). This may change,
            // but until then we'll have to scan the parent node and loop condition includes here to find out
            // which of them resolved to child nodes.
            if ($child instanceof ConditionInclude
                || $child instanceof ConditionElseInclude
                || $child instanceof ConditionIncludeTyposcriptInclude
            ) {
                foreach ($child->getNextChild() as $conditionChild) {
                    if ($conditionChild instanceof AtImportInclude || $conditionChild instanceof IncludeTyposcriptInclude) {
                        /** @var ImportLine|ImportOldLine $originalLine */
                        $originalLine = $conditionChild->getOriginalLine();
                        $valueToken = $originalLine->getValueToken();
                        unset($allImportLines[$valueToken->getLine() . '-' . $valueToken->getColumn()]);
                    }
                }
            }
        }
        // Everything left are invalid includes
        foreach ($allImportLines as $importLine) {
            $this->errors[] = [
                'type' => 'import.empty',
                'include' => $include,
                'line' => $importLine,
            ];
        }
    }
}
