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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Manage a page tree with all test / demo styleguide data.
 */
abstract class AbstractGenerator
{
    /**
     * Create a site configuration on new styleguide root page
     */
    protected function createSiteConfiguration(int $topPageUid, string $base = 'http://localhost/', string $title = 'styleguide demo'): void
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        // When the DataHandler created the page tree, a default site configuration has been added. Fetch,  rename, update.
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($topPageUid);
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteIdentifier = 'styleguide-demo-' . $topPageUid;
        $siteConfiguration->rename($site->getIdentifier(), $siteIdentifier);
        $styleguideSysLanguages = $recordFinder->findUidsOfDemoLanguages();
        $configuration = [
            'base' => $base . 'styleguide-demo-' . $topPageUid,
            'rootPageId' => $topPageUid,
            'routes' => [],
            'websiteTitle' => $title . ' ' . $topPageUid,
            'baseVariants' => [],
            'errorHandling' => [],
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'navigationTitle' => 'English',
                    'hreflang' => 'en-us',
                    'direction' => 'ltr',
                    'flag' => 'us',
                    'websiteTitle' => '',
                ],
                [
                    'title' => 'styleguide demo language danish',
                    'enabled' => true,
                    'base' => '/da/',
                    'typo3Language' => 'da',
                    'locale' => 'da_DK.UTF-8',
                    'iso-639-1' => 'da',
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'fallbackType' => 'strict',
                    'fallbacks' => '',
                    'flag' => 'dk',
                    'languageId' => $styleguideSysLanguages[0],
                ],
                [
                    'title' => 'styleguide demo language german',
                    'enabled' => true,
                    'base' => '/de/',
                    'typo3Language' => 'de',
                    'locale' => 'de_DE.UTF-8',
                    'iso-639-1' => 'de',
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'fallbackType' => 'strict',
                    'fallbacks' => '',
                    'flag' => 'de',
                    'languageId' => $styleguideSysLanguages[1],
                ],
                [
                    'title' => 'styleguide demo language french',
                    'enabled' => true,
                    'base' => '/fr/',
                    'typo3Language' => 'fr',
                    'locale' => 'fr_FR.UTF-8',
                    'iso-639-1' => 'fr',
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'fallbackType' => 'strict',
                    'fallbacks' => '',
                    'flag' => 'fr',
                    'languageId' => $styleguideSysLanguages[2],
                ],
                [
                    'title' => 'styleguide demo language spanish',
                    'enabled' => true,
                    'base' => '/es/',
                    'typo3Language' => 'es',
                    'locale' => 'es_ES.UTF-8',
                    'iso-639-1' => 'es',
                    'websiteTitle' => '',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'fallbackType' => 'strict',
                    'fallbacks' => '',
                    'flag' => 'es',
                    'languageId' => $styleguideSysLanguages[3],
                ]
            ]
        ];
        $siteConfiguration->write($siteIdentifier, $configuration);
    }

    /**
     * Returns the uid of the last "top level" page (has pid 0)
     * in the page tree. This is either a positive integer or 0
     * if no page exists in the page tree at all.
     *
     * @return int
     */
    protected function getUidOfLastTopLevelPage(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $lastPage = $queryBuilder->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->orderBy('sorting', 'DESC')
            ->execute()
            ->fetchOne();
        $uid = 0;
        if (MathUtility::canBeInterpretedAsInteger($lastPage) && $lastPage > 0) {
            $uid = (int)$lastPage;
        }
        return $uid;
    }

    /**
     * Add files to fileadmin/
     *
     * @param array $files
     * @param string $from
     * @param string $to
     */
    protected function addToFal(array $files, string $from, string $to): void
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();

        try {
            $folder->createFolder($to);
            $folder = $folder->getSubfolder($to);
            foreach ($files as $fileName) {
                $sourceLocation = GeneralUtility::getFileAbsFileName($from . $fileName);
                $storage->addFile($sourceLocation, $folder, $fileName, DuplicationBehavior::RENAME, false);
            }
        } catch (ExistingTargetFolderException $e) {
            // No op if folder exists. This code assumes file exist, too.
        }
    }

    /**
     * Delete files from fileadmin/
     *
     * @param string $path
     */
    protected function deleteFalFolder(string $path): void
    {
        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();
        try {
            $folder = $folder->getSubfolder($path);
            $folder->delete(true);
        } catch (\InvalidArgumentException $e) {
            // No op if folder does not exist
        }
    }

    protected function write(array $data = [], array $commands = []): void
    {
        if (!empty($data) || !empty($commands)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->enableLogging = false;
            $dataHandler->bypassAccessCheckForRecords = true;
            $dataHandler->bypassWorkspaceRestrictions = true;
            $dataHandler->start($data, $commands);
            $dataHandler->clear_cacheCmd('all');

            empty($data) ?: $dataHandler->process_datamap();
            empty($commands) ?:$dataHandler->process_cmdmap();

            // Update signal only if not running in cli mode
            if (!Environment::isCli()) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }
        }
    }
}
