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

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\BlockCloseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierBlockOpenLine;
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
     * @var array {int, array{type: string, include: IncludeInterface, line: LineInterface}}
     */
    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
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
                $braceCount ++;
            }
            if ($line instanceof BlockCloseLine) {
                $braceCount --;
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

        // Add the line number of the first token of the line object to the error array.
        // Not strictly needed, but more convenient in Fluid template to render.
        foreach ($this->errors as &$error) {
            /** @var LineInterface $line */
            $line = $error['line'];
            $error['lineNumber'] = $line->getTokenStream()->reset()->peekNext()->getLine();
        }
    }
}
