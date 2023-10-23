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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\ExpressionLanguage\RequestWrapper;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeConditionInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Frontend\Page\PageLayoutResolver;

/**
 * A visitor that looks at IncludeConditionInterface nodes and
 * evaluates their conditions.
 *
 * Condition matching is done in visitBeforeChildren() to be used in combination with
 * ConditionVerdictAwareIncludeTreeTraverser, so children are only traversed for
 * conditions that evaluated true.
 *
 * @internal: Internal tree structure.
 */
final class IncludeTreeConditionMatcherVisitor implements IncludeTreeVisitorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Resolver $resolver;
    private array $conditionList = [];

    public function __construct(
        private readonly Context $context,
        private readonly PageLayoutResolver $pageLayoutResolver,
    ) {}

    /**
     * Prepare the core expression language Resolver class - our API to symfony
     * expression language - for typoscript context usage.
     *
     * The method gets a series of variables hand over coming from caller scope
     * like rootline, page array and eventually a request object. These vars are
     * munged around a bit and enriched with a series of semi-static state variables:
     * Things that can be injected like derived from context, for example
     * frontend / backend user, workspace and similar.
     * This ensures all typoscript 'conditions' receive similar structured data.
     */
    public function initializeExpressionMatcherWithVariables(array $variables): void
    {
        $context = $this->context;
        $enrichedVariables = [
            'context' => $context,
        ];
        // Variables derived directly from context are set if context provides according aspects.
        $frontendUserAspect = $this->context->getAspect('frontend.user');
        if ($frontendUserAspect instanceof UserAspect) {
            $frontend = new \stdClass();
            $frontend->user = new \stdClass();
            $frontend->user->isLoggedIn = $frontendUserAspect->get('isLoggedIn');
            $frontend->user->userId = $frontendUserAspect->get('id');
            $frontend->user->userGroupList = implode(',', $frontendUserAspect->get('groupIds'));
            $frontend->user->userGroupIds = $frontendUserAspect->get('groupIds');
            $enrichedVariables['frontend'] = $frontend;
        }
        $backendUserAspect = $this->context->getAspect('backend.user');
        if ($backendUserAspect instanceof UserAspect) {
            $backend = new \stdClass();
            $backend->user = new \stdClass();
            $backend->user->isAdmin = $backendUserAspect->get('isAdmin');
            $backend->user->isLoggedIn = $backendUserAspect->get('isLoggedIn');
            $backend->user->userId = $backendUserAspect->get('id');
            $backend->user->userGroupList = implode(',', $backendUserAspect->get('groupIds'));
            $backend->user->userGroupIds = $backendUserAspect->get('groupIds');
            $enrichedVariables['backend'] = $backend;
        }
        $workspaceAspect = $this->context->getAspect('workspace');
        if ($workspaceAspect instanceof WorkspaceAspect) {
            $workspace = new \stdClass();
            $workspace->workspaceId = $workspaceAspect->get('id');
            $workspace->isLive = $workspaceAspect->get('isLive');
            $workspace->isOffline = $workspaceAspect->get('isOffline');
            $enrichedVariables['workspace'] = $workspace;
        }

        $pageId = $variables['pageId'] ?? 0;

        // If rootLine is given, create an object that contains some prepared values.
        $fullRootLine = $variables['fullRootLine'] ?? null;
        if ($fullRootLine === null && $pageId > 0) {
            $fullRootLine = BackendUtility::BEgetRootLine($pageId, '', true);
            ksort($fullRootLine);
        }
        $localRootLine = $variables['localRootLine'] ?? $fullRootLine;
        if (!empty($localRootLine)) {
            $tree = new \stdClass();
            $tree->level = count($localRootLine) - 1;
            $tree->rootLine = $localRootLine;
            $tree->fullRootLine = $fullRootLine;
            $tree->rootLineIds = array_column($localRootLine, 'uid');
            $tree->rootLineParentIds = array_slice(array_column($localRootLine, 'pid'), 1);
            // We're feeding the "full" RootLine here, not the "local" one that stops at sys_template record having 'root' set.
            // This is to be in-line with backend here: A 'backend_layout_next_level' on a page above sys_template 'root' page should
            // still be considered. Additionally, $fullRootLine is "deepest page first, then up" for getLayoutForPage() to find
            // the 'nearest' parent.
            $tree->pagelayout = $this->pageLayoutResolver->getLayoutForPage($variables['page'], $fullRootLine);
            $enrichedVariables['tree'] = $tree;
        }

        // If a request is given, make sure it is an instance of RequestWrapper,
        // if not, create an instance from ServerRequestInterface and set it.
        if (isset($variables['request']) && !($variables['request'] instanceof RequestWrapper)) {
            $variables['request'] = new RequestWrapper($variables['request']);
        }

        // We do not expose pageId, rootLine and fullRootLine to conditions directly.
        unset($variables['pageId'], $variables['localRootLine'], $variables['fullRootLine']);

        $enrichedVariables = array_replace($enrichedVariables, $variables);

        $this->resolver = new Resolver('typoscript', $enrichedVariables);
    }

    /**
     * A list of all handled conditions with their verdicts.
     * This is used in FE since condition verdicts influence page caches.
     */
    public function getConditionListWithVerdicts(): array
    {
        return $this->conditionList;
    }

    /**
     * Let symfony expression language handle the expression, gather expressions
     * that have been handled since they influence page caching, negate expression
     * verdicts if they're a [else] expression.
     */
    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if (!$include instanceof IncludeConditionInterface) {
            return;
        }
        $conditionExpression = $include->getConditionToken()->getValue();
        try {
            $verdict = (bool)$this->resolver->evaluate($conditionExpression);
        } catch (SyntaxError) {
            $this->logger->error('Expression could not be parsed.', ['expression' => $conditionExpression]);
            $verdict = false;
        }
        if ($include->isConditionNegated()) {
            // Honor ConditionElseInclude "[ELSE]" which negates the verdict of the main condition.
            $verdict = !$verdict;
        }
        $this->conditionList[$conditionExpression] = $verdict;
        $include->setConditionVerdict($verdict);
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        // Noop, just implement interface
    }
}
