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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree;

use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\StringInclude;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;

/**
 * Parse a single TypoScript string, supporting imports and conditions.
 *
 * This is a relatively simple "tree" builder: It gets a single TypoScript string
 * snippet, tokenizes it and creates a RootInclude "tree". The string is scanned
 * for imports and conditions: Those create sub includes, just like the other
 * TreeBuilder classes do.
 *
 * @internal
 */
final class StringTreeBuilder
{
    public function __construct(
        private readonly TreeFromLineStreamBuilder $treeFromTokenStreamBuilder,
    ) {}

    /**
     * Create tree, ready to be traversed. Will cache if $cache is not null.
     *
     * @param non-empty-string $name A name used as cache identifier, [a-z,A-Z,-] only
     */
    public function getTreeFromString(
        string $name,
        string $typoScriptString,
        TokenizerInterface $tokenizer,
        ?PhpFrontend $cache = null,
    ): RootInclude {
        $lowerCaseName = mb_strtolower($name);
        $identifier = 'string-' . $lowerCaseName . '-' . hash('xxh3', $typoScriptString);
        if ($cache) {
            $includeTree = $cache->require($identifier);
            if ($includeTree instanceof RootInclude) {
                return $includeTree;
            }
        }
        $includeTree = new RootInclude();
        $includeNode = new StringInclude();
        $includeNode->setName('[string] ' . $name);
        $includeNode->setLineStream($tokenizer->tokenize($typoScriptString));
        $this->treeFromTokenStreamBuilder->buildTree($includeNode, 'other', $tokenizer);
        $includeTree->addChild($includeNode);
        $cache?->set($identifier, $this->prepareTreeForCache($includeTree));
        return $includeTree;
    }

    private function prepareTreeForCache(RootInclude $node): string
    {
        return 'return unserialize(\'' . addcslashes(serialize($node), '\'\\') . '\');';
    }
}
