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
 * 				icon = content-container-columns-2
 * 			}
 * 		}
 * 	}
 * }
 *
 * @internal Do not extend, change providers using $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']
 */
final class PageTsBackendLayoutDataProvider implements DataProviderInterface
{
    /**
     * Internal Backend Layout stack
     */
    private array $backendLayouts = [];

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
    private function getPageTsConfig(?DataProviderContext $dataProviderContext, ?int $pageId): array
    {
        if ($dataProviderContext === null && $pageId === null) {
            throw new \RuntimeException('Either $dataProviderContext or $pageId must be provided', 1676380686);
        }
        if ($dataProviderContext) {
            return $dataProviderContext->pageTsConfig;
        }
        return BackendUtility::getPagesTSconfig($pageId);
    }

    /**
     * Generate the Backend Layout configs
     */
    private function generateBackendLayouts(?DataProviderContext $dataProviderContext, ?int $pageId): void
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
     */
    private function generateBackendLayoutFromTsConfig(string $identifier, array $data): ?array
    {
        $backendLayout = [];
        if (is_array($data['config.']['backend_layout.'] ?? null)) {
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
     */
    private function attachBackendLayout(mixed $backendLayout = null): void
    {
        if ($backendLayout) {
            $this->backendLayouts[$backendLayout['uid']] = $backendLayout;
        }
    }

    /**
     * Creates a new backend layout using the given record data.
     */
    private function createBackendLayout(array $data): BackendLayout
    {
        $backendLayout = BackendLayout::create((string)$data['uid'], $data['title'], $data['config']);
        $backendLayout->setIconPath($data['icon'] ?? '');
        $backendLayout->setData($data);
        return $backendLayout;
    }
}
