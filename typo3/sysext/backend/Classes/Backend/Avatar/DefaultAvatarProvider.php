<?php
namespace TYPO3\CMS\Backend\Backend\Avatar;

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
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DefaultAvatarProvider
 */
class DefaultAvatarProvider implements AvatarProviderInterface
{
    /**
     * Get Image
     *
     * @param array $backendUser be_users record
     * @param int $size
     * @return Image|NULL
     */
    public function getImage(array $backendUser, $size)
    {
        $fileUid = $this->getAvatarFileUid($backendUser['uid']);

        // Get file object
        try {
            $file = ResourceFactory::getInstance()->getFileObject($fileUid);
            $processedImage = $file->process(
                ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                ['width' => $size . 'c', 'height' => $size . 'c']
            );

            $image = GeneralUtility::makeInstance(
                Image::class,
                $processedImage->getPublicUrl(),
                $processedImage->getProperty('width'),
                $processedImage->getProperty('height')
            );
        } catch (FileDoesNotExistException $e) {
            // No image found
            $image = null;
        }

        return $image;
    }

    /**
     * Get Avatar fileUid
     *
     * @param int $beUserId
     * @return int
     */
    protected function getAvatarFileUid($beUserId)
    {
        $file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid_local',
            'sys_file_reference',
            'tablenames = \'be_users\' AND fieldname = \'avatar\' AND ' .
            'table_local = \'sys_file\' AND uid_foreign = ' . (int)$beUserId .
            BackendUtility::BEenableFields('sys_file_reference') . BackendUtility::deleteClause('sys_file_reference')
        );
        return $file ? $file['uid_local'] : 0;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
