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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent as LegacyModifyLoadedPageTsConfigEvent;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\TsConfigInclude;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Build include tree for user TSconfig and page TSconfig. This is typically used only by
 * UserTsConfigFactory and PageTsConfigFactory.
 *
 * @internal
 */
final class TsConfigTreeBuilder
{
    public function __construct(
        private readonly TreeFromLineStreamBuilder $treeFromTokenStreamBuilder,
        private readonly PackageManager $packageManager,
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function getUserTsConfigTree(
        BackendUserAuthentication $backendUser,
        TokenizerInterface $tokenizer,
        ?PhpFrontend $cache = null
    ): RootInclude {
        $includeTree = new RootInclude();
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] ?? '')) {
            $includeTree->addChild($this->getTreeFromString('userTsConfig-globals', $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'], $tokenizer, $cache));
        }
        if ($backendUser->isAdmin()) {
            // @todo: Could we maybe solve this differently somehow? Maybe in ext:adminpanel in FE directly?
            $includeTree->addChild($this->getTreeFromString('userTsConfig-admpanel', 'admPanel.enable.all = 1', $tokenizer, $cache));
        }
        foreach ($backendUser->userGroupsUID as $groupId) {
            // Loop through all groups and add their 'TSconfig' fields
            if (!empty($backendUser->userGroups[$groupId]['TSconfig'] ?? '')) {
                $includeTree->addChild($this->getTreeFromString('userTsConfig-group-' . $groupId, $backendUser->userGroups[$groupId]['TSconfig'], $tokenizer, $cache));
            }
        }
        if (!empty($backendUser->user['TSconfig'] ?? '')) {
            $includeTree->addChild($this->getTreeFromString('userTsConfig-user', $backendUser->user['TSconfig'], $tokenizer, $cache));
        }
        return $includeTree;
    }

    public function getPagesTsConfigTree(
        array $rootLine,
        TokenizerInterface $tokenizer,
        ?PhpFrontend $cache = null
    ): RootInclude {
        $collectedPagesTsConfigArray = [];
        $gotPackagesPagesTsConfigFromCache = false;
        if ($cache) {
            $collectedPagesTsConfigArrayFromCache = $cache->require('pagestsconfig-packages-strings');
            if ($collectedPagesTsConfigArrayFromCache) {
                $gotPackagesPagesTsConfigFromCache = true;
                $collectedPagesTsConfigArray = $collectedPagesTsConfigArrayFromCache;
            }
        }
        if (!$gotPackagesPagesTsConfigFromCache) {
            foreach ($this->packageManager->getActivePackages() as $package) {
                $packagePath = $package->getPackagePath();
                $tsConfigFile = null;
                if (file_exists($packagePath . 'Configuration/page.tsconfig')) {
                    $tsConfigFile = $packagePath . 'Configuration/page.tsconfig';
                } elseif (file_exists($packagePath . 'Configuration/Page.tsconfig')) {
                    $tsConfigFile = $packagePath . 'Configuration/Page.tsconfig';
                }
                if ($tsConfigFile) {
                    $typoScriptString = @file_get_contents($tsConfigFile);
                    if (!empty($typoScriptString)) {
                        $collectedPagesTsConfigArray['pagesTsConfig-package-' . $package->getPackageKey()] = $typoScriptString;
                    }
                }
            }
            $cache?->set('pagestsconfig-packages-strings', 'return unserialize(\'' . addcslashes(serialize($collectedPagesTsConfigArray), '\'\\') . '\');');
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] ?? '')) {
            $collectedPagesTsConfigArray['pagesTsConfig-globals-defaultPageTSconfig'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];
        }

        foreach ($rootLine as $page) {
            if (empty($page['uid'])) {
                // Page 0 can happen when the rootline is given from BE context. It has not TSconfig. Skip this.
                continue;
            }
            if (trim($page['tsconfig_includes'] ?? '')) {
                $includeTsConfigFileList = GeneralUtility::trimExplode(',', $page['tsconfig_includes'], true);
                foreach ($includeTsConfigFileList as $key => $includeTsConfigFile) {
                    if (PathUtility::isExtensionPath($includeTsConfigFile)) {
                        [$includeTsConfigFileExtensionKey, $includeTsConfigFilename] = explode('/', substr($includeTsConfigFile, 4), 2);
                        if ($includeTsConfigFileExtensionKey !== ''
                            && ExtensionManagementUtility::isLoaded($includeTsConfigFileExtensionKey)
                            && $includeTsConfigFilename !== ''
                        ) {
                            $extensionPath = ExtensionManagementUtility::extPath($includeTsConfigFileExtensionKey);
                            $includeTsConfigFileAndPath = PathUtility::getCanonicalPath($extensionPath . $includeTsConfigFilename);
                            if (str_starts_with($includeTsConfigFileAndPath, $extensionPath) && file_exists($includeTsConfigFileAndPath)) {
                                $typoScriptString = (string)file_get_contents($includeTsConfigFileAndPath);
                                if (!empty($typoScriptString)) {
                                    $collectedPagesTsConfigArray['pagesTsConfig-page-' . $page['uid'] . '-includes-' . $key] = (string)file_get_contents($includeTsConfigFileAndPath);
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($page['TSconfig'])) {
                $collectedPagesTsConfigArray['pagesTsConfig-page-' . $page['uid'] . '-tsConfig'] = $page['TSconfig'];
            }
        }

        // @deprecated since TYPO3 v12, remove together with event class in v13.
        $event = $this->eventDispatcher->dispatch(new LegacyModifyLoadedPageTsConfigEvent($collectedPagesTsConfigArray, $rootLine));
        $collectedPagesTsConfigArray = $event->getTsConfig();

        $event = $this->eventDispatcher->dispatch(new ModifyLoadedPageTsConfigEvent($collectedPagesTsConfigArray, $rootLine));
        $collectedPagesTsConfigArray = $event->getTsConfig();

        $includeTree = new RootInclude();
        foreach ($collectedPagesTsConfigArray as $key => $typoScriptString) {
            $includeTree->addChild($this->getTreeFromString((string)$key, $typoScriptString, $tokenizer, $cache));
        }
        return $includeTree;
    }

    private function getTreeFromString(
        string $name,
        string $typoScriptString,
        TokenizerInterface $tokenizer,
        ?PhpFrontend $cache = null
    ): TsConfigInclude {
        $lowercaseName = mb_strtolower($name);
        $identifier = $lowercaseName . '-' . hash('xxh3', $typoScriptString);
        if ($cache) {
            $includeNode = $cache->require($identifier);
            if ($includeNode instanceof TsConfigInclude) {
                return $includeNode;
            }
        }
        $includeNode = new TsConfigInclude();
        $includeNode->setName($name);
        $includeNode->setLineStream($tokenizer->tokenize($typoScriptString));
        $this->treeFromTokenStreamBuilder->buildTree($includeNode, 'tsconfig', $tokenizer);
        $cache?->set($identifier, 'return unserialize(\'' . addcslashes(serialize($includeNode), '\'\\') . '\');');
        return $includeNode;
    }
}
