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
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher as BackendConditionMatcher;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionMatcherVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calculate UserTsConfig. This does the heavy lifting additionally supported by
 * TsConfigTreeBuilder: Load basic userTsConfig tree, then build the UserTsConfig AST
 * and return UserTsConfig DTO.
 *
 * @internal Internal for now until API stabilized. Use backendUser->getTSConfig().
 */
final class UserTsConfigFactory
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly TokenizerInterface $tokenizer,
        private readonly TsConfigTreeBuilder $tsConfigTreeBuilder,
        private readonly PhpFrontend $cache,
    ) {
    }

    public function create(BackendUserAuthentication $backendUser): UserTsConfig
    {
        $includeTreeTraverserConditionVerdictAware = new ConditionVerdictAwareIncludeTreeTraverser();
        $userTsConfigTree = $this->tsConfigTreeBuilder->getUserTsConfigTree($backendUser, $this->tokenizer, $this->cache);
        $conditionMatcherVisitor = new IncludeTreeConditionMatcherVisitor();
        $backendConditionMatcher = GeneralUtility::makeInstance(BackendConditionMatcher::class, GeneralUtility::makeInstance(Context::class));
        $conditionMatcherVisitor->setConditionMatcher($backendConditionMatcher);
        $includeTreeTraverserConditionVerdictAware->addVisitor($conditionMatcherVisitor);
        $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
        $includeTreeTraverserConditionVerdictAware->addVisitor($astBuilderVisitor);
        $includeTreeTraverserConditionVerdictAware->traverse($userTsConfigTree);
        return new UserTsConfig($astBuilderVisitor->getAst());
    }
}
