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

namespace TYPO3\CMS\Core\TypoScript\AST;

use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;

/**
 * The main TypoScript AST builder.
 *
 * This creates a tree of Nodes, starting with the root node. Each node can have
 * children. The implementation basically iterates a LineStream created by the
 * tokenizers, and creates AST depending on the line type. It handles all the
 * different operator lines like "=", "<" and so on.
 *
 * @internal: Internal AST structure.
 */
interface AstBuilderInterface
{
    /**
     * @param array<string, string> $flatConstants
     */
    public function build(LineStream $lineStream, RootNode $ast, array $flatConstants = []): RootNode;
}
