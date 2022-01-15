<?php

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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout data provider class
 */
class DefaultDataProvider implements DataProviderInterface
{

    /**
     * @var string
     * Table name for backend_layouts
     */
    protected $tableName = 'backend_layout';

    /**
     * Adds backend layouts to the given backend layout collection.
     * The default backend layout ('default_default') is not added
     * since it's the default fallback if nothing is specified.
     *
     * @param DataProviderContext $dataProviderContext
     * @param BackendLayoutCollection $backendLayoutCollection
     */
    public function addBackendLayouts(
        DataProviderContext $dataProviderContext,
        BackendLayoutCollection $backendLayoutCollection
    ) {
        $layoutData = $this->getLayoutData(
            $dataProviderContext->getFieldName(),
            $dataProviderContext->getPageTsConfig(),
            $dataProviderContext->getPageId()
        );

        foreach ($layoutData as $data) {
            $backendLayout = $this->createBackendLayout($data);
            $backendLayoutCollection->add($backendLayout);
        }
    }

    /**
     * Gets a backend layout by (regular) identifier.
     *
     * @param string|int $identifier
     * @param int $pageId
     * @return BackendLayout|null
     */
    public function getBackendLayout($identifier, $pageId)
    {
        $backendLayout = null;

        if ((string)$identifier === 'default') {
            return $this->createDefaultBackendLayout();
        }

        $data = BackendUtility::getRecordWSOL($this->tableName, (int)$identifier);

        if (is_array($data)) {
            $backendLayout = $this->createBackendLayout($data);
        }

        return $backendLayout;
    }

    /**
     * Creates a backend layout with the default configuration.
     *
     * @return BackendLayout
     */
    protected function createDefaultBackendLayout()
    {
        return BackendLayout::create(
            'default',
            'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.backend_layout.default',
            BackendLayoutView::getDefaultColumnLayout()
        );
    }

    /**
     * Creates a new backend layout using the given record data.
     *
     * @param array $data
     * @return BackendLayout
     */
    protected function createBackendLayout(array $data)
    {
        $backendLayout = BackendLayout::create($data['uid'], $data['title'], $data['config']);
        $backendLayout->setIconPath($this->getIconPath($data));
        $backendLayout->setData($data);
        return $backendLayout;
    }

    /**
     * Resolves the icon from the database record
     *
     * @param array $icon
     * @return string
     */
    protected function getIconPath(array $icon)
    {
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $references = $fileRepository->findByRelation($this->tableName, 'icon', $icon['uid']);
        if (!empty($references)) {
            $icon = reset($references);
            return $icon->getPublicUrl();
        }
        return '';
    }

    /**
     * Get all layouts from the core's default data provider.
     *
     * @param string $fieldName the name of the field the layouts are provided for (either backend_layout or backend_layout_next_level)
     * @param array $pageTsConfig PageTSconfig of the given page
     * @param int $pageUid the ID of the page wea re getting the layouts for
     * @return array $layouts A collection of layout data of the registered provider
     */
    protected function getLayoutData($fieldName, array $pageTsConfig, $pageUid)
    {
        $storagePid = $this->getStoragePid($pageTsConfig);
        $pageTsConfigId = $this->getPageTSconfigIds($pageTsConfig);

        // Add layout records
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()
            ->add(
                GeneralUtility::makeInstance(
                    WorkspaceRestriction::class,
                    GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id')
                )
            );
        $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->comparison(
                            $queryBuilder->createNamedParameter($pageTsConfigId[$fieldName], \PDO::PARAM_INT),
                            ExpressionBuilder::EQ,
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->comparison(
                            $queryBuilder->createNamedParameter($storagePid, \PDO::PARAM_INT),
                            ExpressionBuilder::EQ,
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq(
                            'backend_layout.pid',
                            $queryBuilder->createNamedParameter($pageTsConfigId[$fieldName], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'backend_layout.pid',
                            $queryBuilder->createNamedParameter($storagePid, \PDO::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->comparison(
                            $queryBuilder->createNamedParameter($pageTsConfigId[$fieldName], \PDO::PARAM_INT),
                            ExpressionBuilder::EQ,
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'backend_layout.pid',
                            $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                        )
                    )
                )
            );

        if (!empty($GLOBALS['TCA'][$this->tableName]['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA'][$this->tableName]['ctrl']['sortby']);
        }

        $statement = $queryBuilder->executeQuery();

        $results = [];
        while ($record = $statement->fetchAssociative()) {
            BackendUtility::workspaceOL($this->tableName, $record);
            if (is_array($record)) {
                $results[$record['t3ver_oid'] ?: $record['uid']] = $record;
            }
        }

        return $results;
    }

    /**
     * Returns the storage PID from TCEFORM.
     *
     * @param array $pageTsConfig
     * @return int
     */
    protected function getStoragePid(array $pageTsConfig)
    {
        $storagePid = 0;

        if (!empty($pageTsConfig['TCEFORM.']['pages.']['_STORAGE_PID'])) {
            $storagePid = (int)$pageTsConfig['TCEFORM.']['pages.']['_STORAGE_PID'];
        }

        return $storagePid;
    }

    /**
     * Returns the page TSconfig from TCEFORM.
     *
     * @param array $pageTsConfig
     * @return array
     */
    protected function getPageTSconfigIds(array $pageTsConfig)
    {
        $pageTsConfigIds = [
            'backend_layout' => 0,
            'backend_layout_next_level' => 0,
        ];

        if (!empty($pageTsConfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID'])) {
            $pageTsConfigIds['backend_layout'] = (int)$pageTsConfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID'];
        }

        if (!empty($pageTsConfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID'])) {
            $pageTsConfigIds['backend_layout_next_level'] = (int)$pageTsConfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID'];
        }

        return $pageTsConfigIds;
    }
}
