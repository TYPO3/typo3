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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\DefaultTypoScriptInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\DefaultTypoScriptMagicKeyInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ExtensionStaticInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\FileInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeStaticFileDatabaseInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeStaticFileFileInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SiteInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SysTemplateInclude;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Create a tree representing all TypoScript includes.
 *
 * This is the 'middle' part of the TypoScript parsing process: The tokenizers as "lowest"
 * structure create line streams from TypoScript, the AST builder as "highest" structure create
 * the TypoScript object tree.
 *
 * This structure gathers all TypoScript snippets that have to be tokenized, and creates a
 * tree with include nodes and sub include nodes.
 *
 * It is called in frontend (and backend "Template" module) with the page rootline, gets all
 * attached sys_template records, gets their content and various sub includes and takes care
 * of correct include order.
 *
 * This class together with TreeFromTokenLineStreamBuilder also takes care of conditions and
 * imports ("@import" and "<INCLUDE_TYPOSCRIPT:"): Those create child nodes in the tree. To
 * evaluate conditions, the tree is later traversed, condition verdicts (true / false) are
 * determined, to see if condition's child nodes should be considered in AST.
 *
 * The IncludeTree is "runtime stateless": Constants values and conditions are *not* evaluated
 * here, so the tree is always the same for a given rootline. This makes this structure cache-able:
 * In frontend, the tree (or sub parts of it) is cached and fetched from cache for next
 * call. This means the entire tree-building and tokenizing is suppressed. After that runtime
 * information is added: Conditions are evaluated, and the AST is built from given IncludeTree.
 *
 * @internal: Internal tree structure.
 */
final class TreeBuilder
{
    /**
     * Usually, "extension statics" (auto-loaded files "ext_typoscript_constants.txt" and "ext_typoscript_setup.typoscript")
     * of *all* extensions are included with sys_template records that have root = 1 set. This can be manipulated using the
     * 'static_file_mode' value on sys_template records.
     * Extbase backend modules may depend on loaded extension statics. If now there is no sys_template at all in
     * a tree, (and the extbase BackendConfigurationManager selects the "first" page as fake base page if there
     * is no page uid given), the 'ext_typoscript_*' files from extensions are never loaded, and extbase
     * chokes. Extbase's BackendConfigurationManager thus sets forceProcessExtensionStatics() here to
     * make sure extension statics are loaded at least once.
     * The two variables below track that, so extension statics are forced to be loaded if requested by Extbase.
     * All that is of course hacky and should vanish, probably by obsoleting extbase's BE dependency to
     * FE TypoScript altogether, since that's an evil misconception.
     * Note this flag has a logical collision with sys_template records that have static_file_mode = 2, since it still
     * triggers extension static inclusion *even though* static_file_mode = 2 is set.
     */
    private bool $extensionStaticsProcessed = false;
    private bool $forceProcessExtensionStatics = false;

    /**
     * Used in 'basedOn' includes to prevent endless loop: Each sys_template row can
     * be included only once in 'basedOn'.
     *
     * @var array<int, int>
     */
    private array $includedSysTemplateUids = [];

    /**
     * Either 'constants' or 'setup'
     */
    private string $type;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly PackageManager $packageManager,
        private readonly Context $context,
        private readonly TreeFromLineStreamBuilder $treeFromTokenStreamBuilder,
        private TokenizerInterface $tokenizer,
        private ?PhpFrontend $cache = null,
    ) {
    }

    /**
     * Setting a different Tokenizer than the default injected LossyTokenizer.
     * Disables caching to ensure backend TypoScript IncludeTrees are never cached!
     * Used in backend Template module only.
     */
    public function setTokenizer(TokenizerInterface $tokenizer): void
    {
        $this->tokenizer = $tokenizer;
        $this->disableCache();
    }

    /**
     * Used in FE in no_cache = true context.
     * @todo: Maybe change this and simply hand over a cache to getTreeBySysTemplateRowsAndSite() when needed?
     */
    public function disableCache(): void
    {
        $this->cache = null;
    }

    /**
     * This is a special case for extbase BE modules and should not be used otherwise. See property comment.
     */
    public function forceProcessExtensionStatics(): void
    {
        $this->forceProcessExtensionStatics = true;
    }

    public function getTreeBySysTemplateRowsAndSite(string $type, array $sysTemplateRows, ?SiteInterface $site = null): RootInclude
    {
        if (!in_array($type, ['constants', 'setup'])) {
            throw new \RuntimeException('type must be either constants or setup', 1653737656);
        }

        $this->type = $type;
        $this->includedSysTemplateUids = [];
        $this->extensionStaticsProcessed = false;

        $includeTree = new RootInclude();

        foreach ($sysTemplateRows as $sysTemplateRow) {
            $identifier = $this->getSysTemplateRowCacheIdentifier($sysTemplateRow);
            if ($this->cache) {
                // Get from cache if possible
                $includeNode = $this->cache->require($identifier);
                if ($includeNode) {
                    $includeTree->addChild($includeNode);
                    continue;
                }
            }
            $includeNode = new SysTemplateInclude();
            $name = '[sys_template:' . $sysTemplateRow['uid'] . '] ' . $sysTemplateRow['title'];
            $includeNode->setName($name);
            $includeNode->setIdentifier($identifier);
            $includeNode->setPid((int)$sysTemplateRow['pid']);
            if ($this->type === 'constants') {
                $includeNode->setLineStream($this->tokenizer->tokenize($sysTemplateRow['constants'] ?? ''));
            } else {
                $includeNode->setLineStream($this->tokenizer->tokenize($sysTemplateRow['config'] ?? ''));
            }
            if ($sysTemplateRow['root']) {
                $includeNode->setRoot(true);
            }
            $clear = $sysTemplateRow['clear'];
            if (($this->type === 'constants' && $clear & 1)
                || ($this->type === 'setup' && $clear & 2)
            ) {
                $includeNode->setClear(true);
            }
            $this->handleSysTemplateRecordInclude($includeNode, $sysTemplateRow, $site);
            $this->treeFromTokenStreamBuilder->buildTree($includeNode, $this->type, $this->tokenizer);
            $this->cache?->set($identifier, $this->prepareNodeForCache($includeNode));
            $includeTree->addChild($includeNode);
        }

        if ($this->forceProcessExtensionStatics && !$this->extensionStaticsProcessed) {
            // Extbase hack: See property description above.
            if ($this->type === 'constants') {
                $this->addDefaultTypoScriptFromGlobals($includeTree);
                $this->addDefaultTypoScriptConstantsFromSite($includeTree, $site);
            } else {
                $this->addDefaultTypoScriptFromGlobals($includeTree);
            }
            $this->addExtensionStatics($includeTree);
        }

        return $includeTree;
    }

    /**
     * Add includes defined in a sys_template record.
     */
    private function handleSysTemplateRecordInclude(IncludeInterface $parentNode, array $row, ?SiteInterface $site): void
    {
        $this->includedSysTemplateUids[] = (int)$row['uid'];

        $isRoot = (bool)$row['root'];
        $clearConstants = (int)$row['clear'] & 1;
        $clearSetup = (int)$row['clear'] & 2;
        $staticFileMode = (int)($row['static_file_mode']);
        $includeStaticAfterBasedOn = (bool)$row['includeStaticAfterBasedOn'];

        if ($this->type === 'constants' && $clearConstants) {
            $this->addDefaultTypoScriptFromGlobals($parentNode);
            $this->addDefaultTypoScriptConstantsFromSite($parentNode, $site);
        }
        if ($this->type === 'setup' && $clearSetup) {
            $this->addDefaultTypoScriptFromGlobals($parentNode);
        }
        if ($staticFileMode === 3 && $isRoot) {
            $this->addExtensionStatics($parentNode);
        }
        if (!$includeStaticAfterBasedOn) {
            $this->handleIncludeStaticFileArray($parentNode, (string)$row['include_static_file']);
        }
        if (!empty($row['basedOn'])) {
            $this->handleIncludeBasedOnTemplates($parentNode, (string)$row['basedOn'], $site);
        }
        if ($includeStaticAfterBasedOn) {
            $this->handleIncludeStaticFileArray($parentNode, (string)$row['include_static_file']);
        }
        if ($staticFileMode === 1 || ($staticFileMode === 0 && $isRoot)) {
            $this->addExtensionStatics($parentNode);
        }
    }

    /**
     * Handle includes defined in a sys_template['include_static_file'] row. Extracted as
     * methods since it depends on 'includeStaticAfterBasedOn' field if this is included
     * *before* or *after* other 'basedOn' includes.
     *
     * The cache implemented here *does not* take the *content* of files into account.
     * This means changing a file *does not* automatically void the cache since that would
     * lead to lots of file_exists() and file_get_contents() calls in production.
     * Instances in development context should thus set the typoscript-cache to NullFrontend.
     * Note this cache-usage is the main-cache that kicks in whenever different sys_template
     * records include the same file. For instance, when multiple sites include ext:seo XmlSitemap,
     * the cache implementation here takes care the ext:seo sub-tree is calculated only once.
     */
    private function handleIncludeStaticFileArray(IncludeInterface $parentNode, string $includeStaticFileString): void
    {
        $includeStaticFileIncludeArray = GeneralUtility::trimExplode(',', $includeStaticFileString, true);
        foreach ($includeStaticFileIncludeArray as $includeStaticFile) {
            $cacheIdentifier = str_replace(['/', '\'', ':', '.'], '-', $includeStaticFile) . '-' . $this->type;
            if ($this->cache) {
                $node = $this->cache->require($cacheIdentifier);
                if ($node) {
                    $parentNode->addChild($node);
                    continue;
                }
            }
            $node = new IncludeStaticFileDatabaseInclude();
            $node->setIdentifier($includeStaticFile);
            $node->setName($includeStaticFile);
            $this->handleSingleIncludeStaticFile($node, $includeStaticFile);
            $this->cache?->set($cacheIdentifier, $this->prepareNodeForCache($node));
            $parentNode->addChild($node);
        }
    }

    /**
     * Handle includes defined in a sys_template['basedOn'] row.
     * Warning: Calls handleSysTemplateRecordInclude() recursive when another basedOn templates
     *          record includes things again!
     */
    private function handleIncludeBasedOnTemplates(IncludeInterface $parentNode, string $basedOnList, ?SiteInterface $site): void
    {
        $basedOnTemplateUids = GeneralUtility::intExplode(',', $basedOnList, true);
        // Filter uids that have been handled already.
        $basedOnTemplateUids = array_diff($basedOnTemplateUids, $this->includedSysTemplateUids);
        if (empty($basedOnTemplateUids)) {
            return;
        }

        $basedOnTemplateRows = $this->getBasedOnSysTemplateRowsFromDatabase($basedOnTemplateUids);

        foreach ($basedOnTemplateUids as $basedOnTemplateUid) {
            if (is_array($basedOnTemplateRows[$basedOnTemplateUid] ?? false)) {
                $sysTemplateRow = $basedOnTemplateRows[$basedOnTemplateUid];
                $this->includedSysTemplateUids[] = (int)$sysTemplateRow['uid'];
                $includeNode = new SysTemplateInclude();
                $identifier = 'sys_template:' . $sysTemplateRow['uid'];
                $includeNode->setIdentifier($identifier);
                $name = '[sys_template:' . $sysTemplateRow['uid'] . '] ' . $sysTemplateRow['title'];
                $includeNode->setName($name);
                $includeNode->setPid((int)$sysTemplateRow['pid']);
                if ($this->type === 'constants') {
                    $includeNode->setLineStream($this->tokenizer->tokenize($sysTemplateRow['constants'] ?? ''));
                } else {
                    $includeNode->setLineStream($this->tokenizer->tokenize($sysTemplateRow['config'] ?? ''));
                }
                $this->treeFromTokenStreamBuilder->buildTree($includeNode, $this->type, $this->tokenizer);
                if ($sysTemplateRow['root']) {
                    $includeNode->setRoot(true);
                }
                $clear = $sysTemplateRow['clear'];
                if (($this->type === 'constants' && $clear & 1)
                    || ($this->type === 'setup' && $clear & 2)
                ) {
                    $includeNode->setClear(true);
                }
                $parentNode->addChild($includeNode);
                $this->handleSysTemplateRecordInclude($includeNode, $sysTemplateRow, $site);
            }
        }
    }

    /**
     * Handle a single sys_template ['include_static_file'] include.
     * Looks up file "EXT:/My/Path/include_static_file.txt' in an extension and includes this.
     * Also loads "EXT:/My/Path/[constants|setup].[typoscript|ts|txt].
     * Warning: Recursive since an include_static_file.txt file can include other extension's include_static_file.txt again.
     * This method has no cache-layer usage on it's own: handleSingleIncludeStaticFile() which calls this
     * method is the cache layer here.
     */
    private function handleSingleIncludeStaticFile(IncludeInterface $parentNode, $includeStaticFileString): void
    {
        if (!PathUtility::isExtensionPath($includeStaticFileString)) {
            // Must start with 'EXT:'
            throw new \RuntimeException(
                'Single include_static_file does not start with "EXT:": ' . $includeStaticFileString,
                1651137904
            );
        }

        // Cut off 'EXT:'
        $includeStaticFileWithoutExt = substr($includeStaticFileString, 4);
        $includeStaticFileExtKeyAndPath = GeneralUtility::trimExplode('/', $includeStaticFileWithoutExt, true, 2);
        if (empty($includeStaticFileExtKeyAndPath[0]) || empty($includeStaticFileExtKeyAndPath[1])) {
            throw new \RuntimeException(
                'Syntax of static includes is "EXT:extension_key/Path". Usually enforced as such by ExtensionManagementUtility::addStaticFile',
                1651138603
            );
        }
        $extensionKey = $includeStaticFileExtKeyAndPath[0];
        if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
            return;
        }
        // example: '/.../my_extension/Configuration/TypoScript/MyStaticInclude/'
        $pathSegmentWithAppendedSlash = rtrim($includeStaticFileExtKeyAndPath[1]) . '/';
        $path = ExtensionManagementUtility::extPath($extensionKey, $pathSegmentWithAppendedSlash);

        // '/.../my_extension/Configuration/TypoScript/MyStaticInclude/include_static_file.txt'
        $includeStaticFileFileIncludePath = $path . 'include_static_file.txt';
        if (file_exists($path . 'include_static_file.txt')) {
            $includeStaticFileFileInclude = new IncludeStaticFileFileInclude();
            $identifier = 'EXT:' . $extensionKey . '/' . $pathSegmentWithAppendedSlash . 'include_static_file.txt';
            $includeStaticFileFileInclude->setIdentifier($identifier);
            $includeStaticFileFileInclude->setName($identifier);
            $parentNode->addChild($includeStaticFileFileInclude);
            $includeStaticFileFileIncludeContent = (string)file_get_contents($includeStaticFileFileIncludePath);
            // @todo: There is no array_unique() for DB based include_static_file content?!
            $includeStaticFileFileIncludeArray = array_unique(GeneralUtility::trimExplode(',', $includeStaticFileFileIncludeContent));
            foreach ($includeStaticFileFileIncludeArray as $includeStaticFileFileIncludeString) {
                $this->handleSingleIncludeStaticFile($includeStaticFileFileInclude, $includeStaticFileFileIncludeString);
            }
        }

        $extensions = ['.typoscript', '.ts', '.txt'];
        foreach ($extensions as $extension) {
            // '/.../my_extension/Configuration/TypoScript/MyStaticInclude/[constants|setup]' plus one of the allowed extensions like '.typoscript'
            $fileName = $path . $this->type . $extension;
            if (file_exists($fileName)) {
                $fileContent = file_get_contents($fileName);
                $fileNode = new FileInclude();
                $fileNode->setIdentifier('EXT:' . $extensionKey . '/' . $pathSegmentWithAppendedSlash . $this->type . $extension);
                $fileNode->setName('EXT:' . $extensionKey . '/' . $pathSegmentWithAppendedSlash . $this->type . $extension);
                $fileNode->setLineStream($this->tokenizer->tokenize($fileContent));
                $this->treeFromTokenStreamBuilder->buildTree($fileNode, $this->type, $this->tokenizer);
                $parentNode->addChild($fileNode);
            }
        }

        $extensionKeyWithoutUnderscores = str_replace('_', '', $extensionKey);
        $this->addStaticMagicFromGlobals($parentNode, $extensionKeyWithoutUnderscores . '/' . $pathSegmentWithAppendedSlash);
    }

    /**
     * Load 'EXT:my_extension/ext_typoscript_[constants|setup].typoscript'
     * of *all* loaded extensions if they exist.
     */
    private function addExtensionStatics(IncludeInterface $parentNode): void
    {
        $this->extensionStaticsProcessed = true;
        foreach ($this->packageManager->getActivePackages() as $package) {
            $extensionKey = $package->getPackageKey();
            $extensionKeyWithoutUnderscores = str_replace('_', '', $extensionKey);
            $file = $package->getPackagePath() . 'ext_typoscript_' . $this->type . '.typoscript';
            if (file_exists($file)) {
                $identifier = 'EXT-' . $extensionKey . '-ext_typoscript_' . $this->type . '-typoscript';
                if ($this->cache) {
                    $node = $this->cache->require($identifier);
                    if ($node) {
                        $parentNode->addChild($node);
                        return;
                    }
                }
                $fileContent = file_get_contents($file);
                $this->addStaticMagicFromGlobals($parentNode, $extensionKeyWithoutUnderscores);
                $node = new ExtensionStaticInclude();
                $node->setIdentifier($identifier);
                $node->setName('EXT:' . $extensionKey . '/ext_typoscript_' . $this->type . '.typoscript');
                $node->setLineStream($this->tokenizer->tokenize($fileContent));
                $this->treeFromTokenStreamBuilder->buildTree($node, $this->type, $this->tokenizer);
                $this->cache?->set($identifier, $this->prepareNodeForCache($node));
                $parentNode->addChild($node);
            }
        }
    }

    /**
     * Load default constants TS from $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_[constants|setup]']
     * whenever 'root=1' is set for a sys_template.
     */
    private function addDefaultTypoScriptFromGlobals(IncludeInterface $parentConstantNode): void
    {
        $defaultTypoScriptConstants = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $this->type] ?? '';
        if (!empty($defaultTypoScriptConstants)) {
            $identifier = 'globals-defaultTypoScript-' . $this->type . '-' . sha1($defaultTypoScriptConstants);
            if ($this->cache) {
                $node = $this->cache->require($identifier);
                if ($node) {
                    $parentConstantNode->addChild($node);
                    return;
                }
            }
            $node = new DefaultTypoScriptInclude();
            $node->setIdentifier($identifier);
            $node->setName('TYPO3_CONF_VARS[\'FE\'][\'defaultTypoScript_' . $this->type . '\']');
            $node->setLineStream($this->tokenizer->tokenize($defaultTypoScriptConstants));
            $this->treeFromTokenStreamBuilder->buildTree($node, $this->type, $this->tokenizer);
            $this->cache?->set($identifier, $this->prepareNodeForCache($node));
            $parentConstantNode->addChild($node);
        }
    }

    /**
     * Load default TS constants from site configuration if that page has a site in rootline.
     */
    private function addDefaultTypoScriptConstantsFromSite(IncludeInterface $parentConstantNode, ?SiteInterface $site): void
    {
        if (!$site instanceof Site) {
            return;
        }
        $siteConstants = '';
        $siteSettings = $site->getConfiguration()['settings'] ?? [];
        if (empty($siteSettings)) {
            return;
        }
        $identifier = 'site-constants-' . sha1(json_encode($siteSettings, JSON_THROW_ON_ERROR));
        if ($this->cache) {
            $node = $this->cache->require($identifier);
            if ($node) {
                $parentConstantNode->addChild($node);
                return;
            }
        }
        $siteSettings = ArrayUtility::flattenPlain($siteSettings);
        foreach ($siteSettings as $nodeIdentifier => $value) {
            $siteConstants .= $nodeIdentifier . ' = ' . $value . LF;
        }
        $node = new SiteInclude();
        $node->setIdentifier($identifier);
        $node->setName('Site constants settings of site ' . $site->getIdentifier());
        $node->setLineStream($this->tokenizer->tokenize($siteConstants));
        $this->cache?->set($identifier, $this->prepareNodeForCache($node));
        $parentConstantNode->addChild($node);
    }

    /**
     * A rather weird lookup in $GLOBALS['TYPO3_CONF_VARS']['FE'] for magic includes.
     * See ExtensionManagementUtility::addTypoScript() for more details on this.
     */
    private function addStaticMagicFromGlobals(IncludeInterface $parentNode, string $identifier): void
    {
        // defaultTypoScript_constants.' or defaultTypoScript_setup.'
        $source = $GLOBALS['TYPO3_CONF_VARS ']['FE']['defaultTypoScript_' . $this->type . '.'][$identifier] ?? null;
        if (!empty($source)) {
            $node = new DefaultTypoScriptMagicKeyInclude();
            $node->setIdentifier('globals-defaultTypoScript-' . $this->type . '-' . $identifier);
            $node->setName('TYPO3_CONF_VARS globals_defaultTypoScript_' . $this->type . '.' . $identifier);
            $node->setLineStream($this->tokenizer->tokenize($source));
            $this->treeFromTokenStreamBuilder->buildTree($node, $this->type, $this->tokenizer);
            $parentNode->addChild($node);
        }
        // If this is a template of type "default content rendering", see if other extensions have added their TypoScript that should be included.
        if (in_array($identifier, $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'], true)) {
            $source = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $this->type . '.']['defaultContentRendering'] ?? null;
            if (!empty($source)) {
                $node = new DefaultTypoScriptMagicKeyInclude();
                $node->setIdentifier('globals-defaultTypoScript-' . $this->type . '-defaultContentRendering-' . $identifier);
                $node->setName('TYPO3_CONF_VARS defaultContentRendering ' . $this->type . ' for ' . $identifier);
                $node->setLineStream($this->tokenizer->tokenize($source));
                $this->treeFromTokenStreamBuilder->buildTree($node, $this->type, $this->tokenizer);
                $parentNode->addChild($node);
            }
        }
    }

    /**
     * Get 'basedOn' sys_template sub-rows of sys_templates that use this.
     * Note the 'IN()' query implementation below delivers rows in *any* order. To preserve
     * basedOn list order, we re-index result rows by uid and then iterate on the original
     * order of $basedOnTemplateUids in handleIncludeBasedOnTemplates().
     */
    private function getBasedOnSysTemplateRowsFromDatabase(array $basedOnTemplateUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $queryBuilder->setRestrictions($this->getSysTemplateQueryRestrictionContainer());
        $basedOnTemplateRows = $queryBuilder
            ->select('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($basedOnTemplateUids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
        return array_combine(array_column($basedOnTemplateRows, 'uid'), $basedOnTemplateRows);
    }

    /**
     * Calculate a cache identifier for a sys_template row.
     * This is a bit nifty: There are instances in the wild that add the same TypoScript
     * sys_template over and over again in a page tree to for instance toggle a single value.
     * Those content-identical template rows create only one cache entry: We create a hash
     * from the relevant row fields like 'constants' and 'config', but we do NOT include
     * the sys_template row 'uid' and 'pid'. So different sys_template rows with the same content
     * lead to the same identifier and we cache that just once.
     */
    private function getSysTemplateRowCacheIdentifier(array $sysTemplateRow): string
    {
        $cacheRelevantSysTemplateRowValues = [
            'root' => (int)$sysTemplateRow['root'],
            'clear' => (int)$sysTemplateRow['clear'],
            'include_static_file' => (string)$sysTemplateRow['include_static_file'],
            'constants' => (string)$sysTemplateRow['constants'],
            'config' => (string)$sysTemplateRow['config'],
            'basedOn' => (string)$sysTemplateRow['basedOn'],
            'includeStaticAfterBasedOn' => (int)$sysTemplateRow['includeStaticAfterBasedOn'],
            'static_file_mode' => (int)$sysTemplateRow['static_file_mode'],
        ];
        return 'sys_template-' . $this->type . '-' . sha1(serialize($cacheRelevantSysTemplateRowValues));
    }

    private function prepareNodeForCache(IncludeInterface $node): string
    {
        return 'return unserialize(\'' . addcslashes(serialize($node), '\'') . '\');';
    }

    /**
     * Get sys_template record query builder restrictions.
     * Allows hidden records if enabled in context.
     */
    private function getSysTemplateQueryRestrictionContainer(): DefaultRestrictionContainer
    {
        $restrictionContainer = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);
        if ($this->context->getPropertyFromAspect('visibility', 'includeHiddenContent', false)) {
            $restrictionContainer->removeByType(HiddenRestriction::class);
        }
        return $restrictionContainer;
    }
}
