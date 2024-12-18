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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\AST\Merger\SetupConfigMerger;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionIncludeListAccumulatorVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionMatcherVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConstantsEvent;

/**
 * Create FrontendTypoScript with its details. This is typically used by a Frontend middleware
 * to calculate the TypoScript needed to satisfy rendering details of the specific Request.
 *
 * @internal Methods signatures and detail implementations are still subject to change.
 */
final readonly class FrontendTypoScriptFactory
{
    public function __construct(
        private ContainerInterface $container,
        private EventDispatcherInterface $eventDispatcher,
        private SysTemplateTreeBuilder $treeBuilder,
        private LossyTokenizer $tokenizer,
        private IncludeTreeTraverser $includeTreeTraverser,
        private ConditionVerdictAwareIncludeTreeTraverser $includeTreeTraverserConditionVerdictAware,
    ) {}

    /**
     * First step of TypoScript calculations.
     * This is *always* called, even in FE fully cached pages context since the page
     * cache entry depends on setup condition verdicts, which depends on settings.
     *
     * Returns the FrontendTypoScript object with these parameters set:
     * * settingsTree: The full settings ("constants") AST
     * * flatSettings: Flattened list of settings, derived from settings tree
     * * settingsConditionList: Settings conditions with verdicts of this Request
     * * setupConditionList: Setup conditions with verdicts of this Request
     * * (sometimes) setupIncludeTree: The setup include tree *if* it had to be calculated
     */
    public function createSettingsAndSetupConditions(
        SiteInterface $site,
        array $sysTemplateRows,
        array $expressionMatcherVariables,
        ?PhpFrontend $typoScriptCache,
    ): FrontendTypoScript {
        $settingsDetails = $this->createSettings(
            $site,
            $sysTemplateRows,
            $expressionMatcherVariables,
            $typoScriptCache
        );
        $setupDetails = $this->createSetupConditionList(
            $site,
            $sysTemplateRows,
            $expressionMatcherVariables,
            $typoScriptCache,
            $settingsDetails['flatSettings'],
            $settingsDetails['settingsConditionList'],
        );
        $frontendTypoScript = new FrontendTypoScript(
            $settingsDetails['settingsTree'],
            $settingsDetails['settingsConditionList'],
            $settingsDetails['flatSettings'],
            $setupDetails['setupConditionList'],
        );
        if ($setupDetails['setupIncludeTree']) {
            $frontendTypoScript->setSetupIncludeTree($setupDetails['setupIncludeTree']);
        }
        return $frontendTypoScript;
    }

    /**
     * Calculate settings (formerly "constants").
     *
     * The page cache entry identifier depends on setup TypoScript: A single page with two different
     * setup TypoScript AST will probably render different results, thus two page-cache entries.
     * Setup TypoScript can be different when setup conditions match differently.
     * Setup conditions can use settings "[{$foo} = 42]".
     *
     * All FE requests thus need the current list of settings, and settings can have conditions, too.
     * We thus *always* need the current list of settings, even in fully cached pages context.
     *
     * The method calculates settings and uses caches as much as possible:
     * * settingsTree: The full settings AST
     * * flatSettings: Flattened list of settings, derived from settings AST
     * * settingsConditionList: Settings conditions with verdicts of this Request
     *
     * @return array{settingsTree: RootNode, flatSettings: array, settingsConditionList: array}
     */
    private function createSettings(
        SiteInterface $site,
        array $sysTemplateRows,
        array $expressionMatcherVariables,
        ?PhpFrontend $typoScriptCache,
    ): array {
        $cacheCriteria = [
            'sysTemplateRows' => $sysTemplateRows,
        ];
        if ($site instanceof Site && $site->isTypoScriptRoot()) {
            $cacheCriteria['siteIdentifier'] = $site->getIdentifier();
        }
        $conditionTreeCacheIdentifier = 'settings-condition-tree-' . hash('xxh3', json_encode($cacheCriteria, JSON_THROW_ON_ERROR));

        if ($conditionTree = $typoScriptCache?->require($conditionTreeCacheIdentifier)) {
            // Got the (flat) include tree of all settings conditions for this TypoScript combination from cache.
            // Good. Traverse this list to calculate "current" condition verdicts. Hash this list together with a
            // hash of the TypoScript sys_templates, and try to retrieve the full settings TypoScript AST from cache.
            // Note: Working with the derived condition tree that *only* contains conditions, but not the full
            // include tree is a trick: We only need the condition verdicts to know the AST cache identifier,
            // and traversing the flat condition tree is quicker than traversing the entire settings include tree,
            // since it only scales with the number of settings conditions and not with the full amount of TypoScript
            // settings. The same trick is used for the setup AST cache later.
            $conditionMatcherVisitor = $this->container->get(IncludeTreeConditionMatcherVisitor::class);
            $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
            // It does not matter if we use IncludeTreeTraverser or ConditionVerdictAwareIncludeTreeTraverser here:
            // Conditions list is flat, not nested. IncludeTreeTraverser has an if() less, so we use that one.
            $this->includeTreeTraverser->traverse($conditionTree, [$conditionMatcherVisitor]);
            $conditionList = $conditionMatcherVisitor->getConditionListWithVerdicts();
            $settings = $typoScriptCache->require(
                'settings-' . hash('xxh3', $conditionTreeCacheIdentifier . json_encode($conditionList, JSON_THROW_ON_ERROR))
            );
            if (is_array($settings)) {
                return [
                    'settingsTree' => $settings['ast'],
                    'flatSettings' => $settings['flatSettings'],
                    'settingsConditionList' => $conditionList,
                ];
            }
        }

        // We did not get settings from cache, or are not allowed to use cache. Build settings from scratch.
        // We fetch the full settings include tree (from cache if possible), register the condition
        // matcher and register the AST builder and traverse include tree to retrieve settings AST and derive
        // 'flat settings' from it. Both are cached if allowed afterward for the above 'if' to kick in next time.
        $includeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $this->tokenizer, $site, $typoScriptCache);
        $conditionMatcherVisitor = $this->container->get(IncludeTreeConditionMatcherVisitor::class);
        $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
        $visitors = [];
        $visitors[] = $conditionMatcherVisitor;
        $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
        $visitors[] = $astBuilderVisitor;
        // We must use ConditionVerdictAwareIncludeTreeTraverser here: This one does not walk into
        // children for not matching conditions, which is important to create the correct AST.
        $this->includeTreeTraverserConditionVerdictAware->traverse($includeTree, $visitors);
        $tree = $astBuilderVisitor->getAst();
        // @internal Dispatch an experimental event allowing listeners to still change the settings AST,
        //           to for instance implement nested constants if really needed. Note this event may change
        //           or vanish later without further notice.
        $tree = $this->eventDispatcher->dispatch(new ModifyTypoScriptConstantsEvent($tree))->getConstantsAst();
        $flatSettings = $tree->flatten();

        // Prepare the full list of settings conditions in order to cache this list, avoiding the
        // settings AST building next time. We need all conditions of the entire include tree, but the
        // above ConditionVerdictAwareIncludeTreeTraverser did not find nested conditions if an upper
        // condition did not match. We thus have to traverse include tree a second time with the
        // IncludeTreeTraverser. This one does traverse into not matching conditions.
        $visitors = [];
        $conditionMatcherVisitor = $this->container->get(IncludeTreeConditionMatcherVisitor::class);
        $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
        $visitors[] = $conditionMatcherVisitor;
        $conditionTreeAccumulatorVisitor = null;
        if (!$conditionTree && $typoScriptCache) {
            // If the settingsConditionTree did not come from cache above and if we are allowed to cache,
            // register the visitor that creates the settings condition include tree, to cache it.
            $conditionTreeAccumulatorVisitor = $this->container->get(IncludeTreeConditionIncludeListAccumulatorVisitor::class);
            $visitors[] = $conditionTreeAccumulatorVisitor;
        }
        $this->includeTreeTraverser->traverse($includeTree, $visitors);
        $conditionList = $conditionMatcherVisitor->getConditionListWithVerdicts();

        if ($conditionTreeAccumulatorVisitor) {
            // Cache the flat condition include tree for next run.
            $conditionTree = $conditionTreeAccumulatorVisitor->getConditionIncludes();
            $typoScriptCache?->set(
                $conditionTreeCacheIdentifier,
                'return unserialize(\'' . addcslashes(serialize($conditionTree), '\'\\') . '\');'
            );
        }
        $typoScriptCache?->set(
            // Cache full AST and the derived 'flattened' variant for next run, which will kick in if
            // the sys_templates and condition verdicts are identical with another Request.
            'settings-' . hash('xxh3', $conditionTreeCacheIdentifier . json_encode($conditionList, JSON_THROW_ON_ERROR)),
            'return unserialize(\'' . addcslashes(serialize(['ast' => $tree, 'flatSettings' => $flatSettings]), '\'\\') . '\');'
        );

        return [
            'settingsTree' => $tree,
            'flatSettings' => $flatSettings,
            'settingsConditionList' => $conditionList,
        ];
    }

    /**
     * Calculate setup condition verdicts.
     *
     * With settings being done, the list of matching setup condition verdicts is calculated,
     * which depend on settings. Setup conditions with their verdicts are part of the page
     * cache identifier, they are *always* needed in the FE rendering chain.
     *
     * The cached variant uses a similar trick as with the settings calculation above: We
     * calculate a flat tree of all conditions and cache this, so the traverser only needs
     * to iterate the conditions to calculate there verdicts, but not the entire include
     * tree next time.
     *
     * The method returns:
     * * 'setupConditionList': Setup conditions with verdicts of this Request
     * * (sometimes) setupIncludeTree: The setup include tree *if* it had to be calculated. Used internally
     *                                 to suppress a second calculation in createSetupConfigOrFullSetup().
     *
     * @return array{setupConditionList: array, setupIncludeTree: RootInclude|null}
     */
    private function createSetupConditionList(
        SiteInterface $site,
        array $sysTemplateRows,
        array $expressionMatcherVariables,
        ?PhpFrontend $typoScriptCache,
        array $flatSettings,
        array $settingsConditionList,
    ): array {
        $conditionTreeCacheIdentifier = 'setup-condition-tree-'
            . hash('xxh3', json_encode($sysTemplateRows, JSON_THROW_ON_ERROR) . json_encode($settingsConditionList, JSON_THROW_ON_ERROR));

        if ($conditionTree = $typoScriptCache?->require($conditionTreeCacheIdentifier)) {
            // We got the flat list of all setup conditions for this TypoScript combination from cache. Good. We traverse
            // this list to calculate "current" condition verdicts, which we need as hash to be part of page cache identifier.
            // We're done and return. Note 'setupIncludeTree' is *not* returned in this case since it is not needed and
            // may or may not be needed later, depending on if we can get a page cache entry later and if it has _INT objects.
            $visitors = [];
            $conditionConstantSubstitutionVisitor = $this->container->get(IncludeTreeSetupConditionConstantSubstitutionVisitor::class);
            $conditionConstantSubstitutionVisitor->setFlattenedConstants($flatSettings);
            $visitors[] = $conditionConstantSubstitutionVisitor;
            $conditionMatcherVisitor = $this->container->get(IncludeTreeConditionMatcherVisitor::class);
            $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
            $visitors[] = $conditionMatcherVisitor;
            // It does not matter if we use IncludeTreeTraverser or ConditionVerdictAwareIncludeTreeTraverser here:
            // Condition list is flat, not nested. IncludeTreeTraverser has an if() less, so we use that one.
            $this->includeTreeTraverser->traverse($conditionTree, $visitors);
            return [
                'setupConditionList' => $conditionMatcherVisitor->getConditionListWithVerdicts(),
                'setupIncludeTree' => null,
            ];
        }

        // We did not get setup condition list from cache, or are not allowed to use cache. We have to build setup
        // condition list from scratch. This means we'll fetch the full setup include tree (from cache if possible),
        // register the constant substitution visitor, the condition matcher and the condition accumulator visitor.
        $includeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $this->tokenizer, $site, $typoScriptCache);
        $visitors = [];
        $conditionConstantSubstitutionVisitor = $this->container->get(IncludeTreeSetupConditionConstantSubstitutionVisitor::class);
        $conditionConstantSubstitutionVisitor->setFlattenedConstants($flatSettings);
        $visitors[] = $conditionConstantSubstitutionVisitor;
        $conditionMatcherVisitor = $this->container->get(IncludeTreeConditionMatcherVisitor::class);
        $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
        $visitors[] = $conditionMatcherVisitor;
        $conditionTreeAccumulatorVisitor = $this->container->get(IncludeTreeConditionIncludeListAccumulatorVisitor::class);
        $visitors[] = $conditionTreeAccumulatorVisitor;
        // It is important to use IncludeTreeTraverser here: We need the condition verdicts of *all* conditions, and
        // we want to accumulate all of them. The ConditionVerdictAwareIncludeTreeTraverser wouldn't walk into nested
        // conditions if an upper one does not match, which defeats cache identifier calculations.
        $this->includeTreeTraverser->traverse($includeTree, $visitors);

        $typoScriptCache?->set(
            $conditionTreeCacheIdentifier,
            'return unserialize(\'' . addcslashes(serialize($conditionTreeAccumulatorVisitor->getConditionIncludes()), '\'\\') . '\');'
        );

        return [
            'setupConditionList' => $conditionMatcherVisitor->getConditionListWithVerdicts(),
            'setupIncludeTree' => $includeTree,
        ];
    }

    /**
     * Enrich the given FrontendTypoScript object with TypoScript 'setup' relevant data.
     *
     * The method is called in FE after an attempt to retrieve page content from cache has
     * been done. There are three possible outcomes:
     * * The page has been retrieved from cache and the content *does not* contain uncached "_INT" objects
     * * The page has been retrieved from cache and the content *does* contain uncached "_INT" objects
     * * The page could not be retrieved from cache
     *
     * If the page could not be retrieved from cache, or if the cached page content contains "_INT" objects,
     * flag $needsFullSetup is given true, and the full TypoScript is calculated since at least parts of
     * the page content has to be rendered, which then needs full TypoScript.
     * If the page could be retrieved from cache, and contains no "_INT" objects, $needsFullSetup in false, the
     * rendering chain only needs the "config." part of TypoScript to satisfy the remaining middlewares.
     *
     * The method implements these variants and tries to add as little overhead as possible.
     *
     * Returns the FrontendTypoScript object:
     * * configTree: Always set. Global TypoScript 'config.' merged with overrides from given type/typeNum "page.config.".
     * * configArray: Always set. Array representation of configTree.
     * * setupTree: Not set if $needsFullSetup=false and configTree could be retrieved from cache. Full TypoScript setup.
     * * setupArray: Not set if $needsFullSetup=false and configTree could be retrieved from cache.
     *               Array representation of setupTree.
     * * pageTree: Not set if $needsFullSetup=false and configTree could be retrieved from cache, or if no PAGE object
     *             could be determined. The 'PAGE' object tree for given type/typeNum.
     * * pageArray: Not set if $needsFullSetup=false and configTree could be retrieved from cache, or if no PAGE object
     *              could be determined. Array representation of PageTree.
     */
    public function createSetupConfigOrFullSetup(
        bool $needsFullSetup,
        FrontendTypoScript $frontendTypoScript,
        SiteInterface $site,
        array $sysTemplateRows,
        array $expressionMatcherVariables,
        string $type,
        ?PhpFrontend $typoScriptCache,
        ?ServerRequestInterface $request,
    ): FrontendTypoScript {
        $setupTypoScriptCacheIdentifier = 'setup-' . hash(
            'xxh3',
            json_encode($sysTemplateRows, JSON_THROW_ON_ERROR)
            . ($site instanceof Site && $site->isTypoScriptRoot() ? $site->getIdentifier() : '')
            . json_encode($frontendTypoScript->getSettingsConditionList(), JSON_THROW_ON_ERROR)
            . json_encode($frontendTypoScript->getSetupConditionList(), JSON_THROW_ON_ERROR)
        );
        $setupConfigTypoScriptCacheIdentifier = 'setup-config-' . hash('xxh3', $setupTypoScriptCacheIdentifier . $type);

        $gotSetupConfigFromCache = false;
        if ($setupConfigTypoScriptCache = $typoScriptCache?->require($setupConfigTypoScriptCacheIdentifier)) {
            $frontendTypoScript->setConfigTree($setupConfigTypoScriptCache['ast']);
            $frontendTypoScript->setConfigArray($setupConfigTypoScriptCache['array']);
            if (!$needsFullSetup) {
                // Fully cached page context without _INT - only 'config' is needed. Return early.
                return $frontendTypoScript;
            }
            $gotSetupConfigFromCache = true;
        }

        $setupRawConfigAst = null;
        if (!$typoScriptCache || $needsFullSetup || !$gotSetupConfigFromCache) {
            // If caching is not allowed, if no page cache entry could be loaded or if the page cache entry has _INT
            // object, we need the full setup AST. Try to use a cache entry for setup AST, which especially up _INT
            // parsing. In unavailable, calculate full setup AST and cache it if allowed.
            $gotSetupFromCache = false;
            if ($setupTypoScriptCache = $typoScriptCache?->require($setupTypoScriptCacheIdentifier)) {
                // We need AST, and we got it from cache.
                $frontendTypoScript->setSetupTree($setupTypoScriptCache['ast']);
                $frontendTypoScript->setSetupArray($setupTypoScriptCache['array']);
                $setupRawConfigAst = $setupTypoScriptCache['ast']->getChildByName('config');
                $gotSetupFromCache = true;
            }
            if (!$typoScriptCache || !$gotSetupFromCache) {
                // We need AST and couldn't get it from cache or are now allowed to. We thus need the full setup
                // IncludeTree, which we can get from cache again if allowed, or is calculated a-new if not.
                $setupIncludeTree = $frontendTypoScript->getSetupIncludeTree();
                if (!$typoScriptCache || $setupIncludeTree === null) {
                    // A previous method *may* have calculated setup include tree already. Calculate now if not.
                    $setupIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $this->tokenizer, $site, $typoScriptCache);
                }
                $visitors = [];
                $conditionConstantSubstitutionVisitor = $this->container->get(IncludeTreeSetupConditionConstantSubstitutionVisitor::class);
                $conditionConstantSubstitutionVisitor->setFlattenedConstants($frontendTypoScript->getFlatSettings());
                $visitors[] = $conditionConstantSubstitutionVisitor;
                $conditionMatcherVisitor = $this->container->get(IncludeTreeConditionMatcherVisitor::class);
                $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
                $visitors[] = $conditionMatcherVisitor;
                $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
                $astBuilderVisitor->setFlatConstants($frontendTypoScript->getFlatSettings());
                $visitors[] = $astBuilderVisitor;
                $this->includeTreeTraverserConditionVerdictAware->traverse($setupIncludeTree, $visitors);
                $setupAst = $astBuilderVisitor->getAst();
                // @todo: It would be good to actively remove 'config' from AST and array here
                //        to prevent people from using the unmerged variant. The same
                //        is already done for the determined PAGE 'config' below. This works, but
                //        is currently blocked by functional tests that assert details?
                //        Also, we need to still cache with full 'config' to handle multiple types.
                $setupRawConfigAst = $setupAst->getChildByName('config');
                // $setupAst->removeChildByName('config');
                $frontendTypoScript->setSetupTree($setupAst);
                $frontendTypoScript->setSetupArray($setupAst->toArray());

                // Write cache entry for AST and its array representation.
                $typoScriptCache?->set(
                    $setupTypoScriptCacheIdentifier,
                    'return unserialize(\'' . addcslashes(serialize(['ast' => $setupAst, 'array' => $setupAst->toArray()]), '\'\\') . '\');'
                );
            }

            $setupAst = $frontendTypoScript->getSetupTree();
            $rawSetupPageNodeFromType = null;
            $pageNodeFoundByType = false;
            foreach ($setupAst->getNextChild() as $potentialPageNode) {
                // Find the PAGE object that matches given type/typeNum
                if ($potentialPageNode->getValue() === 'PAGE') {
                    // @todo: We could potentially remove *all* PAGE objects from setup here. This prevents people
                    //        from accessing other ones than the determined one in $frontendTypoScript->getSetupArray().
                    $typeNumChild = $potentialPageNode->getChildByName('typeNum');
                    if ($typeNumChild && $type === $typeNumChild->getValue()) {
                        $rawSetupPageNodeFromType = $potentialPageNode;
                        $pageNodeFoundByType = true;
                        break;
                    }
                    if (!$typeNumChild && $type === '0') {
                        // The first PAGE node that has no typeNum is considered '0' automatically.
                        $rawSetupPageNodeFromType = $potentialPageNode;
                        $pageNodeFoundByType = true;
                        break;
                    }
                }
            }
            if (!$pageNodeFoundByType) {
                $rawSetupPageNodeFromType = new RootNode();
            }
            $setupPageAst = new RootNode();
            foreach ($rawSetupPageNodeFromType->getNextChild() as $child) {
                $setupPageAst->addChild($child);
            }

            if (!$gotSetupConfigFromCache) {
                // If we did not get merged 'config.' from cache above, create it now and cache it.
                $mergedSetupConfigAst = (new SetupConfigMerger())->merge($setupRawConfigAst, $setupPageAst->getChildByName('config'));
                if ($mergedSetupConfigAst->getChildByName('absRefPrefix') === null) {
                    // Make sure config.absRefPrefix is set, fallback to 'auto'.
                    $absRefPrefixNode = new ChildNode('absRefPrefix');
                    $absRefPrefixNode->setValue('auto');
                    $mergedSetupConfigAst->addChild($absRefPrefixNode);
                }
                if ($mergedSetupConfigAst->getChildByName('doctype') === null) {
                    // Make sure config.doctype is set, fallback to 'html5'.
                    $doctypeNode = new ChildNode('doctype');
                    $doctypeNode->setValue('html5');
                    $mergedSetupConfigAst->addChild($doctypeNode);
                }
                if ($request) {
                    // Dispatch ModifyTypoScriptConfigEvent before config is cached and if Request is given.
                    $mergedSetupConfigAst = $this->eventDispatcher
                        ->dispatch(new ModifyTypoScriptConfigEvent($request, $setupAst, $mergedSetupConfigAst))->getConfigTree();
                }
                $frontendTypoScript->setConfigTree($mergedSetupConfigAst);
                $setupConfigArray = $mergedSetupConfigAst->toArray();
                $frontendTypoScript->setConfigArray($setupConfigArray);
                $typoScriptCache?->set(
                    $setupConfigTypoScriptCacheIdentifier,
                    'return unserialize(\'' . addcslashes(serialize(['ast' => $mergedSetupConfigAst, 'array' => $setupConfigArray]), '\'\\') . '\');'
                );
            }

            if ($pageNodeFoundByType) {
                // Remove "page.config" to prevent people from working with the not merged variant.
                // We do *not* set page if it could not be determined (important for hasPage() later
                // to return an early "no PAGE for type found" Response.
                $setupPageAst->removeChildByName('config');
                $frontendTypoScript->setPageTree($setupPageAst);
                $frontendTypoScript->setPageArray($setupPageAst->toArray());
            }
        }
        return $frontendTypoScript;
    }
}
