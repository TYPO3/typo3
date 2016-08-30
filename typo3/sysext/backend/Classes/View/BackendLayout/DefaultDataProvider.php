<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Backend layout data provider class
 */
class DefaultDataProvider implements DataProviderInterface
{
    /**
     * Adds backend layouts to the given backend layout collection.
     * The default backend layout ('default_default') is not added
     * since it's the default fallback if nothing is specified.
     *
     * @param DataProviderContext $dataProviderContext
     * @param BackendLayoutCollection $backendLayoutCollection
     * @return void
     */
    public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection)
    {
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
     * @param string $identifier
     * @param int $pageId
     * @return NULL|BackendLayout
     */
    public function getBackendLayout($identifier, $pageId)
    {
        $backendLayout = null;

        if ((string)$identifier === 'default') {
            return $this->createDefaultBackendLayout();
        }

        $data = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'backend_layout',
            'uid=' . (int)$identifier . BackendUtility::BEenableFields('backend_layout') . BackendUtility::deleteClause('backend_layout')
        );
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
            \TYPO3\CMS\Backend\View\BackendLayoutView::getDefaultColumnLayout()
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
        $backendLayout->setIconPath($this->getIconPath($data['icon']));
        $backendLayout->setData($data);
        return $backendLayout;
    }

    /**
     * Gets and sanitizes the icon path.
     *
     * @param string $icon Name of the icon file
     * @return string
     */
    protected function getIconPath($icon)
    {
        $iconPath = '';

        if (!empty($icon)) {
            $path = rtrim($GLOBALS['TCA']['backend_layout']['ctrl']['selicon_field_path'], '/') . '/';
            $iconPath = '../' . $path . $icon;
        }

        return $iconPath;
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
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'backend_layout',
                '(
					( ' . (int)$pageTsConfigId[$fieldName] . ' = 0 AND ' . (int)$storagePid . ' = 0 )
					OR ( backend_layout.pid = ' . (int)$pageTsConfigId[$fieldName] . ' OR backend_layout.pid = ' . (int)$storagePid . ' )
					OR ( ' . (int)$pageTsConfigId[$fieldName] . ' = 0 AND backend_layout.pid = ' . (int)$pageUid . ' )
				) ' . BackendUtility::BEenableFields('backend_layout') . BackendUtility::deleteClause('backend_layout'),
            '',
            'sorting ASC'
        );

        if (!is_array($results)) {
            $results = [];
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

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
