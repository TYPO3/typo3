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
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * This Provider adds Backend Layouts based on page TSconfig
 *
 * = Example =
 * mod {
 * 	web_layout {
 * 		BackendLayouts {
 * 			example {
 * 				title = Example
 * 				config {
 * 					backend_layout {
 * 						colCount = 1
 * 						rowCount = 2
 * 						rows {
 * 							1 {
 * 								columns {
 * 									1 {
 * 										name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.3
 * 										colPos = 3
 * 										colspan = 1
 * 									}
 * 								}
 * 							}
 * 							2 {
 * 								columns {
 * 									1 {
 * 										name = Main
 * 										colPos = 0
 * 										colspan = 1
 * 									}
 * 								}
 * 							}
 * 						}
 * 					}
 * 				}
 * 				icon = EXT:example_extension/Resources/Public/Images/BackendLayouts/default.gif
 * 			}
 * 		}
 * 	}
 * }
 *
 * @internal Do not extend, change providers using $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']
 * @todo: Mark final in v13 and change protected to private.
 */
class PageTsBackendLayoutDataProvider implements DataProviderInterface
{
    /**
     * Internal Backend Layout stack
     */
    protected array $backendLayouts = [];

    public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection): void
    {
        $this->generateBackendLayouts($dataProviderContext, null);
        foreach ($this->backendLayouts as $backendLayoutConfig) {
            $backendLayout = $this->createBackendLayout($backendLayoutConfig);
            $backendLayoutCollection->add($backendLayout);
        }
    }

    /**
     * Gets a backend layout by (regular) identifier.
     *
     * @param string $identifier
     * @param int $pageId
     */
    public function getBackendLayout($identifier, $pageId): ?BackendLayout
    {
        $this->generateBackendLayouts(null, $pageId);
        if (array_key_exists($identifier, $this->backendLayouts)) {
            return $this->createBackendLayout($this->backendLayouts[$identifier]);
        }
        return null;
    }

    /**
     * Gets page TSconfig from DataProviderContext if available from context,
     * else fetch from BackendUtility by pageId.
     */
    protected function getPageTsConfig(?DataProviderContext $dataProviderContext, ?int $pageId): array
    {
        if ($dataProviderContext === null && $pageId === null) {
            throw new \RuntimeException('Either $dataProviderContext or $pageId must be provided', 1676380686);
        }
        if ($dataProviderContext) {
            return $dataProviderContext->getPageTsConfig();
        }
        return BackendUtility::getPagesTSconfig($pageId);
    }

    /**
     * Generate the Backend Layout configs
     */
    protected function generateBackendLayouts(?DataProviderContext $dataProviderContext, ?int $pageId)
    {
        $pageTsConfig = $this->getPageTsConfig($dataProviderContext, $pageId);
        if (!empty($pageTsConfig['mod.']['web_layout.']['BackendLayouts.'])) {
            $backendLayouts = (array)$pageTsConfig['mod.']['web_layout.']['BackendLayouts.'];
            foreach ($backendLayouts as $identifier => $data) {
                $backendLayout = $this->generateBackendLayoutFromTsConfig($identifier, $data);
                $this->attachBackendLayout($backendLayout);
            }
        }
    }

    /**
     * Generates a Backend Layout from page TSconfig array
     *
     * @param string $identifier
     * @param array $data
     * @return mixed
     */
    protected function generateBackendLayoutFromTsConfig($identifier, $data)
    {
        $backendLayout = [];
        if (!empty($data['config.']['backend_layout.']) && is_array($data['config.']['backend_layout.'])) {
            $backendLayout['uid'] = substr($identifier, 0, -1);
            $backendLayout['title'] = $data['title'] ?? $backendLayout['uid'];
            $backendLayout['icon'] = $data['icon'] ?? '';
            // Convert PHP array back to plain TypoScript to process it
            $config = ArrayUtility::flatten($data['config.']);
            $backendLayout['config'] = '';
            foreach ($config as $row => $value) {
                $backendLayout['config'] .= $row . ' = ' . $value . "\r\n";
            }
            return $backendLayout;
        }
        return null;
    }

    /**
     * Attach Backend Layout to internal Stack
     *
     * @param mixed $backendLayout
     */
    protected function attachBackendLayout($backendLayout = null)
    {
        if ($backendLayout) {
            $this->backendLayouts[$backendLayout['uid']] = $backendLayout;
        }
    }

    /**
     * Creates a new backend layout using the given record data.
     *
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
            $iconPath = $icon;
        }
        return $iconPath;
    }
}
