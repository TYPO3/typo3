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

namespace TYPO3\CMS\Backend\Routing;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\Event\AfterPagePreviewUriGeneratedEvent;
use TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Generate links to Frontend URLs with a modified scope.
 */
class PreviewUriBuilder
{
    public const OPTION_SWITCH_FOCUS = 'switchFocus';
    public const OPTION_WINDOW_NAME = 'windowName';
    public const OPTION_WINDOW_FEATURES = 'windowFeatures';
    public const OPTION_WINDOW_SCOPE = 'windowScope';

    public const OPTION_WINDOW_SCOPE_LOCAL = 'local';
    public const OPTION_WINDOW_SCOPE_GLOBAL = 'global';

    protected array $record = [];
    protected int $pageId;
    protected int $languageId = 0;
    protected array $rootLine = [];
    protected string $section = '';
    protected array $additionalQueryParameters = [];
    protected bool $moduleLoading = true;
    protected Context $context;

    /**
     * @param int|array $page Page ID to be previewed
     */
    public static function create(int|array $page): self
    {
        $pageId = 0;
        $schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages');
        $laguageFieldName = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $transOrigPointerFieldName = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
        if (!is_array($page)) {
            $pageId = $page;
            if ($pageId > 0) {
                // If a page ID is given, we fetch the record from the database
                $page = BackendUtility::getRecord('pages', $pageId) ?? [];
            } else {
                // If no valid page ID is given, we use an empty array
                $page = [];
            }
        }
        if ($page !== []) {
            if (($page[$transOrigPointerFieldName] ?? 0) > 0) {
                // If the page is a translation, we need to use the parent page's ID
                $pageId = (int)$page[$transOrigPointerFieldName];
            } else {
                // Otherwise, use the current page's ID
                $pageId = (int)($page['uid'] ?? 0);
            }
        }
        $obj = new self($pageId);
        $obj->pageId = $pageId;
        $obj->record = $page;
        $obj->languageId = (int)($page[$laguageFieldName] ?? 0);
        return $obj;
    }

    /**
     * @internal Only to be used by TYPO3 core - for now
     */
    public static function createForRecordPreview(string $table, int|array $record, int $pageId): self
    {
        $recordId = is_int($record) ? $record : (int)($record['uid'] ?? 0);
        $previewPageId = self::getPreviewPageId($table, $recordId, $pageId);
        $obj = self::create($previewPageId);
        $obj = $obj->withRootLine(BackendUtility::BEgetRootLine($previewPageId));
        $obj = $obj->withSection($table === 'tt_content' ? '#c' . $recordId : '');
        $obj = $obj->withAdditionalQueryParameters(self::getPreviewUrlParameters($previewPageId, $table, $record));

        return $obj;
    }

    /**
     * @param int $pageId Page ID to be previewed
     */
    public function __construct(int $pageId)
    {
        $this->pageId = $pageId;
        $this->context = clone GeneralUtility::makeInstance(Context::class);
        $this->context->setAspect('visibility', new VisibilityAspect(true, false, false, true));
    }

    /**
     * @param bool $moduleLoading whether to enable JavaScript module loading
     * @return static
     */
    public function withModuleLoading(bool $moduleLoading): self
    {
        if ($this->moduleLoading === $moduleLoading) {
            return $this;
        }
        $target = clone $this;
        $target->moduleLoading = $moduleLoading;
        return $target;
    }

    /**
     * @param array $rootLine (alternative) root-line of pages
     * @return static
     */
    public function withRootLine(array $rootLine): self
    {
        if ($this->rootLine === $rootLine) {
            return $this;
        }
        $target = clone $this;
        $target->rootLine = $rootLine;
        return $this;
    }

    /**
     * @param int $language particular language
     * @return static
     */
    public function withLanguage(int $language): self
    {
        if ($this->languageId === $language) {
            return $this;
        }
        $target = clone $this;
        $target->languageId = $language;
        return $target;
    }

    /**
     * @param string $section particular section (anchor element)
     * @return static
     */
    public function withSection(string $section): self
    {
        if ($this->section === $section) {
            return $this;
        }
        $target = clone $this;
        $target->section = $section;
        return $target;
    }

    /**
     * @param string|array $additionalQueryParameters additional URI query parameters
     * @return static
     */
    public function withAdditionalQueryParameters(array|string $additionalQueryParameters): self
    {
        if (is_array($additionalQueryParameters)) {
            $additionalQueryParams = $additionalQueryParameters;
        } else {
            $additionalQueryParams = [];
            parse_str($additionalQueryParameters, $additionalQueryParams);
        }
        $languageId = $this->languageId;
        if (isset($additionalQueryParams['_language'])) {
            $languageId = (int)$additionalQueryParams['_language'];
            unset($additionalQueryParams['_language']);
        }
        // No change
        if ($this->languageId === $languageId && $additionalQueryParams === $this->additionalQueryParameters) {
            return $this;
        }

        $target = clone $this;
        $target->additionalQueryParameters = $additionalQueryParams;
        $target->languageId = $languageId;
        return $target;
    }

    public function isPreviewable(): bool
    {
        if ($this->pageId === 0 || $this->record === []) {
            return false;
        }
        $isDeletePlaceholder = VersionState::tryFrom($this->record['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER;
        if ($isDeletePlaceholder) {
            return false;
        }
        return self::isPreviewableDoktype($this->pageId, (int)$this->record['doktype']);
    }

    /**
     * Builds preview URI.
     */
    public function buildUri(?array $options = null, ?Context $context = null): ?UriInterface
    {
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        try {
            $event = new BeforePagePreviewUriGeneratedEvent(
                $this->pageId,
                $this->languageId,
                $this->rootLine,
                $this->section,
                $this->additionalQueryParameters,
                $context ?? $this->context,
                $this->enrichOptions($options)
            );
            $eventDispatcher->dispatch($event);

            // If there hasn't been a custom preview URI set by an event listener, generate it.
            if ($event->getPreviewUri() === null) {
                if (!$this->isPreviewable()) {
                    return null;
                }
                $permissionClause = $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW);
                $pageInfo = BackendUtility::readPageAccess($event->getPageId(), $permissionClause) ?: [];
                // Check if the page (= its rootline) has a site attached, otherwise just keep the URI as is
                if ($event->getRootline() === []) {
                    $event->setRootline(BackendUtility::BEgetRootLine($event->getPageId()));
                }
                // prepare custom context for link generation (to allow for example time based previews)
                $event->setAdditionalQueryParameters(
                    array_replace_recursive(
                        $event->getAdditionalQueryParameters(),
                        self::getAdditionalQueryParametersForAccessRestrictedPages($pageInfo, $event->getContext(), $event->getRootline())
                    )
                );

                // Build the URI with a site as prefix, if configured
                $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                try {
                    $site = $siteFinder->getSiteByPageId($event->getPageId(), $event->getRootline());
                } catch (SiteNotFoundException $e) {
                    throw new UnableToLinkToPageException('The page ' . $event->getPageId() . ' had no proper connection to a site, no link could be built.', 1651499353);
                }
                try {
                    $previewRouteParameters = $event->getAdditionalQueryParameters();
                    // Reassemble encapsulated language id into route parameters to get proper localized page preview
                    // uri for non-default languages.
                    if ($event->getLanguageId() > 0) {
                        $previewRouteParameters['_language'] = $site->getLanguageById($event->getLanguageId());
                    }
                    $event->setPreviewUri(
                        $site->getRouter($event->getContext())->generateUri(
                            $event->getPageId(),
                            $previewRouteParameters,
                            $event->getSection(),
                            RouterInterface::ABSOLUTE_URL
                        )
                    );
                } catch (\InvalidArgumentException | InvalidRouteArgumentsException $e) {
                    throw new UnableToLinkToPageException(sprintf('The link to the page with ID "%d" could not be generated: %s', $event->getPageId(), $e->getMessage()), 1651499354, $e);
                }
            }

            $event = new AfterPagePreviewUriGeneratedEvent(
                $event->getPreviewUri(),
                $event->getPageId(),
                $event->getLanguageId(),
                $event->getRootline(),
                $event->getSection(),
                $event->getAdditionalQueryParameters(),
                $event->getContext(),
                $event->getOptions(),
            );
            $eventDispatcher->dispatch($event);

            return $event->getPreviewUri();
        } catch (UnableToLinkToPageException $e) {
            return null;
        }
    }

    /**
     * Builds attributes array (e.g. `['dispatch-action' => ...]`).
     * CAVE: Attributes are NOT XSS-protected and need to be put through `htmlspecialchars`
     *
     * @param array|null $options
     */
    public function buildDispatcherDataAttributes(?array $options = null): ?array
    {
        if (null === ($attributes = $this->buildAttributes($options))) {
            return null;
        }
        $this->loadActionDispatcher();
        return $this->prefixAttributeNames('dispatch-', $attributes);
    }

    /**
     * Builds attributes array (e.g. `['data-dispatch-action' => ...]`).
     * CAVE: Attributes are NOT XSS-protected and need to be put through `htmlspecialchars`
     *
     * @param array|null $options
     */
    public function buildDispatcherAttributes(?array $options = null): ?array
    {
        if (null === ($attributes = $this->buildAttributes($options))) {
            return null;
        }
        $this->loadActionDispatcher();
        return $this->prefixAttributeNames('data-dispatch-', $attributes);
    }

    /**
     * Serialized attributes are processed with `htmlspecialchars` and ready to be used.
     *
     * @param array|null $options
     */
    public function serializeDispatcherAttributes(?array $options = null): ?string
    {
        if (null === ($attributes = $this->buildDispatcherAttributes($options))) {
            return null;
        }
        return ' ' . GeneralUtility::implodeAttributes($attributes, true);
    }

    /**
     * `<typo3-immediate-action>` does not have a specific meaning and is used to
     * expose `data` attributes, see custom element in `ImmediateActionElement.ts`.
     *
     * @param array|null $options
     */
    public function buildImmediateActionElement(?array $options = null): ?string
    {
        if (null === ($attributes = $this->buildAttributes($options))) {
            return null;
        }
        $this->loadImmediateActionElement();
        return sprintf(
            // `<typo3-immediate-action action="TYPO3.WindowManager.localOpen" args="[...]">`
            '<typo3-immediate-action %s></typo3-immediate-action>',
            GeneralUtility::implodeAttributes($attributes, true)
        );
    }

    /**
     * Returns the preview page id, based on the given input, by checking
     * preview configuration and alternatively looking up the rootline.
     *
     * @param string $table The table of the record to be previewed - might be empty for direct page preview ($pageId > 0)
     * @param int $recordId The id of the record to be previewed - might be empty for direct page preview ($pageId > 0)
     * @param int $pageId The page to preview the record on, also used to preview a page directly
     * @internal Only to be used by TYPO3 core
     */
    public static function getPreviewPageId(string $table, int $recordId, int $pageId): int
    {
        if ($table === 'pages') {
            $rootPageId = $recordId;
            if ($rootPageId) {
                $l10nPointer = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages')->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
                $page = BackendUtility::getRecord('pages', $rootPageId, 'is_siteroot,' . $l10nPointer);
                if ($page['is_siteroot'] && $page[$l10nPointer]) {
                    $rootPageId = $page[$l10nPointer];
                }
            }
        } else {
            $rootPageId = max(0, $pageId);
        }

        $previewConfiguration = BackendUtility::getPagesTSconfig($rootPageId)['TCEMAIN.']['preview.'][$table . '.'] ?? [];

        if (isset($previewConfiguration['previewPageId'])) {
            return (int)$previewConfiguration['previewPageId'];
        }

        $rootPageData = null;
        $rootLine = BackendUtility::BEgetRootLine($rootPageId);
        $currentPage = (array)(reset($rootLine) ?: []);
        if (VersionState::tryFrom($currentPage['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER
            && self::isPreviewableDoktype((int)($currentPage['uid'] ?? 0), (int)($currentPage['doktype'] ?? 0))
        ) {
            // try the current page
            $previewPageId = $rootPageId;
        } else {
            // or search for the root page
            foreach ($rootLine as $page) {
                if ($page['is_siteroot'] ?? false) {
                    $rootPageData = $page;
                    break;
                }
            }
            $previewPageId = isset($rootPageData)
                ? (int)$rootPageData['uid']
                : $rootPageId;
        }
        return $previewPageId;
    }

    protected function buildAttributes(?array $options = null): ?array
    {
        $options = $this->enrichOptions($options);
        if (null === ($uri = $this->buildUri($options))) {
            return null;
        }
        $args = [
            // target URI
            (string)$uri,
            // whether to switch focus to that window
            $options[self::OPTION_SWITCH_FOCUS],
            // name of the window instance for JavaScript references
            $options[self::OPTION_WINDOW_NAME],
        ];
        if (isset($options[self::OPTION_WINDOW_FEATURES])) {
            // optional window features (e.g. 'width=500,height=300')
            $args[] = $options[self::OPTION_WINDOW_FEATURES];
        }
        return [
            'action' => $options[self::OPTION_WINDOW_SCOPE] === self::OPTION_WINDOW_SCOPE_GLOBAL
                ? 'TYPO3.WindowManager.globalOpen'
                : 'TYPO3.WindowManager.localOpen',
            'args' => json_encode($args),
        ];
    }

    /**
     * Handles options to used for opening preview URI in a new window/tab.
     * + `switchFocus` (bool): whether to focus new window in browser
     * + `windowName` (string): name of window for internal reference
     * + `windowScope` (string): `local` (current document) `global` (whole backend)
     *
     * @param array|null $options
     */
    protected function enrichOptions(?array $options = null): array
    {
        return array_merge(
            [
                self::OPTION_SWITCH_FOCUS => null,
                // 'newTYPO3frontendWindow' was used in BackendUtility::viewOnClick
                self::OPTION_WINDOW_NAME => 'newTYPO3frontendWindow',
                self::OPTION_WINDOW_SCOPE => self::OPTION_WINDOW_SCOPE_LOCAL,
            ],
            $options ?? []
        );
    }

    protected function loadActionDispatcher(): void
    {
        if (!$this->moduleLoading) {
            return;
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@typo3/backend/action-dispatcher.js');
    }

    protected function loadImmediateActionElement(): void
    {
        if (!$this->moduleLoading) {
            return;
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@typo3/backend/element/immediate-action-element.js');
    }

    protected function prefixAttributeNames(string $prefix, array $attributes): array
    {
        $attributeNames = array_map(
            static function (string $name) use ($prefix): string {
                return $prefix . $name;
            },
            array_keys($attributes)
        );
        return array_combine(
            $attributeNames,
            array_values($attributes)
        );
    }

    /**
     * Creates ADMCMD parameters for the "viewpage" extension / frontend
     * @internal not part of TYPO3 Core API
     */
    public static function getAdditionalQueryParametersForAccessRestrictedPages(array $pageInfo, Context $context, array $rootLine): array
    {
        if ($pageInfo === []) {
            return [];
        }
        // Initialize access restriction values from current page
        $access = [
            'fe_group' => (string)($pageInfo['fe_group'] ?? ''),
            'starttime' => (int)($pageInfo['starttime'] ?? 0),
            'endtime' => (int)($pageInfo['endtime'] ?? 0),
        ];
        // Only check rootline if the current page has not set extendToSubpages itself
        if (!(bool)($pageInfo['extendToSubpages'] ?? false)) {
            // remove the current page from the rootline
            array_shift($rootLine);
            foreach ($rootLine as $page) {
                // Skip root node and pages which do not define extendToSubpages
                if ((int)($page['uid'] ?? 0) === 0 || !(bool)($page['extendToSubpages'] ?? false)) {
                    continue;
                }
                $access['fe_group'] = (string)($page['fe_group'] ?? '');
                $access['starttime'] = (int)($page['starttime'] ?? 0);
                $access['endtime'] = (int)($page['endtime'] ?? 0);
                // Stop as soon as a page in the rootline has extendToSubpages set
                break;
            }
        }
        $additionalQueryParameters = [];
        if ((int)$access['fe_group'] === -2) {
            // -2 means "show at any login". We simulate first available fe_group.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('fe_groups');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

            $activeFeGroupId = $queryBuilder->select('uid')
                ->from('fe_groups')
                ->executeQuery()
                ->fetchOne();

            if ($activeFeGroupId) {
                $additionalQueryParameters['ADMCMD_simUser'] = $activeFeGroupId;
            }
        } elseif (!empty($access['fe_group'])) {
            $additionalQueryParameters['ADMCMD_simUser'] = $access['fe_group'];
        }
        if ($access['starttime'] > $GLOBALS['EXEC_TIME']) {
            // simulate access time to ensure PageRepository will find the page and in turn PageRouter will generate
            // a URL for it
            $dateAspect = new DateTimeAspect(DateTimeFactory::createFromTimestamp($access['starttime']));
            $context->setAspect('date', $dateAspect);
            $additionalQueryParameters['ADMCMD_simTime'] = $access['starttime'];
        }
        if ($access['endtime'] < $GLOBALS['EXEC_TIME'] && $access['endtime'] !== 0) {
            // Set access time to page's endtime subtracted one second to ensure PageRepository will find the page and
            // in turn PageRouter will generate a URL for it
            $dateAspect = new DateTimeAspect(DateTimeFactory::createFromTimestamp($access['endtime'] - 1));
            $context->setAspect('date', $dateAspect);
            $additionalQueryParameters['ADMCMD_simTime'] = ($access['endtime'] - 1);
        }
        return $additionalQueryParameters;
    }

    /**
     * Returns the parameters for the preview URL by evaluating language overlays and preview configuration
     */
    protected static function getPreviewUrlParameters(int $previewPageId, string $table, int|array $record): string
    {
        if (is_array($record)) {
            $recordId = (int)($record['uid'] ?? 0);
        } else {
            $recordId = $record;
            $record = BackendUtility::getRecord($table, $record) ?? [];
        }

        $linkParameters = [];
        $previewConfiguration = BackendUtility::getPagesTSconfig($previewPageId)['TCEMAIN.']['preview.'][$table . '.'] ?? [];

        // language handling
        $schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get($table);
        if ($schema->isLanguageAware()
            && ($languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName())
            && !empty($record[$languageField])
        ) {
            $l10nPointer = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
            if (!empty($record[$l10nPointer]) && ($previewConfiguration['useDefaultLanguageRecord'] ?? true) === false) {
                $recordId = (int)$record[$l10nPointer];
            } else {
                $recordId = (int)$record['uid'];
            }
            $language = $record[$languageField];
            if ($language > 0) {
                $linkParameters['_language'] = $language;
            }
        }

        // Always use live workspace record uid for the preview
        if ($schema->isWorkspaceAware() && ($record['t3ver_oid'] ?? 0) > 0) {
            $recordId = $record['t3ver_oid'];
        }

        // map record data to GET parameters
        if (isset($previewConfiguration['fieldToParameterMap.'])) {
            foreach ($previewConfiguration['fieldToParameterMap.'] as $field => $parameterName) {
                $value = $record[$field] ?? '';
                if ($field === 'uid') {
                    $value = $recordId;
                }
                $linkParameters[$parameterName] = $value;
            }
        }

        // add/override parameters by configuration
        if (isset($previewConfiguration['additionalGetParameters.'])) {
            $linkParameters = array_replace(
                $linkParameters,
                GeneralUtility::removeDotsFromTS($previewConfiguration['additionalGetParameters.'])
            );
        }

        return HttpUtility::buildQueryString($linkParameters, '&');
    }

    /**
     * Check whether the current page has a doktype, which can be previewed
     */
    protected static function isPreviewableDoktype(int $pageId, int $doktype): bool
    {
        if ($pageId <= 0 || $doktype <= 0) {
            return false;
        }

        $TSconfig = BackendUtility::getPagesTSconfig($pageId)['TCEMAIN.']['preview.'] ?? [];
        if (isset($TSconfig['disableButtonForDokType'])) {
            $excludeDokTypes = GeneralUtility::intExplode(',', (string)$TSconfig['disableButtonForDokType'], true);
        } else {
            // Exclude sysfolders and spacers by default
            $excludeDokTypes = [
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_SPACER,
            ];
        }
        return !in_array($doktype, $excludeDokTypes, true);
    }
}
