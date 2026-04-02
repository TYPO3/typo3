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

namespace TYPO3\CMS\Backend\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains translation tools
 *
 * @phpstan-type LanguageRef -1|0|positive-int
 * @internal The whole class is subject to be removed, fetch all language info from the current site object.
 */
#[Autoconfigure(public: true)]
readonly class TranslationConfigurationProvider
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private SiteFinder $siteFinder,
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Returns array of languages given for a specific site (or "nullSite" if on page=0)
     * The property flagIcon returns a string <flags-xx>.
     *
     * @param int $pageId Page id (used to get TSconfig configuration setting flag and label for default language)
     * @return array<LanguageRef, array{uid: int, title: string, ISOcode: string, flagIcon: string}> Array with languages
     */
    public function getSystemLanguages(int $pageId = 0): array
    {
        $cacheKey = 'system-language-cache-page-uid-' . $pageId;
        if ($this->runtimeCache->has($cacheKey)) {
            return $this->runtimeCache->get($cacheKey);
        }
        $allSystemLanguages = [];
        if ($pageId === 0) {
            // Used for e.g. filelist, where there is no site selected.
            // This also means that there is no "-1" (All Languages) selectable.
            // Languages are consolidated across all sites with unique titles.
            $sites = $this->siteFinder->getAllSites();
            foreach ($sites as $site) {
                $this->addSiteLanguagesToConsolidatedList(
                    $allSystemLanguages,
                    $site->getAvailableLanguages($this->getBackendUserAuthentication()),
                );
            }
            $this->computeSystemLanguagesTitleAndFlag($allSystemLanguages, true);
        } else {
            try {
                $site = $this->siteFinder->getSiteByPageId($pageId);
            } catch (SiteNotFoundException) {
                $site = new NullSite();
            }
            $siteLanguages = $site->getAvailableLanguages($this->getBackendUserAuthentication(), true);
            if (!isset($siteLanguages[0])) {
                $siteLanguages[0] = $site->getDefaultLanguage();
            }
            $this->addSiteLanguagesToConsolidatedList($allSystemLanguages, $siteLanguages);
            $this->computeSystemLanguagesTitleAndFlag($allSystemLanguages);
        }
        ksort($allSystemLanguages);
        $this->runtimeCache->set($cacheKey, $allSystemLanguages);
        return $allSystemLanguages;
    }

    protected function addSiteLanguagesToConsolidatedList(array &$allSystemLanguages, array $languagesOfSpecificSite): void
    {
        foreach ($languagesOfSpecificSite as $language) {
            $languageId = $language->getLanguageId();
            $allSystemLanguages[$languageId] ??= [
                'uid' => $languageId,
                'titlesMap' => [],
                'flagsMap' => [],
            ];
            $allSystemLanguages[$languageId]['titlesMap'][$language->getTitle()] = true;
            $allSystemLanguages[$languageId]['flagsMap'][$language->getFlagIdentifier()] = true;
        }
    }

    protected function computeSystemLanguagesTitleAndFlag(array &$allSystemLanguages, bool $showIdInTitle = false): void
    {
        foreach ($allSystemLanguages as &$language) {
            $language['title'] = implode(', ', array_keys($language['titlesMap']));
            if ($language['uid'] === 0 && count($language['titlesMap']) > 1) {
                // "Default" label for language 0 with multiple titles.
                $language['title'] = $this->getLanguageService()->translate('LGL.defaultLanguage', 'core.general');
            }
            if ($showIdInTitle) {
                $language['title'] .= ' [' . $language['uid'] . ']';
            }

            $language['flagIcon'] = array_key_first($language['flagsMap']);
            if (count($language['titlesMap']) > 1 || count($language['flagsMap']) > 1) {
                $language['flagIcon'] = 'flags-multiple';
            }

            unset($language['titlesMap'], $language['flagsMap']);
        }
    }

    /**
     * Information about translation for an element
     * Will overlay workspace version of record too!
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int $languageUid Language uid. If 0, then all languages are selected.
     * @param array|null $row The record to be translated
     * @param array|string $selFieldList Select fields for the query which fetches the translations of the current record
     * @return array|string Array with information or error message as a string.
     */
    public function translationInfo($table, $uid, $languageUid = 0, ?array $row = null, $selFieldList = ''): array|string
    {
        if (!$this->tcaSchemaFactory->has($table) || !$uid) {
            return 'No table "' . $table . '" or no UID value';
        }
        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->isLanguageAware()) {
            return 'Translation is not supported for this table!';
        }
        if ($row === null) {
            $row = BackendUtility::getRecordWSOL($table, $uid);
        }
        if (!is_array($row)) {
            return 'Record "' . $table . '_' . $uid . '" was not found';
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $languageFieldName = $languageCapability->getLanguageField()->getName();
        $translationOriginPointerFieldName = $languageCapability->getTranslationOriginPointerField()->getName();
        if ($row[$languageFieldName] > 0) {
            return 'Record "' . $table . '_' . $uid . '" seems to be a translation already (has a language value "' . $row[$languageFieldName] . '", relation to record "' . $row[$translationOriginPointerFieldName] . '")';
        }
        if ($row[$translationOriginPointerFieldName] != 0) {
            return 'Record "' . $table . '_' . $uid . '" seems to be a translation already (has a relation to record "' . $row[$translationOriginPointerFieldName] . '")';
        }
        // Look for translations of this record, index by language field value:
        if (!empty($selFieldList)) {
            if (is_array($selFieldList)) {
                $selectFields = $selFieldList;
            } else {
                $selectFields = GeneralUtility::trimExplode(',', $selFieldList);
            }
        } else {
            $selectFields = ['uid', $languageFieldName];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUserAuthentication()->workspace));
        $queryBuilder
            ->select(...$selectFields)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $translationOriginPointerFieldName,
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(
                        $row['pid'],
                        Connection::PARAM_INT
                    )
                )
            );
        if (!$languageUid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gt(
                    $languageFieldName,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            );
        } else {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        $languageFieldName,
                        $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                    )
                );
        }
        $translationRecords = $queryBuilder->executeQuery()->fetchAllAssociative();

        $translations = [];
        $translationsErrors = [];
        foreach ($translationRecords as $translationRecord) {
            if (!isset($translations[$translationRecord[$languageFieldName]])) {
                $translations[$translationRecord[$languageFieldName]] = $translationRecord;
            } else {
                $translationsErrors[$translationRecord[$languageFieldName]][] = $translationRecord;
            }
        }
        return [
            'table' => $table,
            'uid' => $uid,
            'CType' => $row['CType'] ?? '',
            'sys_language_uid' => $row[$languageFieldName] ?? null,
            'translations' => $translations,
            'excessive_translations' => $translationsErrors,
        ];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
