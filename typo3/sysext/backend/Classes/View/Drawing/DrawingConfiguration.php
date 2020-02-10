<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\Drawing;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Drawing Configuration
 *
 * Attached to BackendLayout as storage for configuration options which
 * determine how a page layout is rendered. Contains settings for active
 * language, show-hidden, site languages etc. and returns TCA labels for
 * tt_content fields and CTypes.
 *
 * Corresponds to legacy public properties from PageLayoutView.
 */
class DrawingConfiguration
{
    /**
     * @var bool
     */
    protected $defaultLanguageBinding = true;

    /**
     * @var bool
     */
    protected $languageMode = false;

    /**
     * @var array
     */
    protected $languageColumns = [];

    /**
     * @var int
     */
    protected $languageColumnsPointer = 0;

    /**
     * @var bool
     */
    protected $showHidden = true;

    /**
     * @var array
     */
    protected $activeColumns = [1, 0, 2, 3];

    /**
     * @var array
     */
    protected $contentTypeLabels = [];

    /**
     * @var array
     */
    protected $itemLabels = [];

    /**
     * @var int
     */
    protected $pageId = 0;

    /**
     * @var array
     */
    protected $pageRecord = [];

    /**
     * @var SiteLanguage[]
     */
    protected $siteLanguages = [];

    /**
     * @var bool
     */
    protected $showNewContentWizard = true;

    public function getDefaultLanguageBinding(): bool
    {
        return $this->defaultLanguageBinding;
    }

    public function setDefaultLanguageBinding(bool $defaultLanguageBinding): void
    {
        $this->defaultLanguageBinding = $defaultLanguageBinding;
    }

    public function getLanguageMode(): bool
    {
        return $this->languageMode;
    }

    public function setLanguageMode(bool $languageMode): void
    {
        $this->languageMode = $languageMode;
    }

    public function getLanguageColumns(): array
    {
        if ($this->languageColumnsPointer) {
            return [0 => 0, $this->languageColumnsPointer => $this->languageColumnsPointer];
        }
        return $this->languageColumns;
    }

    public function setLanguageColumns(array $languageColumns): void
    {
        $this->languageColumns = $languageColumns;
    }

    public function getLanguageColumnsPointer(): int
    {
        return $this->languageColumnsPointer;
    }

    public function setLanguageColumnsPointer(int $languageColumnsPointer): void
    {
        $this->languageColumnsPointer = $languageColumnsPointer;
    }

    public function getShowHidden(): bool
    {
        return $this->showHidden;
    }

    public function setShowHidden(bool $showHidden): void
    {
        $this->showHidden = $showHidden;
    }

    public function getActiveColumns(): array
    {
        return $this->activeColumns;
    }

    public function setActiveColumns(array $activeColumns): void
    {
        $this->activeColumns = $activeColumns;
    }

    public function getContentTypeLabels(): array
    {
        if (empty($this->contentTypeLabels)) {
            foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
                $this->contentTypeLabels[$val[1]] = $this->getLanguageService()->sL($val[0]);
            }
        }
        return $this->contentTypeLabels;
    }

    public function getItemLabels(): array
    {
        if (empty($this->itemLabels)) {
            foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
                $this->itemLabels[$name] = $this->getLanguageService()->sL($val['label']);
            }
        }
        return $this->itemLabels;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
        $this->pageRecord = BackendUtility::getRecordWSOL('pages', $pageId);
    }

    public function getPageRecord(): array
    {
        return $this->pageRecord;
    }

    /**
     * @return SiteLanguage[]
     */
    public function getSiteLanguages(): array
    {
        if (empty($this->setSiteLanguages)) {
            try {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->pageId);
            } catch (SiteNotFoundException $e) {
                $site = new NullSite();
            }
            $this->siteLanguages = $site->getAvailableLanguages($this->getBackendUser(), false, $this->pageId);
        }
        return $this->siteLanguages;
    }

    public function getSiteLanguage(int $languageUid): ?SiteLanguage
    {
        return $this->getSiteLanguages()[$languageUid] ?? null;
    }

    public function getShowNewContentWizard(): bool
    {
        return $this->showNewContentWizard;
    }

    public function setShowNewContentWizard(bool $showNewContentWizard): void
    {
        $this->showNewContentWizard = $showNewContentWizard;
    }

    public function getLocalizedPageTitle(): string
    {
        if ($this->getLanguageColumnsPointer()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $localizedPage = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($this->getPageId(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($this->getLanguageColumnsPointer(), \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
            BackendUtility::workspaceOL('pages', $localizedPage);
            return $localizedPage['title'];
        }
        return $this->getPageRecord()['title'];
    }

    public function isPageEditable(): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        $pageRecord = $this->getPageRecord();
        return !$pageRecord['editlock'] && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::PAGE_EDIT);
    }

    public function getNewLanguageOptions(): array
    {
        if (!$this->getBackendUser()->check('tables_modify', 'pages')) {
            return '';
        }
        $id = $this->getPageId();

        // First, select all languages that are available for the current user
        $availableTranslations = [];
        foreach ($this->getSiteLanguages() as $language) {
            if ($language->getLanguageId() === 0) {
                continue;
            }
            $availableTranslations[$language->getLanguageId()] = $language->getTitle();
        }

        // Then, subtract the languages which are already on the page:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            );
        $statement = $queryBuilder->execute();
        while ($row = $statement->fetch()) {
            unset($availableTranslations[(int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']]]);
        }
        // If any languages are left, make selector:
        $options = [];
        if (!empty($availableTranslations)) {
            $options[] = $this->getLanguageService()->getLL('new_language');
            foreach ($availableTranslations as $languageUid => $languageTitle) {
                // Build localize command URL to DataHandler (tce_db)
                // which redirects to FormEngine (record_edit)
                // which, when finished editing should return back to the current page (returnUrl)
                $parameters = [
                    'justLocalized' => 'pages:' . $id . ':' . $languageUid,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $redirectUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', $parameters);
                $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                    '&cmd[pages][' . $id . '][localize]=' . $languageUid,
                    $redirectUrl
                );

                $options[$targetUrl] = $languageTitle;
            }
        }
        return $options;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
