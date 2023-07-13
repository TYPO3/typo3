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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\ExpressionLanguage\DeprecatingRequestWrapper;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionMatcherVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calculate user TSconfig. This does the heavy lifting additionally supported by
 * TsConfigTreeBuilder: Load basic user TSconfig tree, then build the user TSconfig AST
 * and return user TSconfig DTO.
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
        $includeTreeTraverserConditionVerdictAwareVisitors = [];
        $userTsConfigTree = $this->tsConfigTreeBuilder->getUserTsConfigTree($backendUser, $this->tokenizer, $this->cache);
        $conditionMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
        // User TSconfig is not within page context, that what page TSconfig is for, so 'page', 'pageId',
        // 'rootLine' and 'tree' can not be used in user TSconfig conditions. There is no request, either.
        $conditionMatcherVisitor->initializeExpressionMatcherWithVariables([
            'page' => [],
            'pageId' => 0,
            // @deprecated since v12, will be removed in v13.
            'request' => new DeprecatingRequestWrapper($GLOBALS['TYPO3_REQUEST'] ?? null),
        ]);
        $includeTreeTraverserConditionVerdictAwareVisitors[] = $conditionMatcherVisitor;
        $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
        $includeTreeTraverserConditionVerdictAwareVisitors[] = $astBuilderVisitor;
        $includeTreeTraverserConditionVerdictAware->traverse($userTsConfigTree, $includeTreeTraverserConditionVerdictAwareVisitors);
        return new UserTsConfig($astBuilderVisitor->getAst());
    }
}
