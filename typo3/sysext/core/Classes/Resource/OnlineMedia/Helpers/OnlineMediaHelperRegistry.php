<?php
namespace TYPO3\CMS\Core\Resource\OnlineMedia\Helpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Online Media Source Registry
 */
class OnlineMediaHelperRegistry implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Returns an instance of this class
     *
     * @return OnlineMediaHelperRegistry
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Get helper class for given File
     *
     * @param File $file
     * @return bool|OnlineMediaHelperInterface
     */
    public function getOnlineMediaHelper(File $file)
    {
        $registeredHelpers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'];
        if (isset($registeredHelpers[$file->getExtension()])) {
            return GeneralUtility::makeInstance($registeredHelpers[$file->getExtension()], $file->getExtension());
        }
        return false;
    }

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @param Folder $targetFolder
     * @param string[] $allowedExtensions
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder, $allowedExtensions = [])
    {
        $registeredHelpers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'];
        foreach ($registeredHelpers as $extension => $className) {
            if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
                continue;
            }
            /** @var OnlineMediaHelperInterface $helper */
            $helper = GeneralUtility::makeInstance($className, $extension);
            $file = $helper->transformUrlToFile($url, $targetFolder);
            if ($file !== null) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Get all file extensions that have a OnlineMediaHelper
     *
     * @return string[]
     */
    public function getSupportedFileExtensions()
    {
        return array_keys($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']);
    }
}
