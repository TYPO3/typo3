<?php
namespace TYPO3\CMS\Backend\Provider;

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
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This Provider adds Backend Layouts based on PageTsConfig
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
 */
class PageTsBackendLayoutDataProvider implements DataProviderInterface
{
    /**
     * Internal Backend Layout stack
     *
     * @var array
     */
    protected $backendLayouts = [];

    /**
     * PageTs Config
     *
     * @var array
     */
    protected $pageTsConfig = [];

    /**
     * PageId
     *
     * @var int
     */
    protected $pageId = 0;

    /**
     * Set PageTsConfig
     *
     * @param array $pageTsConfig
     * @return void
     */
    protected function setPageTsConfig(array $pageTsConfig)
    {
        $this->pageTsConfig = $pageTsConfig;
    }

    /**
     * Get PageTsConfig
     *
     * @return array
     */
    protected function getPageTsConfig()
    {
        return $this->pageTsConfig;
    }

    /**
     * Set PageId
     *
     * @param int $pageId
     * @return void
     */
    protected function setPageId($pageId)
    {
        $this->pageId = (int)$pageId;
    }

    /**
     * Get PageId
     *
     * @return int
     */
    protected function getPageId()
    {
        return (int)$this->pageId;
    }

    /**
     * Gets PageTsConfig from DataProviderContext if available,
     * if not it will be generated for the current Page.
     *
     * @param DataProviderContext $dataProviderContext
     * @return void
     */
    protected function generatePageTsConfig($dataProviderContext = null)
    {
        if ($dataProviderContext === null) {
            $pageId = $this->getPageId();
            $pageId = $pageId > 0 ? $pageId : (int)GeneralUtility::_GP('id');
            $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);
        } else {
            $pageTsConfig = $dataProviderContext->getPageTsConfig();
        }
        $this->setPageTsConfig($pageTsConfig);
    }

    /**
     * Generate the Backend Layout configs
     *
     * @param DataProviderContext $dataProviderContext
     * @return void
     */
    protected function generateBackendLayouts($dataProviderContext = null)
    {
        $this->generatePageTsConfig($dataProviderContext);
        $pageTsConfig = $this->getPageTsConfig();
        if (!empty($pageTsConfig['mod.']['web_layout.']['BackendLayouts.'])) {
            $backendLayouts = (array)$pageTsConfig['mod.']['web_layout.']['BackendLayouts.'];
            foreach ($backendLayouts as $identifier => $data) {
                $backendLayout = $this->generateBackendLayoutFromTsConfig($identifier, $data);
                $this->attachBackendLayout($backendLayout);
            }
        }
    }

    /**
     * Generates a Backend Layout from PageTsConfig array
     *
     * @return mixed
     */
    protected function generateBackendLayoutFromTsConfig($identifier, $data)
    {
        if (!empty($data['config.']['backend_layout.']) && is_array($data['config.']['backend_layout.'])) {
            $backendLayout['uid'] = substr($identifier, 0, -1);
            $backendLayout['title'] = ($data['title']) ? $data['title'] : $backendLayout['uid'];
            $backendLayout['icon'] = ($data['icon']) ? $data['icon'] : '';
            // Convert PHP array back to plain TypoScript so it can be procecced
            $config = \TYPO3\CMS\Core\Utility\ArrayUtility::flatten($data['config.']);
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
     * @param DataProviderContext $dataProviderContext
     * @param BackendLayoutCollection $backendLayoutCollection
     * @return void
     */
    public function addBackendLayouts(DataProviderContext $dataProviderContext, BackendLayoutCollection $backendLayoutCollection)
    {
        $this->generateBackendLayouts($dataProviderContext);
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
     * @return NULL|BackendLayout
     */
    public function getBackendLayout($identifier, $pageId)
    {
        $this->setPageId($pageId);
        $this->generateBackendLayouts();
        $backendLayout = null;
        if (array_key_exists($identifier, $this->backendLayouts)) {
            return $this->createBackendLayout($this->backendLayouts[$identifier]);
        }
        return $backendLayout;
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
            $iconPath = $icon;
        }
        return $iconPath;
    }
}
