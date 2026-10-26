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

namespace TYPO3\CMS\Install\ExtensionScanner\Php\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

/**
 * A visitor doing some counting.
 * It sums the number of ignored lines and lines of effective code.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class CodeStatistics extends NodeVisitorAbstract
{
    /**
     * True if a class statement has @extensionScannerIgnoreFile
     */
    protected bool $isCurrentFileIgnored = false;

    /**
     * Counts @extensionScannerIgnoreLine statements
     */
    protected int $numberOfIgnoreLines = 0;

    /**
     * Number of effective code lines - class and method statements, function calls ...
     */
    protected int $numberOfEffectiveCodeLines = 0;

    /**
     * Current line number given not is in, runtime helper var
     */
    protected int $currentLineNumber = 0;

    /**
     * Called by PhpParser during traversal.
     */
    public function enterNode(Node $node): null
    {
        $startLineOfNode = $node->getStartLine();
        if ($startLineOfNode !== $this->currentLineNumber) {
            $this->currentLineNumber = $startLineOfNode;
            $this->numberOfEffectiveCodeLines++;

            // Class statements may contain the @extensionScannerIgnoreFile statements
            if ($node instanceof Class_) {
                $comments = $node->getComments();
                if ($comments !== []) {
                    foreach ($comments as $comment) {
                        if (str_contains($comment->getText(), '@extensionScannerIgnoreFile')) {
                            $this->isCurrentFileIgnored = true;
                            break;
                        }
                    }
                }
            }

            // First node of line may contain the @extensionScannerIgnoreLine comment
            $comments = $node->getComments();
            if ($comments !== []) {
                foreach ($comments as $comment) {
                    if (str_contains($comment->getText(), '@extensionScannerIgnoreLine')) {
                        $this->numberOfIgnoreLines++;
                        break;
                    }
                }
            }
        }
        return null;
    }

    /**
     * True if a @extensionScannerIgnoreFile has been found.
     * Called externally *after* traversing
     */
    public function isFileIgnored(): bool
    {
        return $this->isCurrentFileIgnored;
    }

    /**
     * Number of "effective" code lines: No comments, no empty lines,
     * but "class" statements, "function" statements, "use xy", etc.
     * Called externally *after* traversing
     */
    public function getNumberOfEffectiveCodeLines(): int
    {
        return $this->numberOfEffectiveCodeLines;
    }

    /**
     * Returns number of found @extensionScannerIgnoreLine comments
     * Called externally *after* traversing
     */
    public function getNumberOfIgnoredLines(): int
    {
        return $this->numberOfIgnoreLines;
    }
}
