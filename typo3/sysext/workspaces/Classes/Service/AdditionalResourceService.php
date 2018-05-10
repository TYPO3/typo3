<?php
namespace TYPO3\CMS\Workspaces\Service;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Service for additional columns in GridPanel
 */
class AdditionalResourceService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $javaScriptResources = [];

    /**
     * @var array
     */
    protected $stylesheetResources = [];

    /**
     * @var array
     */
    protected $localizationResources = [];

    /**
     * @return AdditionalResourceService
     */
    public static function getInstance()
    {
        return self::getObjectManager()->get(AdditionalResourceService::class);
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @param string $name
     * @param string $resourcePath
     */
    public function addJavaScriptResource($name, $resourcePath)
    {
        $this->javaScriptResources[$name] = $this->resolvePath($resourcePath);
    }

    /**
     * @param string $name
     * @param string $resourcePath
     */
    public function addStylesheetResource($name, $resourcePath)
    {
        $this->stylesheetResources[$name] = $this->resolvePath($resourcePath);
    }

    /**
     * @param string $resourcePath
     */
    public function addLocalizationResource($resourcePath)
    {
        $absoluteResourcePath = GeneralUtility::getFileAbsFileName($resourcePath);
        $this->localizationResources[$absoluteResourcePath] = $absoluteResourcePath;
    }

    /**
     * @return array
     */
    public function getJavaScriptResources()
    {
        return $this->javaScriptResources;
    }

    /**
     * @return array
     */
    public function getStyleSheetResources()
    {
        return $this->stylesheetResources;
    }

    /**
     * @return array
     */
    public function getLocalizationResources()
    {
        return $this->localizationResources;
    }

    /**
     * Resolve path
     *
     * @param string $resourcePath
     * @return string|null
     */
    protected function resolvePath($resourcePath)
    {
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($resourcePath);
        $absolutePath = PathUtility::dirname($absoluteFilePath);
        $fileName = PathUtility::basename($absoluteFilePath);

        return PathUtility::getRelativePathTo($absolutePath) . $fileName;
    }
}
