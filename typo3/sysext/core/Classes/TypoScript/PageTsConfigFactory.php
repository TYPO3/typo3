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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\ExpressionLanguage\DeprecatingRequestWrapper;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SiteInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\TsConfigInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionMatcherVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calculate page TSconfig. This does the heavy lifting additionally supported by
 * TsConfigTreeBuilder: Load basic page TSconfig tree, overload with user TSconfig, parse
 * site settings ("constants"), then build the page TSconfig AST and return page TSconfig DTO.
 *
 * @internal Internal for now until API stabilized. Use BackendUtility::getPagesTSconfig().
 */
final class PageTsConfigFactory
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly TokenizerInterface $tokenizer,
        private readonly TsConfigTreeBuilder $tsConfigTreeBuilder,
        private readonly PhpFrontend $cache,
    ) {
    }

    public function create(
        array $fullRootLine,
        SiteInterface $site,
        ?UserTsConfig $userTsConfig = null
    ): PageTsConfig {
        $pagesTsConfigTree = $this->tsConfigTreeBuilder->getPagesTsConfigTree($fullRootLine, $this->tokenizer, $this->cache);

        // Overloading with user TSconfig if hand over
        if ($userTsConfig instanceof UserTsConfig) {
            $userTsConfigAst = $userTsConfig->getUserTsConfigTree();
            $userTsConfigPageOverrides = '';
            // @todo: This is ugly and expensive. There should be a better way to do this. Similar in BE page TSconfig controllers.
            $userTsConfigFlat = $userTsConfigAst->flatten();
            foreach ($userTsConfigFlat as $userTsConfigIdentifier => $userTsConfigValue) {
                if (str_starts_with($userTsConfigIdentifier, 'page.')) {
                    $userTsConfigPageOverrides .= substr($userTsConfigIdentifier, 5) . ' = ' . $userTsConfigValue . chr(10);
                }
            }
            if (!empty($userTsConfigPageOverrides)) {
                $includeNode = new TsConfigInclude();
                $includeNode->setName('pageTsConfig-overrides-by-userTsConfig');
                $includeNode->setLineStream($this->tokenizer->tokenize($userTsConfigPageOverrides));
                $pagesTsConfigTree->addChild($includeNode);
            }
        }

        // Prepare site constants to be substituted
        $includeTreeTraverserConditionVerdictAware = new ConditionVerdictAwareIncludeTreeTraverser();
        $siteSettingsFlat = [];
        if ($site instanceof Site) {
            $siteSettings = $site->getSettings();
            if (!$siteSettings->isEmpty()) {
                $siteSettingsCacheIdentifier = 'site-settings-flat-' . hash('xxh3', json_encode($siteSettings, JSON_THROW_ON_ERROR));
                $siteSettingsCacheArray = $this->cache->require($siteSettingsCacheIdentifier);
                if (isset($siteSettingsCacheArray['flatConstants'])) {
                    $siteSettingsFlat = $siteSettingsCacheArray['flatConstants'];
                } else {
                    $siteConstants = '';
                    $siteSettings = $siteSettings->getAllFlat();
                    foreach ($siteSettings as $nodeIdentifier => $value) {
                        $siteConstants .= $nodeIdentifier . ' = ' . $value . LF;
                    }
                    $siteSettingsNode = new SiteInclude();
                    $siteSettingsNode->setName('Site constants settings of site "' . $site->getIdentifier() . '"');
                    $siteSettingsNode->setLineStream($this->tokenizer->tokenize($siteConstants));
                    $siteSettingsTreeRoot = new RootInclude();
                    $siteSettingsTreeRoot->addChild($siteSettingsNode);
                    $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
                    $includeTreeTraverserConditionVerdictAware->traverse($siteSettingsTreeRoot, [$astBuilderVisitor]);
                    $siteSettingsFlat = $astBuilderVisitor->getAst()->flatten();
                    $this->cache->set($siteSettingsCacheIdentifier, 'return unserialize(\'' . addcslashes(serialize(['flatConstants' => $siteSettingsFlat]), '\'\\') . '\');');
                }
            }
        }

        // Create AST with constants from site and conditions
        $includeTreeTraverserConditionVerdictAwareVisitors = [];
        if (!empty($siteSettingsFlat)) {
            $setupConditionConstantSubstitutionVisitor = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
            $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($siteSettingsFlat);
            $includeTreeTraverserConditionVerdictAwareVisitors[] = $setupConditionConstantSubstitutionVisitor;
        }
        $lastPageFullRecord = [];
        $pageId = 0;
        if (!empty($fullRootLine)) {
            $lastPage = $fullRootLine[array_key_last($fullRootLine)];
            $pageId = $lastPage['uid'];
            $lastPageFullRecord = BackendUtility::getRecord('pages', $pageId) ?: [];
        }
        $conditionMatcherVariables = [
            'fullRootLine' => $fullRootLine,
            'site' => $site,
            // @todo We're using the full page row here to provide all necessary fields (e.g. "backend_layout"),
            //       which are currently not included in the rows, RootlineUtility provides by default. We might
            //       want to switch to $fullRootLine[array_key_last($fullRootLine)] as soon as it contains all fields.
            'page' => $lastPageFullRecord,
            'pageId' => $pageId,
            // @deprecated since v12, will be removed in v13.
            'request' => new DeprecatingRequestWrapper($GLOBALS['TYPO3_REQUEST'] ?? null),
        ];
        $conditionMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
        $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($conditionMatcherVariables);
        $includeTreeTraverserConditionVerdictAwareVisitors[] = $conditionMatcherVisitor;
        $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
        $astBuilderVisitor->setFlatConstants($siteSettingsFlat);
        $includeTreeTraverserConditionVerdictAwareVisitors[] = $astBuilderVisitor;
        $includeTreeTraverserConditionVerdictAware->traverse($pagesTsConfigTree, $includeTreeTraverserConditionVerdictAwareVisitors);

        return new PageTsConfig($astBuilderVisitor->getAst());
    }
}
