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

namespace TYPO3\CMS\Core\TypoScript;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilderInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\IncludeTree\StringTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;

/**
 * A factory to create the AST object tree for a given TypoScript snippet.
 *
 * This is used by some consumers in the core that parse a TypoScript a-like
 * syntax that is not Frontend TypoScript and TsConfig directly.
 */
final class TypoScriptStringFactory
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly TokenizerInterface $tokenizer,
    ) {
    }

    /**
     * Parse a single string and support imports and conditions, cache optionally.
     *
     * @param non-empty-string $name A name used as cache identifier, [a-z,A-Z,-] only
     */
    public function parseFromStringWithIncludes(string $name, string $typoScript): RootNode
    {
        $cacheManager = $this->container->get(CacheManager::class);
        /** @var PhpFrontend $cache */
        $cache = $cacheManager->getCache('typoscript');
        $stringTreeBuilder = $this->container->get(StringTreeBuilder::class);
        $includeTree = $stringTreeBuilder->getTreeFromString($name, $typoScript, $this->tokenizer, $cache);
        $includeTreeTraverserConditionVerdictAware = new IncludeTreeTraverser();
        $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
        $includeTreeTraverserConditionVerdictAware->traverse($includeTree, [$astBuilderVisitor]);
        return $astBuilderVisitor->getAst();
    }

    /**
     * Parse a single string *not* supporting imports, conditions and caching.
     * Detail method used in install tool and in a couple of other special cases.
     *
     * @internal
     */
    public function parseFromString(string $typoScript, AstBuilderInterface $astBuilder): RootNode
    {
        $lineStream = $this->tokenizer->tokenize($typoScript);
        return $astBuilder->build($lineStream, new RootNode());
    }
}
