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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedPageTsConfigEvent;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;
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
#[Autoconfigure(public: true)]
final class TsConfigTreeBuilder
{
    public function __construct(
        private readonly TreeFromLineStreamBuilder $treeFromTokenStreamBuilder,
        private readonly PackageManager $packageManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly SiteFinder $siteFinder,
        private readonly SetRegistry $setRegistry,
    ) {}

    public function getUserTsConfigTree(
        BackendUserAuthentication $backendUser,
        TokenizerInterface $tokenizer,
        ?PhpFrontend $cache = null
    ): RootInclude {
        $includeTree = new RootInclude();

        $collectedUserTsConfigArray = [];
        $gotPackagesUserTsConfigFromCache = false;
        $cacheIdentifier = (new PackageDependentCacheIdentifier($this->packageManager))
            ->withPrefix('usertsconfig-packages-strings')
            ->toString();
        if ($cache) {
            $collectedUserTsConfigArrayFromCache = $cache->require($cacheIdentifier);
            if ($collectedUserTsConfigArrayFromCache) {
                $gotPackagesUserTsConfigFromCache = true;
                $collectedUserTsConfigArray = $collectedUserTsConfigArrayFromCache;
            }
        }
        if (!$gotPackagesUserTsConfigFromCache) {
            $event = $this->eventDispatcher->dispatch(new BeforeLoadedUserTsConfigEvent());
            $collectedUserTsConfigArray = $event->getTsConfig();
            foreach ($this->packageManager->getActivePackages() as $package) {
                $packagePath = $package->getPackagePath();
                $tsConfigFile = null;
                if (file_exists($packagePath . 'Configuration/user.tsconfig')) {
                    $tsConfigFile = $packagePath . 'Configuration/user.tsconfig';
                } elseif (file_exists($packagePath . 'Configuration/User.tsconfig')) {
                    $tsConfigFile = $packagePath . 'Configuration/User.tsconfig';
                }
                if ($tsConfigFile) {
                    $typoScriptString = @file_get_contents($tsConfigFile);
                    if (!empty($typoScriptString)) {
                        $collectedUserTsConfigArray['userTsConfig-package-' . $package->getPackageKey()] = $typoScriptString;
                    }
                }
            }
            $cache?->set($cacheIdentifier, 'return unserialize(\'' . addcslashes(serialize($collectedUserTsConfigArray), '\'\\') . '\');');
        }
        foreach ($collectedUserTsConfigArray as $key => $typoScriptString) {
            $includeTree->addChild($this->getTreeFromString((string)$key, $typoScriptString, $tokenizer, $cache));
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

        $collectedPagesTsConfigArray += $this->getPackagePageTsConfigTree($cache);

        // HEADS up: rootLine may be modified by getSitePagesTsConfigTree
        $collectedPagesTsConfigArray += $this->getSitePageTsConfigTree($rootLine, $cache);

        $collectedPagesTsConfigArray += $this->getRootlinePageTsConfigTree($rootLine, $cache);

        $event = $this->eventDispatcher->dispatch(new ModifyLoadedPageTsConfigEvent(
            array_map(static fn(array $descriptor): string => $descriptor['content'], $collectedPagesTsConfigArray),
            $rootLine
        ));
        $collectedPagesTsConfigContentArray = $event->getTsConfig();
        foreach ($collectedPagesTsConfigContentArray as $key => $content) {
            $collectedPagesTsConfigArray[$key]['content'] = $content;
        }

        $includeTree = new RootInclude();
        foreach ($collectedPagesTsConfigArray as $key => $descriptor) {
            $typoScriptString = $descriptor['content'];
            $filename = $descriptor['filename'] ?? null;
            $includeTree->addChild($this->getTreeFromString((string)$key, $typoScriptString, $tokenizer, $cache, $filename));
        }
        return $includeTree;
    }

    private function getPackagePageTsConfigTree(
        ?PhpFrontend $cache = null
    ): array {
        $collectedPagesTsConfigArray = [];
        $gotPackagesPagesTsConfigFromCache = false;
        $cacheIdentifier = (new PackageDependentCacheIdentifier($this->packageManager))
            ->withPrefix('pagestsconfig-packages-strings')
            ->toString();
        if ($cache) {
            $collectedPagesTsConfigArrayFromCache = $cache->require($cacheIdentifier);
            if ($collectedPagesTsConfigArrayFromCache) {
                $gotPackagesPagesTsConfigFromCache = true;
                $collectedPagesTsConfigArray = $collectedPagesTsConfigArrayFromCache;
            }
        }
        if (!$gotPackagesPagesTsConfigFromCache) {
            $event = $this->eventDispatcher->dispatch(new BeforeLoadedPageTsConfigEvent());
            $collectedPagesTsConfigArray = array_map(static fn(string $config): array => ['content' => $config, 'filename' => null], $event->getTsConfig());
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
                        $collectedPagesTsConfigArray['pagesTsConfig-package-' . $package->getPackageKey()] = [
                            'filename' => $tsConfigFile,
                            'content' => $typoScriptString,
                        ];
                    }
                }
            }
            $cache?->set($cacheIdentifier, 'return unserialize(\'' . addcslashes(serialize($collectedPagesTsConfigArray), '\'\\') . '\');');
        }
        return $collectedPagesTsConfigArray;
    }

    private function getSitePageTsConfigTree(
        array &$rootLine,
        ?PhpFrontend $cache = null
    ): array {
        $reverseRootLine = array_reverse($rootLine);
        $rootlineUntilSite = [];
        $rootSite = null;
        foreach ($reverseRootLine as $rootLineEntry) {
            array_unshift($rootlineUntilSite, $rootLineEntry);
            $uid = (int)($rootLineEntry['uid'] ?? 0);
            if ($uid === 0) {
                continue;
            }
            try {
                $site = $this->siteFinder->getSiteByRootPageId($uid);
            } catch (SiteNotFoundException) {
                continue;
            }
            if ($site->isTypoScriptRoot()) {
                $rootSite = $site;
                $rootLine = $rootlineUntilSite;
                break;
            }
        }

        if ($rootSite === null) {
            return [];
        }

        $cacheIdentifier = (new PackageDependentCacheIdentifier($this->packageManager))
            ->withPrefix('pagestsconfig-site')
            ->withAdditionalHashedIdentifier($rootSite->getIdentifier())
            ->toString();
        $pageTsConfig = $cache?->require($cacheIdentifier) ?: null;

        if ($pageTsConfig === null) {
            $pageTsConfig = [];
            $sets = $this->setRegistry->getSets(...$rootSite->getSets());
            foreach ($sets as $set) {
                if ($set->pagets !== null && file_exists($set->pagets)) {
                    $content = @file_get_contents($set->pagets);
                    if (!empty($content)) {
                        $pageTsConfig['pageTsConfig-set-' . str_replace('/', '-', $set->name)] = [
                            'filename' => GeneralUtility::getFileAbsFileName($set->pagets),
                            'content' => $content,
                        ];
                    }
                }
            }

            $pageTsConfig['pageTsConfig-site-' . $rootSite->getIdentifier()] = [
                'filename' => GeneralUtility::getFileAbsFileName(Environment::getConfigPath() . '/sites/' . $rootSite->getIdentifier() . '/page.tsconfig'),
                'content' => $rootSite->getTSconfig()->pageTSconfig ?? '',
            ];
            $cache?->set($cacheIdentifier, 'return ' . var_export($pageTsConfig, true) . ';');
        }
        return $pageTsConfig;
    }

    private function getRootlinePageTsConfigTree(
        array $rootLine,
        ?PhpFrontend $cache = null
    ): array {
        $collectedPagesTsConfigArray = [];
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
                                    $collectedPagesTsConfigArray['pagesTsConfig-page-' . $page['uid'] . '-includes-' . $key] = [
                                        'content' => (string)file_get_contents($includeTsConfigFileAndPath),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($page['TSconfig'])) {
                $collectedPagesTsConfigArray['pagesTsConfig-page-' . $page['uid'] . '-tsConfig'] = ['content' => $page['TSconfig']];
            }
        }
        return $collectedPagesTsConfigArray;
    }

    private function getTreeFromString(
        string $name,
        string $typoScriptString,
        TokenizerInterface $tokenizer,
        ?PhpFrontend $cache = null,
        ?string $filename = null,
    ): TsConfigInclude {
        $lowercaseName = mb_strtolower($name);
        $identifier = (new PackageDependentCacheIdentifier($this->packageManager))
            ->withPrefix($lowercaseName)
            ->withAdditionalHashedIdentifier($typoScriptString)
            ->toString();
        if ($cache) {
            $includeNode = $cache->require($identifier);
            if ($includeNode instanceof TsConfigInclude) {
                return $includeNode;
            }
        }
        $includeNode = new TsConfigInclude();
        $includeNode->setName($name);
        if ($filename !== null) {
            $includeNode->setPath($filename);
        }
        $includeNode->setLineStream($tokenizer->tokenize($typoScriptString));
        $this->treeFromTokenStreamBuilder->buildTree($includeNode, 'tsconfig', $tokenizer);
        $cache?->set($identifier, 'return unserialize(\'' . addcslashes(serialize($includeNode), '\'\\') . '\');');
        return $includeNode;
    }
}
